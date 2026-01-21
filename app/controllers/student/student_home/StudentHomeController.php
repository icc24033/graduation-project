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
        /*
        $viewData = $this->service->getHomeData();
        
        RepositoryFactory::closePdo();

        extract($viewData);
        */
        require_once '../student_home.php';
    }

    /**
     * コース編集画面
     */
    public function index_course($course_id, $year) {
        $viewData = $this->service->getEditData();
        $gradeData = $this->service->getGradeData();
        $basic_data = $this->service->getStudentsInCourse($course_id, $year);
        
        RepositoryFactory::closePdo();

        extract($viewData);
        extract($basic_data);
        extract($gradeData);
        require_once '../student_edit_course.php';
    }
}