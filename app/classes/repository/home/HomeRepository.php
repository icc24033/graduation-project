<?php
// HomeRepository.php
// ホーム画面に関するデータ操作を担当するリポジトリクラス
// MasterとTeacherで共通する処理を記述し、両方のホームコントローラーで継承される

abstract class  HomeRepository {

    public static function session_resetting() {
        // 0.1. サーバー側GCの有効期限を設定
        // すでに session_start() が呼ばれている場合は設定できないため、注意が必要
        // セッションが開始されている場合は設定(ini_set('session.gc_maxlifetime', $session_duration);)をスキップする   
        if (session_status() === PHP_SESSION_NONE) {
            $session_duration = 7 * 24 * 60 * 60; // 7日間を秒数で設定
            ini_set('session.gc_maxlifetime', (string)$session_duration);

            // 0.2. クライアント側（ブラウザ）のCookie有効期限を設定
            // 'lifetime' に $session_duration を設定することで、7日間はログイン状態を保持する
            // secure => true: 本番環境で HTTPS でのみCookieを送信
            // httponly => true: JavaScriptからのアクセスを禁止
            // samesite => 'Lax': クロスサイトリクエストフォージェリ（CSRF）対策
            // すでに session_start() が呼ばれている場合は設定できないため、注意が必要
            session_set_cookie_params([
                'lifetime' => $session_duration,
                'path' => '/',
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // HTTPSならtrue
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    }

    /*
    * ユーザーのホーム画面データの固有ステータスを取得するメソッド
    */
    public function getHomeDataByUserdate() {
        // ユーザーの権限レベルと固有IDをセッションから取得
        $data = [
            'user_grade' => '',
            'current_user_id' => '',
            'user_picture' => '',
            'smartcampus_picture' => ''
        ];

        $raw_grade = $_SESSION['user_grade'] ?? 'student';
        $data['user_grade'] = trim($raw_grade);
        $data['current_user_id'] = $_SESSION['user_id'] ?? '';
        $data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
        $data['smartcampus_picture'] = 'images/smartcampus.png'; // ICCスマートキャンパスのロゴ画像パス
        
        return $data;
    }

    /*
    * Userインスタンスを生成するメソッド
    * @param string $user_grade ユーザーの権限レベル
    */
    public function create_user_instance($user_grade, $current_user_id) {
        $base_path = __DIR__ . '/../../user/';
        require_once $base_path . 'User_class.php';

        if ($user_grade === 'master@icc_ac.jp') {
            require_once $base_path . 'Master_class.php';
            return new Master($current_user_id);
        }
        elseif ($user_grade === 'teacher@icc_ac.jp') {
            require_once $base_path . 'Teacher_class.php';
            return new Teacher($current_user_id);
        }
        else {
            return null; // 不明な権限レベルの場合はnullを返す
        }
    }

    /*
    * Userインスタンスをもとに関数カードのHTMLを生成するメソッド
    * @param object|null $user_instance UserクラスまたはそのサブクラスのインスタンスgetFunctionCardsHtml
    * @param array $links 遷移先ファイルの定義配列
    */
    public function generate_function_cards_html($user_instance, $links) {
        if (!$user_instance) {
            return '';
        }
        // UserインスタンスのgetFunctionCardsHtmlメソッドを呼び出してHTMLを取得
        return $user_instance->getFunctionCardsHtml($links);
    }

    // 抽象メソッド
        /**
     * 管理者ホーム画面の初期表示データを取得する
     * @return array ビューに渡すリンク先情報データの配列
     */
    public function html_links() {
        return [];
    }
}