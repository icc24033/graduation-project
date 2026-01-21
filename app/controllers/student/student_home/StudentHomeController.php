<?php
// StudentHomeController.php
require_once __DIR__ . '/../../../services/student/StudentHomeService.php';

// RepositoryFactoryの読み込み
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';

class StudentHomeController {
    private $service;

    public function __construct() {
        $this->service = new StudentHomeService();
    }

    /**
     * 生徒ホーム画面
     */
    public function index() {
        
        $courseList = $this->service->getEditData();

        RepositoryFactory::closePdo();

        extract($courseList);

        require_once '../student_home.php';
    }
}