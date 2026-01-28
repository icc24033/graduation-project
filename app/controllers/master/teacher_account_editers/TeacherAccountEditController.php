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

        $user_picture = $_SESSION['user_picture'] ?? 'images/default_icon.png';

        extract(['user_picture' => $user_picture]);
        extract($viewData);

        require_once '../master.php';

         // ユーザーアイコン表示用
         $data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
         extract($data);
 
         $smartcampus_picture = '../images/smartcampus.png';
    }

    /**
     * 教員削除処理
     */
    public function index_delete() {

        $viewData = $this->service->getTeachers();
        RepositoryFactory::closePdo();

        $user_picture = $_SESSION['user_picture'] ?? 'images/default_icon.png';

        extract(['user_picture' => $user_picture]);

        extract($viewData);
        require_once '../teacher_delete.php';

         // ユーザーアイコン表示用
         $data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
         extract($data);
 
         $smartcampus_picture = '../images/smartcampus.png';
    }

    /**
     * 教員追加画面
     */
    public function index_addition() {
        $basicInfo = $this->service->getAdditionBasicInfo($_GET['backend'] ?? '');

        $user_picture = $_SESSION['user_picture'] ?? 'images/default_icon.png';

        extract(['user_picture' => $user_picture]);
        
        extract($basicInfo);

        require_once '../teacher_addition.php';

         // ユーザーアイコン表示用
         $data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
         extract($data);
 
         $smartcampus_picture = '../images/smartcampus.png';
    }   

    /**
     * 教員情報編集画面
     */
    public function index_information() {

        $viewData = $this->service->getTeachers();
        RepositoryFactory::closePdo();

        $user_picture = $_SESSION['user_picture'] ?? 'images/default_icon.png';

        extract(['user_picture' => $user_picture]);
        
        extract($viewData);
        require_once '../teacher_Information.php';

         // ユーザーアイコン表示用
         $data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
         extract($data);
 
         $smartcampus_picture = '../images/smartcampus.png';
    }
}