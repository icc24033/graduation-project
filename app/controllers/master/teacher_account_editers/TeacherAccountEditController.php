<?php
// TeacherAccountEditController.php
require_once __DIR__ . '/../../../services/master/TeacherAccountService.php';

// RepositoryFactoryの読み込み
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';

// 教員アカウント編集コントローラー
class TeacherAccountEditController {
    private $service;

    public function __construct() {
        $this->service = new TeacherAccountService();
    }

    /**
     * マスター編集画面（index_master）
     */
    public function index_master() {

        $viewData = $this->service->getTeachers();
        
        RepositoryFactory::closePdo();
        
        extract($viewData);
        require_once '../master.php';
    }

    /**
     * 教員削除処理
     */
    public function index_delete() {

        $viewData = $this->service->getTeachers();
        RepositoryFactory::closePdo();

        extract($viewData);
        require_once '../teacher_delete.php';
    }
}