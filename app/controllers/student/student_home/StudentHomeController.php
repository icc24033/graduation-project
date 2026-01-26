<?php
// StudentHomeController.php
require_once __DIR__ . '/../../../services/student/StudentHomeService.php';
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';
require_once __DIR__ . '/../../../classes/login/student_login_class.php';
require_once __DIR__ . '/../../../services/master/timetable_create/TimeTableService.php';

class StudentHomeController {
    private $service;
    private $serviceTimeTable;
    private $studentCourseId;

    public function __construct() {
        // セッションがまだ開始されていなければ開始する（安全策）
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->service = new StudentHomeService();
        $this->serviceTimeTable = new TimeTableService();
        
        // セッションに値があるかチェックしてからインスタンスを作る（エラー回避）
        if (isset($_SESSION['user_id'], $_SESSION['user_grade'], $_SESSION['user_course'])) {
            // $this->studentCourseId に代入しないと後で使えません
            $this->studentCourseId = new StudentLogin(
                (string)$_SESSION['user_id'],     // student_id ではなく user_id
                (string)$_SESSION['user_grade'],  // grade ではなく user_grade
                (string)$_SESSION['user_course']  // course_id ではなく user_course
            );
        } else {
            // セッションがない場合はログイン画面に飛ばすなどの処理が必要
            header('Location: ../login/login_control.php'); 
            exit;
        }
    }

    public function index() {
        $sessionCourseId = $this->studentCourseId->getCourseId();
    
        // POSTがあればそれを、なければセッションのIDを使う
        $courseId = (int)($_POST['selected_course'] ?? $sessionCourseId);
        $dateStr  = $_POST['search_date'] ?? null;
    
        $viewData = $this->service->getDashboardData($courseId, $dateStr);
    
        // ビュー側の変数名 $selected_course に合わせるためにキーを調整
        $viewData['selected_course'] = $viewData['selected_course_id'];
    
        $testdata = $this->serviceTimeTable->ChangeConsideringAllTimetables();
        // 変数 $testdata をビューで使えるようにする    
        extract($testdata);


        extract($viewData);
    
        RepositoryFactory::closePdo();
        require_once '../student_home.php';
    }
}