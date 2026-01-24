<?php
// TimetableService.php
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';
require_once __DIR__ . '/../../../classes/helper/dropdown/ViewHelper.php';

class TimetableService {

    // クラス内キャッシュ用プロパティ
    private $courses = null;

    /**
     * loadCourses
     * 概要：コンストラクタ、または必要時にコース情報をロードする
     * 戻り値：なし
     * 取得情報：course_id, course_name, grade
     */
    private function loadCourses() {
        if ($this->courses === null) {
            $courseRepo = RepositoryFactory::getCourseRepository();
            $this->courses = $courseRepo->getAllCoursesIncludedGrade();
        }
    }

    /**
     * getCourseDropdownOptions
     * 概要：コースのドロップダウンオプションを配列で取得
     * 戻り値：配列（course_id, course_name）
     * ※loadCoursesを実行し、プロパティを参照してjsで使用するための全コース情報（生データ）を取得する
     */
    public function getRawCourseData() {
        $this->loadCourses();
        return $this->courses;
    }

    public function getSidebarCourseListHtml() {
        $this->loadCourses();
        // ViewHelperを使って <li>...</li> のリストを生成する
        // 第3引数のラベルキーは 'course_name' を使用
        return ViewHelper::renderDropdownList($this->courses, 'course_id', 'course_name');
    }

    /**
     * getCoursesHtmlWithGradeData
     * 概要：コースのドロップダウンオプションをgrade付きでHTML化して取得
     * 戻り値：HTML文字列（course_id, course_name, grade）
     * ※ ViewHelperを使用してHTML化
     * ※ 新規作成時のポップアップで使用する
     */
    public function getCoursesHtmlWithGradeData() {
        $this->loadCourses();
        return ViewHelper::renderDropdownList($this->courses, 'course_id', 'course_name', 'grade');
    }

