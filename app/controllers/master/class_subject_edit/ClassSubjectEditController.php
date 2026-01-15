<?php
// ClassSubjectEditController.php

// サービスの読み込み
require_once __DIR__ . '/../../../services/master/ClassSubjectEditService.php';

// RepositoryFactoryの読み込み
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';

// 授業科目編集コントローラー
class ClassSubjectEditController {
    private $service;

    public function __construct() {
        $this->service = new ClassSubjectEditService();
    }

    /**
     * 授業科目追加画面
     */
    public function index_addition() {

        require_once '../tuika.php';
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
}