<?php
// StudentHomeService.php
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

class StudentHomeService {
    
    /**
     * 初期表示用データ（コース一覧）の取得
     */
    public function getInitialData() {
        try {
            $courseRepo = RepositoryFactory::getCourseRepository();
            return $courseRepo->getAllCourses();
        } catch (Exception $e) {
            error_log("Error in getInitialData: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 表示対象の日付を決定するロジック
     */
    public function determineDisplayDate($postDate) {
        if (!empty($postDate)) {
            return new DateTime($postDate);
        }

        $current_time = new DateTime();
        $end_time_threshold = new DateTime(date('Y-m-d') . ' 16:20:00');
        $display_date = clone $current_time;
        
        $day_map = [0 => '日', 1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金', 6 => '土'];
        $current_day_jp = $day_map[(int)$display_date->format('w')];

        // 平日の16:20以降、または土日の場合の翌営業日判定
        if (in_array($current_day_jp, ['月', '火', '水', '木', '金'])) {
            if ($current_time >= $end_time_threshold) {
                $display_date->modify('+1 day');
            }
        }
        
        // 土日をスキップして月曜にする処理
        while (in_array((int)$display_date->format('w'), [0, 6])) {
            $display_date->modify('+1 day');
        }

        return $display_date;
    }

    /**
     * 指定された条件で時間割を取得し、時限をキーにした配列に整形する
     */
    public function getTimeTable($courseId, $dayJp) {
        $day_to_num = ['月' => 1, '火' => 2, '水' => 3, '木' => 4, '金' => 5, '土' => 6, '日' => 7];
        $target_day_num = $day_to_num[$dayJp] ?? 1;

        // リポジトリ経由でのデータ取得に変更
        $repo = RepositoryFactory::getTimeTableDetailsRepository();
        $rows = $repo->findByDayAndTimetableId($target_day_num, $courseId);

        // Serviceの役割：取得したデータをViewが使いやすい形に加工（ビジネスロジック）
        $schedule = [];
        foreach ($rows as $row) {
            $schedule[$row['period']] = $row;
        }
        return $schedule;
    }
}