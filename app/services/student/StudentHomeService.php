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
}
        