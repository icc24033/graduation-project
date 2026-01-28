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

        $viewData = $this->service->getEditData();
        $gradeData = $this->service->getGradeData();
        $basic_data = $this->service->getAdditionBasicInfo($_GET['backend'] ?? null);
        
        RepositoryFactory::closePdo();
        
        extract($viewData);
        extract($basic_data);
        extract($gradeData);
        require_once '../student_addition.php';

        // ユーザーアイコン表示用
        $data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
        extract($data);

        $smartcampus_picture = '../images/smartcampus.png';

    }
    
    /*
    // ユーザーアイコン表示用
    $data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
    extract($data);
    */

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

        // ユーザーアイコン表示用
        $data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
        extract($data);

        $smartcampus_picture = '../images/smartcampus.png';

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

         // ユーザーアイコン表示用
         $data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
         extract($data);
 
         $smartcampus_picture = '../images/smartcampus.png';
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

         // ユーザーアイコン表示用
         $data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
         extract($data);
 
         $smartcampus_picture = '../images/smartcampus.png';
    }
}