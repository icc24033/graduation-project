<?php
// StudentHomeController.php
require_once __DIR__ . '/../../../services/student/StudentHomeService.php';
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';
require_once __DIR__ . '/../../../classes/login/student_login_class.php';

class StudentHomeController {
    private $service;
    private $studentCourseId;

    public function __construct() {
        // セッションがまだ開始されていなければ開始する（安全策）
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->service = new StudentHomeService();
        
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
        // 入力値の受け取り
        $courseId  = $this->studentCourseId->getCourseId();

        $courseId = (int)($_POST['selected_course'] ?? $courseId);
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