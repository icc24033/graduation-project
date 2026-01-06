<?php
// StudentAccountEditController.php
require_once __DIR__ . '/../../../services/master/StudentAccountService.php';

class StudentAccountEditController {
    private $service;

    public function __construct() {
        $this->service = new StudentAccountService();
    }

    /**
     * 学生追加画面（index_addition）
     */
    public function index_addition() {
        $viewData = $this->service->getEditData();
        $basic_data = $this->service->getAdditionBasicInfo($_GET['backend'] ?? null);
        
        extract($viewData);
        extract($basic_data);
        require_once '../student_addition.php';
    }

    /**
     * コース編集画面
     */
    public function index_course($course_id, $year) {
        $viewData = $this->service->getEditData();
        $basic_data = $this->service->getStudentsInCourse($course_id, $year);

        extract($viewData);
        extract($basic_data);
        require_once '../student_edit_course.php';
    }

    /**
     * 学年移動画面
     */
    public function index_transfer($course_id, $year) {
        $viewData = $this->service->getEditData();
        $basic_data = $this->service->getStudentsInCourse($course_id, $year);

        extract($viewData);
        extract($basic_data);
        require_once '../student_grade_transfer.php';
    }

    /**
     * 学生削除画面
     */
    public function index_delete($course_id, $year) {
        $viewData = $this->service->getEditData();
        $basic_data = $this->service->getStudentsInCourse($course_id, $year);

        extract($viewData);
        extract($basic_data);
        require_once '../student_delete.php';
    }
}