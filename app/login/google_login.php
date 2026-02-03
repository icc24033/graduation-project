<?php
// google_login.php
// GoogleOAuth2.0 を利用したログイン機能を実装するためのフローを取りまとめたコード

// ----------------------------------------------------
// 0. SecurityHelperの読み込み
// ----------------------------------------------------
require_once __DIR__ . '/../classes/security/SecurityHelper.php';

// セキュリティヘッダーの適用
SecurityHelper::applySecureHeaders();

// ----------------------------------------------------
// 1. セッション設定の読み込み
// ----------------------------------------------------
require_once __DIR__ . '/../session/session_config.php'; 

// セッションが開始されていない場合のみ開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------
// 2. 機密情報の読み込み
// ----------------------------------------------------
$config_path = __DIR__ . '/../../config/secrets_local.php';

if (!file_exists($config_path)) {
    die("エラー: 機密情報ファイルが見つかりません。パスを確認してください: " . $config_path);
}

$config = require $config_path;

// クラスファイルの読み込み
require_once __DIR__ . '/../classes/login/LoginUser.php';   // インターフェース
require_once __DIR__ . '/../classes/login/Student_login_class.php';  // 生徒クラス
require_once __DIR__ . '/../classes/login/Teacher_login_class.php';  // 先生クラス
require_once __DIR__ . '/../classes/login/AuthRepository_class.php'; // リポジトリ

// ----------------------------------------------------
// 3. 定数の設定
// ----------------------------------------------------
define('CLIENT_ID', $config['client_id']); 
define('CLIENT_SECRET', $config['client_secret']); 
define('REDIRECT_URI', $config['redirect_uri']);
define('ICC_DOMAIN', $config['icc_domain']); 
define('HOME_URL', $config['home_url']);

// データベース接続情報
define('DB_HOST', $config['db_host']);
define('DB_NAME', $config['db_name']);
define('DB_USER', $config['db_user']);
define('DB_PASS', $config['db_pass']);

// Google API エンドポイント
$scope = 'email profile';
$auth_endpoint = 'https://accounts.google.com/o/oauth2/v2/auth';
$token_endpoint = 'https://oauth2.googleapis.com/token';
$userinfo_endpoint = 'https://openidconnect.googleapis.com/v1/userinfo';


require_once __DIR__ . '/../classes/login/GoogleOAuthService_class.php';

// サービスクラスの初期化
$googleService = new GoogleOAuthService(CLIENT_ID, CLIENT_SECRET, REDIRECT_URI);

/** * ログインエラー時の共通処理関数
 */
function handle_login_error(): void
{
    sleep(3); 
    // セッション破棄
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
    header('Location: login_error.html');
    exit(); 
}


// -------------------------------------------------------------------------
// 【A. コールバック処理：認可コードを受け取った後の処理】
// -------------------------------------------------------------------------

if (isset($_GET['code'])) {

    // 0. CSRF対策の実装
    $session_state = $_SESSION['oauth_state'] ?? '';
    $returned_state = $_GET['state'] ?? '';

    // セッション内のstateを削除（使い捨て）
    unset($_SESSION['oauth_state']);

    if (empty($session_state) || $session_state !== $returned_state) {
        error_log("CSRF Error: Invalid state parameter");
        handle_login_error();
    }

    // 1. トークン交換
    $accessToken = $googleService->fetchAccessToken($_GET['code']);

    // 2. ユーザー情報取得
    $userInfo = $googleService->fetchUserInfo($accessToken);
    $userEmail = $userInfo['email'] ?? null;

    // 3. ドメインチェックとDB照合
    if ($userEmail) {
        $emailDomain = substr(strrchr($userEmail, "@"), 1);
        
        if ($emailDomain === ICC_DOMAIN) {
            
            try {
                // DB接続
                // localhost で接続できない場合は 127.0.0.1 を使用
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $pdo = new PDO($dsn, DB_USER, DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $authRepo = new AuthRepository($pdo);
                $user = $authRepo->findUserByEmail($userEmail); // LoginUser オブジェクトが返る
                
                $pdo = null; // 接続解除

                if ($user) {
                    // --- 共通のログイン成功処理 ---
                    session_regenerate_id(true);
                    $_SESSION['user_email']   = $userEmail;
                    $_SESSION['logged_in']    = true;
                    $_SESSION['user_picture'] = $userInfo['picture'] ?? null; // プロフィール画像URL

                    // --- ユーザータイプを問わず共通のメソッドで値を取得 ---
                    $_SESSION['user_id']    = $user->getUserId();
                    $_SESSION['user_grade'] = $user->getUserGrade();

                    // ログインしたユーザーが生徒の場合
                    // --- 生徒固有の追加情報をセッションに保存 ---
                    if ($user instanceof StudentLogin) {
                        $_SESSION['user_course'] = $user->getCourseId();
                    }
                    
                    // それぞれのホーム画面へリダイレクト
                    header('Location: ' . $user->getHomeUrl());
                    exit();

                } else {
                    // DBに登録なし
                    handle_login_error();
                }
            }
            catch (Throwable $e) {
                // DBエラーなどの致命的エラー
                error_log("Login Error: " . $e->getMessage());
                handle_login_error();
            }
        } else {
            // ドメイン不一致
            handle_login_error();
        }
    } else {
        // メールアドレス取得失敗
        handle_login_error();
    }
} 
// -------------------------------------------------------------------------
// 【B. 認証開始処理：login.htmlから直接呼び出された場合の処理】
// -------------------------------------------------------------------------

// ログイン済みの場合は、権限に応じたホーム画面へリダイレクト
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    
    // セッションから必要な情報を取得
    $grade    = $_SESSION['user_grade'] ?? '';
    $userId   = $_SESSION['user_id'] ?? '';
    $courseId = $_SESSION['user_course'] ?? ''; // 生徒の場合のみ存在

    // ポリモーフィズムを活用するため、セッション情報からオブジェクトを復元
    $loginUser = null;

    if ($grade === 'student@icc_ac.jp') {
        // 生徒クラスをインスタンス化
        $loginUser = new StudentLogin($userId, $grade, $courseId);
    } else if ($grade === 'teacher@icc_ac.jp' || $grade === 'master@icc_ac.jp') {
        try {
            // GoogleアイコンURLをDBに保存
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $authRepo = new AuthRepository($pdo);
            $authRepo->saveUserIconUrl($userId, $_SESSION['user_picture'], 'teacher');

        }
        catch (Exception $e) {
            handle_login_error();
        }
        // 先生クラスをインスタンス化
        $loginUser = new TeacherLogin($userId, $grade);
    } 

    // クラスのメソッド (getHomeUrl) を使ってURLを取得しリダイレクト
    if ($loginUser) {
        header('Location: ' . $loginUser->getHomeUrl());
        exit();
    }
}

// state パラメーターの生成
// ランダムな文字列を生成する
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state; // セッションに保存して後で検証する

// 認証URLを生成してGoogleへリダイレクト
header('Location: ' . $googleService->getAuthUrl($state));
exit();
?>