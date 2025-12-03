<?php

require_once __DIR__ . '/../session/session_config.php'; // セッション設定を読み込む

// login.htmlで"始める"ボタンが押されたときにこのファイルが呼び出される
// GoogleOAuth2.0によるログイン処理を行う

// 1. 環境設定
session_start();
// エラー報告の有効化
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ----------------------------------------------------
// 2. 機密情報の読み込み
// ----------------------------------------------------

// $config 変数に secrets_local.php の配列が格納される
$config_path = __DIR__ . '/../../config/secrets_local.php'; 

// ファイルが存在するか確認し、存在しない場合はエラーで停止
if (!file_exists($config_path)) {
    die("エラー: 機密情報ファイルが見つかりません。パスを確認してください: " . $config_path);
}

$config = require_once $config_path;


// 3. 定数の設定 (configファイルから値をロード)
// define() 内のハードコードされた値を $config から読み込む
define('CLIENT_ID', $config['client_id']); 
define('CLIENT_SECRET', $config['client_secret']); // シークレット分離
define('REDIRECT_URI', $config['redirect_uri']);
define('ICC_DOMAIN', $config['icc_domain']); 
define('HOME_URL', $config['home_url']);
// データベース接続情報
define('DB_HOST', $config['db_host']);   // 'db_host' の値 'localhost' を DB_HOST に代入
define('DB_NAME', $config['db_name']);   // 'db_name' の値 'icc_smart_campus' を DB_NAME に代入
define('DB_USER', $config['db_user']);   // 'db_user' の値 'root' を DB_USER に代入
define('DB_PASS', $config['db_pass']);   // 'db_pass' の値 'root' を DB_PASS に代入


// 取得したい情報のスコープ（メールアドレスと基本プロフィール）
$scope = 'email profile';
$auth_endpoint = 'https://accounts.google.com/o/oauth2/v2/auth'; // 認可エンドポイント・ユーザーをどこに誘導するか(ログイン同意画面の入口)
$token_endpoint = 'https://oauth2.googleapis.com/token'; // トークンエンドポイントのURLを定義
$userinfo_endpoint = 'https://openidconnect.googleapis.com/v1/userinfo'; // ユーザー情報エンドポイントのURLを定義

// -------------------------------------------------------------------------
// 汎用的なcURLリクエスト関数
// -------------------------------------------------------------------------

/**
 * 指定されたエンドポイントにcURLリクエストを送信する関数。
 * *
 * @param string $url リクエスト先のURL
 * @param array $data POSTで送信するデータ (または null)
 * @param string|null $accessToken Authorizationヘッダーに含めるアクセストークン
 * @param string $error_message エラー発生時に表示するメッセージのプレフィックス
 * @return array デコードされたJSONレスポンス
 */
function send_curl_request(string $url, ?array $data, ?string $accessToken, string $error_message): array
{
    // cURLセッションの初期化
    $ch = curl_init($url);

    // レスポンスを文字列で取得する設定
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // POSTデータの設定
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POST, true); // POSTリクエストに設定
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // データをURLエンコード形式で送信
    }

    // ヘッダーの設定
    $headers = [];
    if ($accessToken !== null) {
        // ユーザー情報取得時のAuthorizationヘッダーを設定 (セキュリティ配慮)
        $headers[] = "Authorization: Bearer {$accessToken}"; // Bearerトークン方式
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // ヘッダーを設定
    }
    
    // HTTPS通信のための設定（セキュリティ配慮 - 証明書検証を必須とする）
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // サーバー証明書の検証を有効化
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // ホスト名の検証を有効化
    
    // プロジェクト内の cacert.pem ファイルのフルパスを指定
    $cafile_path = __DIR__ . '/../../config/cacert.pem'; // __DIR__ は現在のファイルのディレクトリを示します
    curl_setopt($ch, CURLOPT_CAINFO, $cafile_path);

    $response = curl_exec($ch); // リクエストの実行
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // HTTPステータスコードの取得
    $curl_error = curl_error($ch); // cURLエラーメッセージの取得
    curl_close($ch); // cURLセッションの終了
    
    // cURL自体のエラーチェック
    if ($curl_error) {
        die("{$error_message} cURLエラー: " . $curl_error);
    }

    // HTTPステータスコードのチェック
    if ($http_code !== 200) {
        die("{$error_message} HTTPコード {$http_code} レスポンス: {$response}");
    }
    
    $result = json_decode($response, true); // JSONレスポンスを連想配列 $result にデコード

    // Google APIからのエラーチェック (JSONレスポンス内に'error'が含まれる場合)
    if (isset($result['error'])) {
        $desc = $result['error_description'] ?? $result['error'];
        die("{$error_message} Google APIエラー: " . $desc);
    }

    return $result; // デコードされたレスポンスを返す
}

