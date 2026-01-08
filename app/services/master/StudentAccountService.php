<?php
// StudentAccountService.php
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

class StudentAccountService {

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
     * 学年リストの取得
     */
    public function getGradeData() {
        $data = ['gradeList' => [], 'error_message' => ''];
        try {
            $studentGradeRepo = RepositoryFactory::getStudentGradeRepository();
            $data['gradeList'] = $studentGradeRepo->getAllGrades();
        } catch (Exception $e) {
            error_log("StudentAccountService Error (getGradeData): " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }
        return $data;
    }

    /**
     * 学生追加画面の基本情報取得
     */
    public function getAdditionBasicInfo($backend) {
        
        // data送信に必要な変数を初期化
        $student_count = 0; 
        $csv_count = 0; 
        $csv_count_flag = false;
        $csv_data = []; 
        $error_count = 0; 
        $error_count_flag = false; 
        $error_data = [];

        if (empty($backend)) {
            $backend = 'student_addition';
            $errorStudentRepo = RepositoryFactory::getErrorStudentRepository();
            // 注意: エラーが出ていた箇所。リポジトリにこのメソッドがあるか確認してください
            if (method_exists($errorStudentRepo, 'createErrorDataTable')) {
                $errorStudentRepo->createErrorDataTable();
            }
        }

        $errorStudentRepo = RepositoryFactory::getErrorStudentRepository();
        $error_count = $errorStudentRepo->countErrorData();
        if ($error_count > 0) {
            $error_count_flag = true;
            $error_data = $errorStudentRepo->getAllErrorData();
        }

        if ($backend === 'student_addition') {
            $studentRepo = RepositoryFactory::getStudentRepository();
            $student_count = $studentRepo->countStudentsByYear();
        } else if ($backend === 'csv_upload') {
            $csvRepo = RepositoryFactory::getCsvRepository();
            $csv_count = $csvRepo->countCsvData();
            if ($csv_count > 0) {
                $csv_count_flag = true;
                $csv_data = $csvRepo->getAllCsvData();
            }
        }

        return [
            'success' => true,
            'backend' => $backend,
            'error_csv' => $error_count_flag,
            'error_data' => $error_data,
            'before' => 'teacher_home',
            'success_csv' => $csv_count_flag,
            'csv_data' => $csv_data,
            'student_count' => $student_count
        ];
    }

    /**
     * 各種画面共通の学生リスト取得ロジック
     */
    public function getStudentsInCourse($received_course_id, $received_current_year) {
        // empty() は 0 を真と判定してしまうため、明示的に null と空文字をチェックする
        if (($received_course_id === null || $received_current_year === null)) {
            $course_id = 1;
            $current_year = 2;
        } else {
            $course_id = $received_course_id;
            $current_year = $received_current_year;
        }
    
        $studentRepo = RepositoryFactory::getStudentRepository();
        $students_in_course = $studentRepo->getStudentsByCourse($course_id);
    
        return [
            'success' => true,
            'before' => 'teacher_home',
            'course_id' => $course_id,
            'current_year' => $current_year,
            'students_in_course' => $students_in_course
        ];
    }

    
        
}