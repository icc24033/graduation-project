<?php
// StudentHomeService.php
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

class StudentHomeService {
    
    /**
     * コースリストの取得
     */
    public function getEditData() {
        $data = ['courseList' => [], 'error_message' => ''];
        try {
            $courseRepo = RepositoryFactory::getCourseRepository();
            $data['courseList'] = $courseRepo->getAllCourses();
        } catch (Exception $e) {
            error_log("StudentAccountService Error (getEditData): " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }
        return $data;
    }

    /**
     * 先生追加画面の基本情報取得
     */
    public function getAdditionBasicInfo($backend) {
        
        // data送信に失敗した場合の初期値設定
        $csv_count_flg = false;
        $csv_data = [];
        $error_count_flg = false;
        $error_data = [];
        $errorTeacherRepo = RepositoryFactory::getErrorTeacherTableRepository();
        
        if (empty($backend)) {
            $backend = 'teacher_addition';
            // error_teacher_tableの
            $errorTeacherRepo->createErrorTeacherTable();
        }

        if ($backend === 'csv_upload') {
            // 一時テーブルからデータを取得
            $tempTeacherRepo = RepositoryFactory::getTempTeacherCsvRepository();
            if ($tempTeacherRepo->hasTempTeachers()) {
                $csv_count_flg = true;
                $csv_data = $tempTeacherRepo->getAllTempTeachers();
            }

            // エラーテーブルからデータを取得
            if ($errorTeacherRepo->hasErrorTeachers()) {
                $error_count_flg = true;
                $error_data = $errorTeacherRepo->getAllErrorTeachers();
            }
        }

        return [
            'backend' => $backend,
            'csv_count_flg' => $csv_count_flg,
            'csv_data' => $csv_data,
            'error_count_flg' => $error_count_flg,
            'error_data' => $error_data
        ];
    }
}
        