// -------------------------------------------------------------------------
//【A. コールバック処理：認可コードを受け取った後の処理】
// -------------------------------------------------------------------------

if (isset($_GET['code'])) {

    // 3.1 トークン交換リクエストの準備
    $token_params = array(
        'code'          => $_GET['code'], // 認可コード
        'client_id'     => CLIENT_ID, // クライアントID
        'client_secret' => CLIENT_SECRET, // クライアントシークレット
        'redirect_uri'  => REDIRECT_URI, // リダイレクトURI
        'grant_type'    => 'authorization_code' // 認可タイプ(トークンを要求する方法を指定)
    );
    // 3.2 トークン交換リクエストの実行
    $token = send_curl_request($token_endpoint, $token_params, null, "トークン交換");
    $accessToken = $token['access_token']; // アクセストークンの取得

    // 3.2. ユーザー情報取得リクエスト
    // ユーザー情報取得にはアクセストークンをヘッダーに含める（$dataはnull）
    $userInfo = send_curl_request($userinfo_endpoint, null, $accessToken, "ユーザー情報取得");

    // 3.3. ICCアカウントかどうかのドメイン制限チェック (セキュリティチェック)
    $userEmail = $userInfo['email'] ?? null;

    if ($userEmail) {
        $emailDomain = substr(strrchr($userEmail, "@"), 1);

        if ($emailDomain === ICC_DOMAIN) {
           try {
                // PDOを使って安全に接続
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // login_tableからメールアドレスを検索
                $sql = "SELECT COUNT(*) FROM login_table WHERE user_email = :email";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':email', $userEmail);
                $stmt->execute();
                
                // 照合結果を取得
                $user_exists = $stmt->fetchColumn(); 

                if ($user_exists > 0) {
                    // 照合成功：ログイン続行
                    session_regenerate_id(true); 

                    // 認証成功: セッションに情報を保存
                    $_SESSION['user_email'] = $userEmail; 
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_picture'] = $userInfo['picture'] ?? null;

                    // データベース接続を閉じる
                    $pdo = null;
                    
                    // ホーム画面に遷移
                    header('Location: ' . HOME_URL);
                    exit();
                }
            }
            catch (PDOException $e) {
                // データベース接続またはクエリ実行エラー
                // 攻撃者にエラー内容を伝えず、一般的なエラーメッセージを返す
                error_log("DB Connection Error: " . $e->getMessage()); 
                sleep(2); // 遅延処理
                
                // ★★★ ここを一時的に置き換えます ★★★
                die("DB接続エラー詳細: " . $e->getMessage()); 
                // ★★★ 元のコード: die("1:認証に失敗しました。アプリケーションのエラーが発生しました。");

                // データベース接続を閉じる (この行はここに残して問題ありません)
                $pdo = null;
            }
            // catch (PDOException $e) {
            //     // データベース接続またはクエリ実行エラー
            //     // 攻撃者にエラー内容を伝えず、一般的なエラーメッセージを返す
            //     error_log("DB Connection Error: " . $e->getMessage()); 
            //     sleep(2); // 遅延処理
            //     die("1:認証に失敗しました。アプリケーションのエラーが発生しました。");

            //     // データベース接続を閉じる
            //     $pdo = null;
            // }
            // 照合失敗：ループを抜けてエラーメッセージ表示へ
            catch (Exception $e) {
                // その他のエラー処理
                error_log("General Error: " . $e->getMessage());
                sleep(2); // 遅延処理
                die("2:認証に失敗しました。アプリケーションのエラーが発生しました。");
            // データベース接続を閉じる
            $pdo = null;
            }
        }
        sleep(3); // 失敗時に3秒待機
        // ドメイン不一致またはメールアドレスが取得できなかった場合
        die("3:認証に失敗しました。ICCのGoogleアカウントでのみログイン可能です。");
    }
    sleep(3); // 失敗時に3秒待機
    die("4:認証に失敗しました。メールアドレスが取得できませんでした。");
}

// -------------------------------------------------------------------------
//【B. 認証開始処理：login.htmlから直接呼び出された場合の処理】
// -------------------------------------------------------------------------
// ログイン済みの場合はホームへリダイレクト
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ' . HOME_URL);
    exit();
}

// 認証URLを生成
$authUrl = $auth_endpoint . '?' . http_build_query(array(
    'client_id' => CLIENT_ID,
    'redirect_uri' => REDIRECT_URI,
    'response_type' => 'code',
    'scope' => $scope,
    'access_type' => 'online',
    'include_granted_scopes' => 'true',
    'hd' => ICC_DOMAIN,
    // CSRF対策としてstateパラメーターを生成し、リクエストに含める
    'state' => bin2hex(random_bytes(16)) 
    // ※ 厳密には、コールバック時に $_GET['state'] と $_SESSION['state'] の比較が必要です
));

// Googleのログイン画面へリダイレクト
header('Location: ' . $authUrl);
exit();

?>