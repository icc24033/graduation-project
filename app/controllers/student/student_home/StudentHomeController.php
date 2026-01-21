<?php
// StudentHomeController.php
require_once __DIR__ . '/../../../services/student/StudentHomeService.php';
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';

class StudentHomeController {
    private $service;

    public function __construct() {
        $this->service = new StudentHomeService();
    }

    public function index() {
        // 入力値の受け取り
        $courseId = (int)($_POST['selected_course'] ?? 1);
        $dateStr  = $_POST['search_date'] ?? null;

        // サービスから表示用データを一括取得
        $viewData = $this->service->getDashboardData($courseId, $dateStr);

        // Viewで使いやすいように変数を展開（extract）
        // これにより $viewData['course_label'] が $course_label として参照可能になります
        extract($viewData);

        RepositoryFactory::closePdo();
        require_once '../student_home.php';
    }
}