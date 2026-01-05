<?php
// TeacherHomeController.php
// 教員ホーム画面のコントローラー
require_once __DIR__ . '/../../../classes/repository/home/HomeRepository.php';

class MasterHomeController extends HomeRepository {
    // HomeRepositoryの__constructを呼び出す
    public function __construct() {
        parent::__construct();
    }

    /**
     * 管理者ホーム画面の初期表示データを取得する
     * @return array ビューに渡すデータの配列
     */
    public function html_links() {
        // 遷移先ファイルの定義（クラスに渡すため配列化）
        $links = [
            'link_time_table_edit'   => "timetable_change/edit_timetable_control.php",
            'link_notification_edit' => "notification_edit.php",
            'link_subject_edit'      => "subject_edit.php",
            'link_time_table_view'   => "time_table_view.php",
            'link_send_setting'      => "send_setting.php"
        ];

        return $links;
    }
}