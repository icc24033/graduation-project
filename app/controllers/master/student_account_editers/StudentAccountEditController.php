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

    public function student_addittion_basic_info() {
        $student_count_sql = ("SELECT COUNT(*)  FROM student WHERE LEFT(student_id, 2) = ?;");
        $data = [
            'success' => true,
            'backend' => 'student_addition',
            'error_csv' => false,
            'before' => 'teacher_home',
            'student_count_sql' => $student_count_sql
        ];
        return $data;
    }

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

        $student_sql = ("SELECT 
                            S.student_id,
                            S.student_name,
                            S.course_id,
                            S.grade,
                            C.course_name 
                        FROM
                            student AS S 
                        INNER JOIN 
                            course AS C 
                        ON 
                            S.course_id = C.course_id 
                        WHERE 
                            S.course_id = ?;"
                        );
        $data = [
            'success' => true,
            'before' => 'teacher_home',
            'student_sql' => $student_sql,
            'course_id' => $course_id,
            'current_year' => $current_year
        ];
        
    return $data;
    }

}