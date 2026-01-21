<?php
// StudentHomeController.php
require_once __DIR__ . '/../../../services/student/StudentHomeService.php';
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';

class StudentHomeController {
    private $service;

    public function __construct() {
        $this->service = new StudentHomeService();
    }

    public function index() {
        // 1. コース一覧の取得とラベル化
        $courseList = $this->service->getInitialData();
        $course_labels = [];
        foreach ($courseList as $course) {
            $course_labels[$course['course_id']] = $course['course_name'];
        }

        // 2. 選択状態の取得
        $selected_course = isset($_POST['selected_course']) ? (int)$_POST['selected_course'] : 1;
        $course_label = $course_labels[$selected_course] ?? 'コース不明';

        // 3. 日付の決定
        $display_date_obj = $this->service->determineDisplayDate($_POST['search_date'] ?? null);
        
        $day_map_full = [0 => '日', 1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金', 6 => '土'];
        $display_day_jp = $day_map_full[(int)$display_date_obj->format('w')];
        $today_date_value = $display_date_obj->format('Y-m-d');
        $formatted_full_date = $display_date_obj->format('Y/n/j') . " (" . $display_day_jp . ")";

        // 4. 時間割データの取得
        $schedule_by_period = $this->service->getTimeTable($selected_course, $display_day_jp);

        // 定数定義
        $time_schedule = [
            1 => '9:10 ～ 10:40', 2 => '10:50 ～ 12:20', 3 => '13:10 ～ 14:40',
            4 => '14:50 ～ 16:20', 5 => '16:30 ～ 18:00', 6 => '18:10 ～ 19:40',
        ];

        RepositoryFactory::closePdo();

        // Viewの読み込み (変数が展開されて渡される)
        require_once '../student_home.php';
    }
}