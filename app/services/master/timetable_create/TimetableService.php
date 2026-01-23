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
     * 
     * 目的: 時間割り作成画面で編集されたデータを保存するため
     */
    public function saveTimetable($data) {
        $id = $data['id'] ?? null; // IDがあれば更新、なければ新規
        $courseId = $data['course_id'];
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];
        $details = $data['timetable_data'] ?? []; // グリッドの中身

        // トランザクション開始のためにPDOを取得（RepositoryFactory経由などで取得できる前提、あるいはコンストラクタで確保）
        // ※ここでは簡易的にリポジトリからPDOにアクセスするか、Service内でPDOを持つ構造にしてください。
        $repo = RepositoryFactory::getTimetableRepository();
        $pdo = $repo->getConnection(); // BaseRepositoryにgetConnection()がある前提

        try {
            $pdo->beginTransaction();

            if ($id) {
                // --- 更新処理 ---
                $repo->updateTimetable($id, $startDate, $endDate);
                // 詳細データは一度削除して作り直す（Delete-Insert）
                $repo->deleteDetailsByTimetableId($id);
            } else {
                // --- 新規作成処理 ---
                $timetableName = $data['timetable_name'] ?? '新規時間割'; // 名前が必要ならJSから送る
                $id = $repo->createTimetable($courseId, $startDate, $endDate, $timetableName);
            }

            // --- 詳細データの登録 ---
            // 曜日変換マップ（JSから「月」などの文字で来る場合の対策）
            $dayMap = ['月' => 1, '火' => 2, '水' => 3, '木' => 4, '金' => 5, '土' => 6, '日' => 7];

            foreach ($details as $row) {
                // 1. 曜日の変換 (数値で来ているならそのまま、文字なら変換)
                $dayVal = $row['day'];
                if (!is_numeric($dayVal) && isset($dayMap[$dayVal])) {
                    $dayVal = $dayMap[$dayVal];
                }

                if (empty($dayVal) || !is_numeric($dayVal)) {
                    error_log("スキップされたデータ: dayの値が不正です (" . print_r($row['day'], true) . ")");
                    continue; 
                }

                // 2. detail (科目) の登録
                // JS側のキー名 (subjectId) と合わせる
                $subjectId = $row['subjectId'];
                if (!$subjectId) continue; // 科目がない空データはスキップ

                $detailId = $repo->addDetail($id, $dayVal, $row['period'], $subjectId);

                // 3. teacher/room の登録
                // ※ JSから teacherIds (配列) で来るか、teacherId (単一) で来るかで処理を分ける
                // 今回はシンプルに「単一の先生・教室」として処理する例
                $teacherId = !empty($row['teacherId']) ? $row['teacherId'] : 0; // 0=未定など
                $roomId = !empty($row['roomId']) ? $row['roomId'] : null;

                $repo->addDetailTeacher($detailId, $teacherId, $roomId);
            }

            $pdo->commit();
            return $id; // 保存したIDを返す

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}