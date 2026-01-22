<?php
// StudentHomeService.php
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

class StudentHomeService {
    public const DAY_MAP_FULL = [0 => '日', 1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金', 6 => '土'];
    public const TIME_SCHEDULE = [
        1 => '9:10 ～ 10:40', 2 => '10:50 ～ 12:20', 3 => '13:10 ～ 14:40',
        4 => '14:50 ～ 16:20', 5 => '16:30 ～ 18:00', 6 => '18:10 ～ 19:40',
    ];

    /**
     * ダッシュボード表示に必要な全てのデータを一括で取得・整形する
     */
    public function getDashboardData($courseId, $targetDateStr) {
        // 1. コース一覧
        $courseRepo = RepositoryFactory::getCourseRepository();
        $courses = $courseRepo->getAllCourses();
        $course_labels = array_column($courses, 'course_name', 'course_id');

        // 2. 日付関連
        $date_obj = $this->determineDisplayDate($targetDateStr);
        $w = (int)$date_obj->format('w');
        $day_jp = self::DAY_MAP_FULL[$w];

        // 3. 時間割
        $schedule = $this->getTimeTable($courseId, $day_jp);

        return [
            'course_labels'       => $course_labels,
            'selected_course_id'  => $courseId,
            'course_label'        => $course_labels[$courseId] ?? 'コース不明',
            'today_date_value'    => $date_obj->format('Y-m-d'),
            'formatted_full_date' => $date_obj->format('Y/n/j') . " ($day_jp)",
            'schedule_by_period'  => $schedule,
            'time_schedule'       => self::TIME_SCHEDULE
        ];
    }

    private function determineDisplayDate($postDate) {
        if (!empty($postDate)) return new DateTime($postDate);

        $current = new DateTime();
        $threshold = new DateTime(date('Y-m-d') . ' 16:20:00');
        $display = clone $current;
        
        // 16:20以降なら翌日へ
        if ($current >= $threshold && (int)$display->format('w') >= 1 && (int)$display->format('w') <= 5) {
            $display->modify('+1 day');
        }
        // 土日は月曜へ飛ばす
        while (in_array((int)$display->format('w'), [0, 6])) {
            $display->modify('+1 day');
        }
        return $display;
    }

    private function getTimeTable($courseId, $dayJp) {
        $day_to_num = ['月' => 1, '火' => 2, '水' => 3, '木' => 4, '金' => 5, '土' => 6, '日' => 7];
        $target_day_num = $day_to_num[$dayJp] ?? 1;

        $repo = RepositoryFactory::getTimeTableDetailsRepository();
        $rows = $repo->findByDayAndTimetableId($target_day_num, $courseId);

        return array_column($rows, null, 'period');
    }
}