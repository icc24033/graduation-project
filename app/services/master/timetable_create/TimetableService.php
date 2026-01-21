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
}