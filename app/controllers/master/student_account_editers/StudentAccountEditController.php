<?php
// StudentAccountEditController.php
require_once __DIR__ . '/../../../services/master/StudentAccountService.php';

// RepositoryFactoryの読み込み
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';

class StudentAccountEditController {
    private $service;

    public function __construct() {
        $this->service = new StudentAccountService();
    }

    /**
     * 学生追加画面（index_addition）
     */
    public function index_addition() {

        // 5月なら卒業生を削除しているか確認する処理を追加
        if (date('n') == 5) {
            $this->service->deleteGraduatedStudents();
        }

        $viewData = $this->service->getEditData();
        $gradeData = $this->service->getGradeData();
        $basic_data = $this->service->getAdditionBasicInfo($_GET['backend'] ?? null);
        
        RepositoryFactory::closePdo();
        
        extract($viewData);
        extract($basic_data);
        extract($gradeData);
        require_once '../student_addition.php';
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

    /**
     * 学年移動画面
     */
    public function index_transfer($course_id, $year) {
        $viewData = $this->service->getEditData();
        $gradeData = $this->service->getGradeData();
        $basic_data = $this->service->getStudentsInCourse($course_id, $year);
        
        RepositoryFactory::closePdo();

        extract($viewData);
        extract($basic_data);
        extract($gradeData);
        require_once '../student_grade_transfer.php';
    }

    /**
     * 学生削除画面
     */
    public function index_delete($course_id, $year) {
        $viewData = $this->service->getEditData();
        $gradeData = $this->service->getGradeData();
        $basic_data = $this->service->getStudentsInCourse($course_id, $year);
        
        RepositoryFactory::closePdo();

        extract($viewData);
        extract($basic_data);
        extract($gradeData);
        require_once '../student_delete.php';
    }
}