    /**
     * getAllTimetableData
     * 概要：すべての時間割データを取得する
     * 戻り値：配列（すべての時間割データ）
     */
    public function getAllTimetableData() {
        $allTimetables = [];
        $this->loadCourses(); // コース情報確保

        try {
            $timetableRepo = RepositoryFactory::getTimetableRepository();

            foreach ($this->courses as $course) {
                $courseId = $course['course_id'];
                $timetables = $timetableRepo->getTimetablesByCourseId($courseId);

                if (!empty($timetables)) {
                    foreach ($timetables as $t) {
                        $allTimetables[] = $t;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("TimetableService Error: " . $e->getMessage());
            return [];
        }

        return $allTimetables;
    }

    /**
     * getAllCourseMasterData
     * 概要：マスタデータの取得
     * 戻り値：配列（course_idをキーにした「科目・教員・教室」の組み合わせデータ）
     * 目的：時間割り作成画面で、特定のコースに割り当てられた科目情報を取得し、ドロップダウンリストの選択肢として表示するため
     */
    public function getAllCourseMasterData() {
        $masterData = [];
        $this->loadCourses(); // コース情報確保

        try {
            $sicRepo = RepositoryFactory::getSubjectInChargesRepository();

            foreach ($this->courses as $course) {
                $courseId = $course['course_id'];
                // ここで学年(grade)によるフィルタが必要なら $course['grade'] を渡せます
                $definitions = $sicRepo->getSubjectDefinitionsByCourse($courseId);
                $masterData[$courseId] = $definitions;
            }
        } catch (Exception $e) {
            error_log("TimetableService::getAllCourseMasterData Error: " . $e->getMessage());
            return [];
        }

        return $masterData;
    }

    /**
     * updateStatusAutomatically
     * 概要: 指定されたコース内の全時間割のステータスを日付に基づいて自動更新する
     * ロジック:
     * 0: 過去 (endDate < 今日)
     * 1: 現在 (startDate <= 今日 <= endDate)
     * 2: 次回 (今日 < startDate の中で一番早いもの)
     * 3~: 次回以降 (次回以外の未来の時間割に対して、日付が近い順に番号を振る)
     */
    private function updateStatusAutomatically($courseId, $pdo) {
        $today = date('Y-m-d');

        // 1. そのコースの全時間割を取得 (日付などでソートしておく)
        $sql = "SELECT timetable_id, start_date, end_date FROM timetables 
                WHERE course_id = :courseId 
                ORDER BY start_date ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':courseId', $courseId, PDO::PARAM_INT);
        $stmt->execute();
        $timetables = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($timetables)) return;

        // 未来の時間割を格納する配列
        $futureTimetables = [];

        // 更新用SQLステートメント
        $updateSql = "UPDATE timetables SET status_type = :status WHERE timetable_id = :id";
        $updateStmt = $pdo->prepare($updateSql);

        foreach ($timetables as $t) {
            $tid = $t['timetable_id'];
            $start = $t['start_date'];
            $end = $t['end_date'];

            $status = 0; // デフォルト（エラー回避用で設定する）

            if ($end < $today) {
                // 過去
                $status = 0;
                $updateStmt->bindValue(':status', 0, PDO::PARAM_INT);
                $updateStmt->bindValue(':id', $tid, PDO::PARAM_INT);
                $updateStmt->execute();
            } elseif ($start <= $today && $today <= $end) {
                // 現在適用中
                $status = 1;
                $updateStmt->bindValue(':status', 1, PDO::PARAM_INT);
                $updateStmt->bindValue(':id', $tid, PDO::PARAM_INT);
                $updateStmt->execute();
            } else {
                // 未来 (後でソートして 2, 3... を振る)
                $futureTimetables[] = $t;
            }
        }

        // 未来分のステータス更新
        // SQLでASCソートしているので、配列の先頭が「次回(2)」になる
        if (!empty($futureTimetables)) {
            $nextStatus = 2; // 次回は2からスタート
            foreach ($futureTimetables as $ft) {
                $updateStmt->bindValue(':status', $nextStatus, PDO::PARAM_INT);
                $updateStmt->bindValue(':id', $ft['timetable_id'], PDO::PARAM_INT);
                $updateStmt->execute();
                
                // 次回以降はステータス番号を増やしていく（3, 4, 5...）
                // 仕様に合わせて固定値「3」にする場合はここを調整
                $nextStatus++; 
            }
        }
    }

    /**
     * saveTimetable
     * 概要: 時間割データの保存（新規・更新の自動判別）
     * 引数: $data - 保存する時間割データの配列 (詳細は下記参照)
     * 戻り値: 保存された時間割ID
     * $dataの構造例:
     * [
     *   'id' => (int|null) 既存の時間割ID（新規の場合はnullまたは未設定）
     *   'course_id' => (int) コースID
     *   'start_date' => (string) 開始日 (YYYY-MM-DD)
     *   'end_date' => (string) 終了日 (YYYY-MM-DD)
     *   'timetable_name' => (string) 時間割名 (新規作成時のみ必要)
     *   'timetable_data' => [ // グリッドの中身
     *      [
     *          'day' => (string|int) 曜日 ("月"などの文字列、または1-7の数値)
     *          'period' => (int) 時限
     *          'subjectId' => (int) 科目ID
     *          'teacherId' => (int) 教員ID
     *          'roomId' => (int) 教室ID
     *      ],
     *   ...
     * ]
     * 
     * 注意: トランザクション処理を含むため、例外発生時にはロールバックされます
     * ※ 科目・教員・教室の紐づけは subject_in_charges テーブルで事前に設定されている必要があります
     * ※ teacherId, roomId は省略可能（未設定の場合は0やNULLで保存されます）
     * 戻り値: 保存された時間割ID
     * 目的: 時間割り作成画面で編集されたデータを保存するため
     */
    public function saveTimetable($data) {
        $id = $data['id'] ?? null;
        $courseId = $data['course_id'];
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];
        $details = $data['timetable_data'] ?? [];

        $repo = RepositoryFactory::getTimetableRepository();
        $pdo = $repo->getConnection();

        // トランザクション開始
        try {
            $pdo->beginTransaction();

            if ($id) {
                $repo->updateTimetable($id, $startDate, $endDate);
                $repo->deleteDetailsByTimetableId($id);
            } else {
                $timetableName = $data['timetable_name'] ?? '新規時間割';
                // 一旦 status_type=0 などで作成（後で更新されるので何でも良い）
                $id = $repo->createTimetable($courseId, $startDate, $endDate, $timetableName);
            }

            // 詳細データの登録処理
            $dayMap = ['月' => 1, '火' => 2, '水' => 3, '木' => 4, '金' => 5, '土' => 6, '日' => 7];

            foreach ($details as $row) {
                // 1. 曜日の変換
                $dayVal = $row['day'];
                if (!is_numeric($dayVal) && isset($dayMap[$dayVal])) {
                    $dayVal = $dayMap[$dayVal];
                }
                if (empty($dayVal) || !is_numeric($dayVal)) continue;

                // 2. detail (科目) の登録
                $subjectId = $row['subjectId'];
                if (!$subjectId) continue;

                $detailId = $repo->addDetail($id, $dayVal, $row['period'], $subjectId);

                // 3. teacher/room の登録（複数の登録に対応する）
                // JSから送られてくるのは teacherIds, roomIds という配列
                $teacherIds = $row['teacherIds'] ?? [];
                $roomIds = $row['roomIds'] ?? [];

                // 配列でない場合の正規化を実施する
                if (!is_array($teacherIds)) $teacherIds = [];
                if (!is_array($roomIds)) $roomIds = [];

                // teacher_id はDB制約上 NOT NULL なので、教員が0人の場合は登録できない
                // ※空の場合はスキップする
                if (empty($teacherIds)) continue;

                // ループ回数は「多い方」に合わせる
                // 例: 先生2人, 教室3つ → 3回ループ
                $loopCount = max(count($teacherIds), count($roomIds));

                for ($i = 0; $i < $loopCount; $i++) {
                    // 先生IDの決定
                    // インデックスに対応する先生がいればその先生、いなければ「最後の先生」を割り当てる
                    // これにより「先生A, 先生B」で「教室1, 教室2, 教室3」の場合、(A-1), (B-2), (B-3) のように保存される
                    if (isset($teacherIds[$i])) {
                        $tId = $teacherIds[$i];
                    } else {
                        $tId = end($teacherIds); // 最後の要素を取得
                    }

                    // 教室IDの決定（足りない場合は NULL）
                    $rId = isset($roomIds[$i]) ? $roomIds[$i] : null;

                    // DB登録処理
                    $repo->addDetailTeacher($detailId, $tId, $rId);
                }
            }

            // ここでステータスを自動更新する
            // トランザクション内で行うことで整合性を保つ
            $this->updateStatusAutomatically($courseId, $pdo);

            $pdo->commit();
            return $id;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * deleteTimetable
     * 概要: 時間割の削除処理
     * 引数: $id - 時間割りID
     * 戻り値: bool 削除に成功したらtrue
     */
    public function deleteTimetable($id) {
        try {
            $repo = RepositoryFactory::getTimetableRepository();
            $deletedCount = $repo->deleteTimetable($id);
            return $deletedCount > 0;
        } catch (Exception $e) {
            error_log("TimetableService::deleteTimetable Error: " . $e->getMessage());
            throw $e;
        }
    }
}