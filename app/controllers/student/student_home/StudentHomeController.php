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

    // StudentHomeController.php
    public function index() {
        // --- 修正: POST または GET から値を取得するように変更 ---
        $sessionCourseId = $this->studentCourseId->getCourseId();
        
        // コースIDの取得 (優先順位: POST > GET > セッション)
        $courseId = (int)($_POST['selected_course'] ?? $_GET['course_id'] ?? $sessionCourseId);
        
        // 日付の取得 (優先順位: POST > GET > 今日)
        $date = $_POST['search_date'] ?? $_GET['date'] ?? date('Y-m-d');
        // -----------------------------------------------------

        // 1. データの取得 (メソッド名は getDashboardData)
        $data = $this->service->getDashboardData($courseId, $date);

        // ※デバッグ用：取得した日付を上書きして確認する場合
        // $date = $data['today_date_value']; 

        // 2. $testdata の内容（月曜日のテストデータ）
        $testdata_data = [
            ["day" => "月", "period" => 1, "className" => "データ通信", "teacherName" => "永田・山本", "roomName" => "プ実1・総合実習室"],
            ["day" => "月", "period" => 2, "className" => "卒業研究", "teacherName" => "永田", "roomName" => "シス2"],
            ["day" => "月", "period" => 3, "className" => "卒業研究", "teacherName" => "永田", "roomName" => "シス2"],
            ["day" => "月", "period" => 4, "className" => "卒業研究", "teacherName" => "永田", "roomName" => "シス2"]
        ];

        // 3. schedule_by_period を上書き
        $mockSchedule = [];
        $dayOfWeekKanji = ["日", "月", "火", "水", "木", "金", "土"][(int)date('w', strtotime($date))];

        foreach ($testdata_data as $item) {
            if ($item['day'] === $dayOfWeekKanji) {
                $mockSchedule[$item['period']] = [
                    'subject_name' => $item['className'],
                    'teacher_name' => $item['teacherName'],
                    'room_name'    => $item['roomName']
                ];
            }
        }

        // 表示データのセット
        $data['schedule_by_period'] = $mockSchedule;
        $viewData = $data;
        $viewData['selected_course'] = $courseId; // 選択状態を保持するために追加

        $testdata = $this->serviceTimeTable->ChangeConsideringAllTimetables();
        // 変数 $testdata をビューで使えるようにする    
        extract($testdata);

        // 変数を展開してビューを読み込む
        extract($viewData);
        require_once '../student_home.php';
    }
}