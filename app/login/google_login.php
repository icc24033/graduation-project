<?php
// ----------------------------------------------------
// 1. セッション設定の読み込み
// ----------------------------------------------------
// session_config.php 内で session_start() していないことが前提
require_once __DIR__ . '/../session/session_config.php'; 

// セッションが開始されていない場合のみ開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// エラー報告の有効化（デバッグ用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ----------------------------------------------------
// 2. 機密情報の読み込み
// ----------------------------------------------------
$config_path = __DIR__ . '/../../config/secrets_local.php'; 

if (!file_exists($config_path)) {
    die("エラー: 機密情報ファイルが見つかりません。パスを確認してください: " . $config_path);
}

$config = require_once $config_path;

// クラスファイルの読み込み
require_once __DIR__ . '/../classes/login/LoginUser.php';   // インターフェース
require_once __DIR__ . '/../classes/login/Student_login_class.php'; // 生徒クラス
require_once __DIR__ . '/../classes/login/Teacher_login_class.php'; // 先生クラス
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


// -------------------------------------------------------------------------
// 関数定義エリア
// -------------------------------------------------------------------------

/**
 * 指定されたエンドポイントにcURLリクエストを送信する関数
 */
function send_curl_request(string $url, ?array $data, ?string $accessToken, string $error_message): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $headers = [];
    if ($accessToken !== null) {
        $headers[] = "Authorization: Bearer {$accessToken}";
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    // SSL設定
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $cafile_path = __DIR__ . '/../../config/cacert.pem';
    curl_setopt($ch, CURLOPT_CAINFO, $cafile_path);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        die("{$error_message} cURLエラー: " . $curl_error);
    }
    if ($http_code !== 200) {
        die("{$error_message} HTTPコード {$http_code} レスポンス: {$response}");
    }
    
    $result = json_decode($response, true);
    if (isset($result['error'])) {
        $desc = $result['error_description'] ?? $result['error'];
        die("{$error_message} Google APIエラー: " . $desc);
    }

    return $result;
}

/** * ログインエラー時の共通処理
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

    // 1. トークン交換
    $token_params = array(
        'code'          => $_GET['code'],
        'client_id'     => CLIENT_ID,
        'client_secret' => CLIENT_SECRET,
        'redirect_uri'  => REDIRECT_URI,
        'grant_type'    => 'authorization_code'
    );
    $token = send_curl_request($token_endpoint, $token_params, null, "トークン交換");
    $accessToken = $token['access_token'];

    // 2. ユーザー情報取得
    $userInfo = send_curl_request($userinfo_endpoint, null, $accessToken, "ユーザー情報取得");
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

                // ★★★ 変更点: ポリモーフィズム（多態性）を活用 ★★★
                $authRepo = new AuthRepository($pdo);
                $user = $authRepo->findUserByEmail($userEmail); // LoginUser オブジェクトが返る
                
                $pdo = null; // 接続解除

                if ($user) {
                    // --- 共通のログイン成功処理 ---
                    session_regenerate_id(true);
                    $_SESSION['user_email']   = $userEmail;
                    $_SESSION['logged_in']    = true;
                    $_SESSION['user_picture'] = $userInfo['picture'] ?? null;

                    // --- ユーザータイプを問わず共通のメソッドで値を取得 ---
                    $_SESSION['user_id']    = $user->getUserId();
                    $_SESSION['user_grade'] = $user->getUserGrade();
                    
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

// ログイン済みの場合はホームへ
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ' . HOME_URL);
    exit();
}

// 認証URLを生成してGoogleへリダイレクト
$authUrl = $auth_endpoint . '?' . http_build_query(array(
    'client_id'     => CLIENT_ID,
    'redirect_uri'  => REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => $scope,
    'access_type'   => 'online',
    'prompt'        => 'select_account' // アカウント選択画面を強制
));

header('Location: ' . $authUrl);
exit();
?>