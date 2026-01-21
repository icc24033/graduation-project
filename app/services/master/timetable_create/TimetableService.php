<?php
// TimetableService.php
// クラスの読み込み（パスは環境に合わせて調整してください）
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';
require_once __DIR__ . '/../../../classes/helper/dropdown/ViewHelper.php';

class TimetableService {

    private $courses;

    /**
     * getAllTimetableData
     * 概要：すべてのコースの時間割データを取得し、JSが読み込める形式で返す関数
     * 戻り値：すべての時間割データの配列
     */
    public function getAllTimetableData() {
        $allTimetables = [];

        try {
            // 1. 各リポジトリの取得
            $courseRepo = RepositoryFactory::getCourseRepository();
            $timetableRepo = RepositoryFactory::getTimetableRepository();

            // 2. コース全取得
            $this->courses = $courseRepo->getAllCourses();

            // 3. コースごとにループして時間割を取得
            foreach ($this->courses as $course) {
                $courseId = $course['course_id'];
                
                // 特定のコースの時間割を取得
                $timetables = $timetableRepo->getTimetablesByCourseId($courseId);

                // データがあれば結合
                if (!empty($timetables)) {
                    // array_mergeだとキーが連番でリセットされる可能性があるため、単純に追加
                    foreach ($timetables as $t) {
                        $allTimetables[] = $t;
                    }
                }
            }

        } catch (Exception $e) {
            // エラーログ出力など
            error_log("TimetableService Error: " . $e->getMessage());
            return []; // エラー時は空配列を返す
        }

        return $allTimetables;
    }

    /**
     * 
     * 概要：コースの情報を返す関数
     * 戻り値：コース情報が格納された配列
     */
    public function getCourseDropdownOptions() {
        $courseRepo = RepositoryFactory::getCourseRepository();
        $courses = $courseRepo->getAllCourses();
        $html = ViewHelper::renderDropdownList($courses, 'course_id', 'course_name');
        return $html;
    }
    
    /**
     * getAllCourseMasterData
     * 概要：全コースの「科目・担当教員・教室」のマスタデータを取得し、
     * コースIDをキーとした連想配列として返す。
     * 目的：フロントエンド(JS)での動的なドロップダウン生成とオートフィル用
     * * 戻り値の形式:
     * [
     * 1 => [ // コースID
     * [ 'subject_id' => 10, 'subject_name' => 'Java', 'teacher_id' => 5, ... ],
     * [ ... ]
     * ],
     * 2 => [ ... ]
     * ]
     */
    public function getAllCourseMasterData() {
        $masterData = [];

        try {
            // リポジトリの取得
            $courseRepo = RepositoryFactory::getCourseRepository();
            $sicRepo = RepositoryFactory::getSubjectInChargesRepository();

            // 1. 全コースを取得
            // (getAllTimetableDataですでに取得していればプロパティを使っても良いですが、念のため再取得またはキャッシュ利用)
            if (empty($this->courses)) {
                $this->courses = $courseRepo->getAllCourses();
            }

            // 2. コースごとに定義データを取得して格納
            foreach ($this->courses as $course) {
                $courseId = $course['course_id'];
                
                // SubjectInChargesRepositoryを使ってデータを取得
                // ※学年(grade)による絞り込みが必要な場合は、ここでロジックを追加します
                $definitions = $sicRepo->getSubjectDefinitionsByCourse($courseId);

                // コースIDをキーにして格納
                // データがない場合でも対応できるように空配列として処理する
                $masterData[$courseId] = $definitions;
            }

        } catch (Exception $e) {
            error_log("TimetableService::getAllCourseMasterData Error: " . $e->getMessage());
            return [];
        }

        return $masterData;
    }
}
