<?php
// TeacherAccountService.php
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

class TeacherAccountService {

    /**
     * 教員リストの取得
     * @return array 教員情報の配列とエラーメッセージ
     */
    public function getTeachers() {
        $data = ['teacherList' => [], 'error_message' => ''];
        try {
            $teacherRepo = RepositoryFactory::getTeacherRepository();
            $data['teacherList'] = $teacherRepo->getAllTeachers();
        } catch (Exception $e) {
            error_log("TeacherAccountService Error (getTeachers): " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }
        return $data;
    }
}
        