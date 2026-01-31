<?php
// MasterHomeController.php
// 管理者（マスター）ホーム画面のコントローラー

require_once __DIR__ . '/../../../classes/repository/home/HomeRepository.php';
require_once __DIR__ . '/../../../classes/security/SecurityHelper.php';
require_once __DIR__ . '/../../../services/master/timetable_create/TimetableService.php';

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
            'link_time_table_create' => "../master/timetable_create/create_timetable_control.php",
            // 時間割り変更へのリンク
            'link_time_table_edit'   => "../teacher/timetable_change/timetable_change_control.php",
            // アカウント編集へのリンク
            'link_account_edit'      => "../master/user-round/user-round.html",
            // 授業科目編集へのリンク
            'link_notification_edit' => "../master/class_subject_edit/controls/addition_control.php",
            // 授業詳細編集へのリンク
            //'link_subject_edit'      => "../teacher/class_detail_edit/class_detail_edit_control.php",
            'link_subject_edit'      => "../teacher/class_detail_edit/class_detail_edit_control.php",
            // 時間割り閲覧へのリンク
            'link_time_table_view'   => "../teacher/timetable_view/timetable_view_control.php",
        ];

        return $links;
    }

    /**
     * メイン処理
     */
    public function index() {
        // 1. ログインチェック
        SecurityHelper::requireLogin();
        SecurityHelper::applySecureHeaders();

        // 2. データの取得
        $user_data = $this->getHomeDataByUserdate();

        // ユーザーインスタンス生成
        $user_instance = $this->create_user_instance($user_data['user_grade'], $user_data['current_user_id']);

        // 時間割りサービスのインスタンス化
        $timetableService = new TimetableService();
        // 時間割りデータのstatusType更新
        $timetableService->updateTimetableStatusTypeForAllCourses();

        // 3. 権限チェックと表示準備
        // Masterクラスのインスタンスかチェック（instanceof を使うとより確実です）
        if ($user_instance !== null && is_a($user_instance, 'Master')) {
            
            // リンク情報の取得
            $links = $this->html_links();

            
            // ユーザーアイコン表示用
            $data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
            extract($data);

            $smartcampus_picture = '../images/smartcampus.png';

            // 関数カード（HTMLパーツ）の生成
            $function_cards_html = $this->generate_function_cards_html($user_instance, $links);

            // 4. Viewに変数を渡す
            extract($links);
            extract($user_data);

            SecurityHelper::setTransitionToken('from_home_to_create_timetable');

            // 5. Viewの読み込み
            // パスはコントローラーからの相対パスになるので注意
            require_once __DIR__ . '/../../../../public/master/master_home.php';

        } else {
            // 権限がない、またはユーザーが取得できない場合
            // ログインエラー画面などへ
            require_once __DIR__ . '/../../../../public/login/login_error.html';
            exit();
        }
    }
}