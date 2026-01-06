<?php
// MasterHomeController.php
// 管理者（マスター）ホーム画面のコントローラー

require_once __DIR__ . '/../../../classes/repository/home/HomeRepository.php';

class MasterHomeController extends HomeRepository {
    // HomeRepositoryの__constructを呼び出す
    public function __construct() {
        parent::session_resetting();
    }

    /**
     * 管理者ホーム画面の初期表示データを取得する
     * @return array ビューに渡すデータの配列
     */
    public function html_links() {
        // 遷移先ファイルの定義（クラスに渡すため配列化）
        $links = [
            // 時間割り作成へのリンク
            'link_time_table_create' => "../master/timetable_create/create_timetable.php",
            // 時間割り変更へのリンク
            'link_time_table_edit'   => "timetable_change/edit_timetable_control.php",
            // アカウント編集へのリンク
            'link_account_edit'      => "../master/user-round/user-round.html",
            // 授業科目編集へのリンク
            'link_notification_edit' => "notification_edit.php",
            // 授業詳細編集へのリンク
            'link_subject_edit'      => "subject_edit.php",
            // 時間割り閲覧へのリンク
            'link_time_table_view'   => "time_table_view.php",
        ];

        return $links;
    }
}