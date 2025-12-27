<?php
// HomeRepository.php
// ホーム画面に関するデータ操作を担当するリポジトリクラス
// MasterとTeacherで共通する処理を記述し、両方のホームコントローラーで継承される

class HomeRepository {

    public function __construct() {
        // コンストラクタ（必要に応じて初期化処理を追加）
        $session_duration = 604800; // 7日間 (秒単位: 7 * 24 * 60 * 60)

        // 0.1. サーバー側GCの有効期限を設定
        ini_set('session.gc_maxlifetime', $session_duration);

        // 0.2. クライアント側（ブラウザ）のCookie有効期限を設定
        // 'lifetime' に $session_duration を設定することで、7日間はログイン状態を保持する
        // secure => true: 本番環境で HTTPS でのみCookieを送信
        // httponly => true: JavaScriptからのアクセスを禁止
        session_set_cookie_params([
            'lifetime' => $session_duration,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // HTTPSならtrue
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
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

        $data['user_grade'] = $_SESSION['user_grade'] ?? 'student'; 
        $data['current_user_id'] = $_SESSION['user_id'] ?? '';
        $data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
        $data['smartcampus_picture'] = 'images/smartcampus.png'; // ICCスマートキャンパスのロゴ画像パス
        
        return $data;
    }

    /*
    * Userインスタンスを生成するメソッド
    * @param string $user_grade ユーザーの権限レベル
    */
    public function create_user_instance($user_grade) {
        $base_path = __DIR__ . '/../user/';
        require_once $base_path . 'User_class.php'; 

        switch ($user_grade) {
            case 'teacher':
                require_once $base_path . 'Teacher_class.php'; 
                return new Teacher($current_user_id);
            case 'master':
                require_once $base_path . 'Master_class.php'; 
                return new Master($current_user_id);
            default:
                return;
        }
    }

    /*
    * Userインスタンスをもとに関数カードのHTMLを生成するメソッド
    * @param object|null $user_instance Userクラスまたはそのサブクラスのインスタンス
    * @param array $links 遷移先ファイルの定義配列
    */
    public function generate_function_cards_html($user_instance, $links) {
        if (!$user_instance) {
            return '';
        }
        return $user_instance->generateFunctionCardsHtml($links);
    }
}