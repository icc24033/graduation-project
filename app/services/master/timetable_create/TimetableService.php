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
     * 概要: 指定されたコースの時間割ステータスを自動更新する
     * 引数: $courseId - コースID
     *       $pdo - PDO接続オブジェクト
     * 戻り値: なし
     * 目的: 時間割り作成・更新後に、時間割のステータスを自動的に最新化するため
     */
    public function updateStatusAutomatically($courseId, $pdo) {
        $today = date('Y-m-d');

        // ----------------------------------------------------
        // ① 今日すでに更新済みかチェック
        // ----------------------------------------------------
        $checkSql = "SELECT last_status_update FROM course WHERE course_id = :courseId";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->bindValue(':courseId', $courseId, PDO::PARAM_INT);
        $checkStmt->execute();
        $lastUpdate = $checkStmt->fetchColumn();

        // 最終更新日が今日なら、何もせず終了 (Return)
        if ($lastUpdate === $today) {
            return; 
        }

        // ----------------------------------------------------
        // ② ステータス更新ロジック (既存コード + 負荷軽減)
        // ----------------------------------------------------
        
        // 「過去」ですでに「ステータス0」になっているものは取得対象から外し負荷軽減を図る
        // (end_date < 今日 かつ status_type != 0) OR (end_date >= 今日) のような条件

        // 対象の全時間割を取得
        $sql = "SELECT timetable_id, start_date, end_date FROM timetables 
                WHERE course_id = :courseId 
                ORDER BY start_date ASC"; // 日付順は必須
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':courseId', $courseId, PDO::PARAM_INT);
        $stmt->execute();
        $timetables = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($timetables)) {
            // データがなくても「今日チェックした」ことに更新しておく
            $this->markCourseAsUpdated($courseId, $today, $pdo);
            return;
        }

        // 更新用SQL
        $updateSql = "UPDATE timetables SET status_type = :status WHERE timetable_id = :id";
        $updateStmt = $pdo->prepare($updateSql);

        $futureTimetables = [];

        foreach ($timetables as $t) {
            $tid = $t['timetable_id'];
            $start = $t['start_date'];
            $end = $t['end_date'];
            
            // 判定ロジック
            if ($end < $today) {
                // 過去 (0)
                $updateStmt->bindValue(':status', 0, PDO::PARAM_INT);
                $updateStmt->bindValue(':id', $tid, PDO::PARAM_INT);
                $updateStmt->execute();
            } elseif ($start <= $today && $today <= $end) {
                // 現在 (1)
                $updateStmt->bindValue(':status', 1, PDO::PARAM_INT);
                $updateStmt->bindValue(':id', $tid, PDO::PARAM_INT);
                $updateStmt->execute();
            } else {
                // 未来 (後で採番)
                $futureTimetables[] = $t;
            }
        }

        // 未来分の更新 (2, 3, ...)
        if (!empty($futureTimetables)) {
            $nextStatus = 2;
            foreach ($futureTimetables as $ft) {
                $updateStmt->bindValue(':status', $nextStatus, PDO::PARAM_INT);
                $updateStmt->bindValue(':id', $ft['timetable_id'], PDO::PARAM_INT);
                $updateStmt->execute();
                // 次回以降は 3 で固定する仕様であれば $nextStatus = 3; をループ内で制御
                // 番号を増やすならインクリメント
                $nextStatus++; 
            }
        }

        // ----------------------------------------------------
        // ③ 最後に「今日更新した」ことを記録
        // ----------------------------------------------------
        $this->markCourseAsUpdated($courseId, $today, $pdo);
    }

    // 更新日記録用のヘルパーメソッド
    private function markCourseAsUpdated($courseId, $today, $pdo) {
        $sql = "UPDATE course SET last_status_update = :today WHERE course_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':today', $today, PDO::PARAM_STR);
        $stmt->bindValue(':id', $courseId, PDO::PARAM_INT);
        $stmt->execute();
    }

    // 時間割りデータのstatusTypeをすべてのコースで更新する（外部から呼び出し可能）
    public function updateTimetableStatusTypeForAllCourses() {
        try {
            // 先生や管理者の場合、担当コースまたは全コースをチェック
            // ここでは例として全コースチェック
            $courseRepo = RepositoryFactory::getCourseRepository();
            $pdo = RepositoryFactory::getPdo();
            $courses = $courseRepo->getAllCoursesIncludedGrade();
            foreach($courses as $course) {
                $this->updateStatusAutomatically($course['course_id'], $pdo);
            }
        } catch (Exception $e) {
            // 更新処理のエラーでログイン自体を落とさないようログ出力に留める
            error_log("Status Auto Update Failed: " . $e->getMessage());
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