<?php
// StudentAccountEditController.php
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';

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

    public function basic_info() {
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

}