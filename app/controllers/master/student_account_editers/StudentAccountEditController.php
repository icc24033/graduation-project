<?php
// StudentAccountEditController.php
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';    // データベース接続

class StudentAccountEditController {

    /**
     * 学生アカウント編集画面の初期表示データを取得する
     * @return array ビューに渡すデータの配列
     */
    public function edit() {
        $data = [
            // 'studentList' => [],
            'courseList' => [],
            'error_message' => ''
        ];

        try {
            // リポジトリからデータを取得
            // $studentRepo = RepositoryFactory::getStudentRepository();
            // $data['studentList'] = $studentRepo->getAllStudents();
            
            $courseRepo = RepositoryFactory::getCourseRepository();
            $data['courseList'] = $courseRepo->getAllCourses();

            // データ取得後にDB接続を閉じる
            RepositoryFactory::closePdo();

        } catch (Exception $e) {
            error_log("StudentAccountEditController Error: " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }

        return $data;
    }

    /**
     * 学生追加画面の基本情報を取得する
     * @return array 基本情報の配列
     */
    public function student_addittion_basic_info($backend) {
        
        // data送信に必要な変数を初期化
        $student_count = 0;
        $csv_count = 0;
        $csv_count_flag = false;
        $csv_data = [];
        $error_count = 0;
        $error_count_flag = false;
        $error_data = [];

        if (empty($backend)) {
            $backend = 'student_addition'; // デフォルト値を設定
        }

        if ($backend === 'student_addition') {
            $studentRepo = RepositoryFactory::getStudentRepository();
            $student_count = $studentRepo->countStudentsByYear();
        }
        else if ($backend === 'csv_upload') {
            $csvRepo = RepositoryFactory::getCsvRepository();
            $csv_count = $csvRepo->countCsvData(); 

            $errorStudentRepo = RepositoryFactory::getErrorStudentRepository();
            $error_count = $errorStudentRepo->countErrorData();

            // csv_tableにデータが存在した場合の処理
            if ($csv_count > 0) {
                $csv_count_flag = true;
                $csv_data = $csvRepo->getAllCsvData();
            }
            else {
                $csv_count_flag = false;
                $csv_data = [];
            }

            // error_student_tableにデータが存在した場合の処理
            if ($error_count > 0) {
                $error_count_flag = true;
                $error_data = $errorStudentRepo->getAllErrorData();
            }
            else {
                $error_count_flag = false;
                $error_data = [];
            }
        }
                


            

        $data = [
            'success' => true,
            'backend' => $backend,
            'error_csv' => $error_count_flag,
            'error_data' => $error_data,
            'before' => 'teacher_home',
            'success_csv' => $csv_count_flag,
            'csv_data' => $csv_data,
            'student_count' => $student_count
        ];
        return $data;
    }

    /**
     * 学生削除画面の基本情報を取得する
     * @return array 基本情報の配列
     */
    public function student_delete_basic_info($received_course_id, $received_current_year) {

        if (empty($received_course_id) || empty($received_current_year)) {
            $course_id = 1; // デフォルト値を設定
            $current_year = date("Y");
            $current_year = substr($current_year, -2);
        }
        else {
            $course_id = $received_course_id;
            $current_year = $received_current_year;
        }

        $studentRepo = RepositoryFactory::getStudentRepository();
        $students_in_course = $studentRepo->getStudentsByCourse($course_id);

        $data = [
            'success' => true,
            'before' => 'teacher_home',
            'course_id' => $course_id,
            'current_year' => $current_year,
            'students_in_course' => $students_in_course
        ];

    return $data;
    }

    /**
     * コース編集画面の基本情報を取得する
     * @return array 基本情報の配列
     */
    public function student_course_basic_info($received_course_id, $received_current_year) {
        
        if (empty($received_course_id) || empty($received_current_year)) {
            $course_id = 1; // デフォルト値を設定
            $current_year = date("Y");
            $current_year = substr($current_year, -2);
        }
        else {
            $course_id = $received_course_id;
            $current_year = $received_current_year;
        }

        $studentRepo = RepositoryFactory::getStudentRepository();
        $students_in_course = $studentRepo->getStudentsByCourse($course_id);

        $data = [
            'success' => true,
            'before' => 'teacher_home',
            'course_id' => $course_id,
            'current_year' => $current_year,
            'students_in_course' => $students_in_course
        ];
    
    return $data;
    }

    /**
     * 学年移動画面の基本情報を取得する
     * @return array 基本情報の配列
     */
    public function student_grade_basic_info($received_course_id, $received_current_year) {
            
        if (empty($received_course_id) || empty($received_current_year)) {
            $course_id = 1; // デフォルト値を設定
            $current_year = date("Y");
            $current_year = substr($current_year, -2);
        }
        else {
            $course_id = $received_course_id;
            $current_year = $received_current_year;
        }

        $studentRepo = RepositoryFactory::getStudentRepository();
        $students_in_course = $studentRepo->getStudentsByCourse($course_id);

        $data = [
            'success' => true,
            'before' => 'teacher_home',
            'course_id' => $course_id,
            'current_year' => $current_year,
            'students_in_course' => $students_in_course
        ];
    
    return $data;
    }
}