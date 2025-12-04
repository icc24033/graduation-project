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

/** 
*ログインエラー時の共通処理
*遅延処理を行い、login_error.htmlへリダイレクト
**
* @return void
*/
function handle_login_error(): void
{
    sleep(3); // 遅延処理
    // 残っているセッションデータをクリア
    session_unset();
    session_destroy();
    
    header('Location: login_error.html');
    exit(); // リダイレクト後は必ず exit()
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

    if ($userEmail) { // メールアドレスが取得できた場合
        $emailDomain = substr(strrchr($userEmail, "@"), 1);

        if ($emailDomain === ICC_DOMAIN) {
           try {
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // login_tableからメールアドレスを検索し、user_gradeとuser_idを取得する
                // 取得カラムに user_grade と user_id を追加
                $sql = "SELECT user_id, user_grade FROM login_table WHERE user_email = :email";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':email', $userEmail);
                $stmt->execute();
                
                // 結果を連想配列で1行取得する
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC); 

                // if ($user_exists > 0) の代わりに、if ($user_data) でチェック
                if ($user_data) {
                    // 照合成功：メールアドレスがテーブルに存在する場合
                    // セッションIDを再生成してセッション固定攻撃を防止
                    session_regenerate_id(true); 

                    // 認証成功: セッションに情報を保存
                    $_SESSION['user_email'] = $userEmail; 
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_picture'] = $userInfo['picture'] ?? null;
                    
                    // $user_data から grade と user_id をセッションに保存
                    $_SESSION['user_grade'] = $user_data['grade']; 
                    $_SESSION['user_id'] = $user_data['user_id']; 

                    // データベース接続を閉じる
                    $pdo = null;
                    
                    // HTTPヘッダーでキャッシュを無効化
                    // ブラウザの履歴に戻ってもページが再読み込みされない。
                    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                    header("Pragma: no-cache");
                    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

                    // 履歴操作用の redirect.php にリダイレクト
                    header('Location: redirect.php'); 
                    exit();
                }
                else {
                    // 照合失敗：メールアドレスがテーブルに存在しない場合
                    $pdo = null; // データベース接続を閉じる
                    // 共通のエラー処理関数を呼び出す
                    handle_login_error();
                }
            }
            // catch (PDOException $e) {
            //     // データベース接続またはクエリ実行エラー
            //     // 攻撃者にエラー内容を伝えず、一般的なエラーメッセージを返す
            //     $pdo = null;
            //     error_log("DB Connection Error: " . $e->getMessage());
            //     // 共通のエラー処理関数を呼び出す
            //     handle_login_error();
            // }
            // //照合失敗：ループを抜けてエラーメッセージ表示へ
            // catch (Exception $e) {
            //     // その他のエラー処理
            //     error_log("General Error: " . $e->getMessage());
            //     $pdo = null;
            //     handle_login_error();
            // }
    //     }
    //     // ドメイン不一致またはメールアドレスが取得できなかった場合
    //     // 共通のエラー処理関数を呼び出す
    //     else {
    //         handle_login_error();
    //     }
    // }
    // else {
    //     // メールアドレスが取得できなかった場合
    //     // 共通のエラー処理関数を呼び出す
    //     handle_login_error();
    // }
    // ★ポイント2: DB接続エラーをキャッチし、エラーコードを表示して処理停止
            catch (PDOException $e) {
                // 開発環境向け：具体的なエラーメッセージを表示（本番環境では非推奨）
                $pdo = null;
                error_log("DB_ERROR: " . $e->getMessage()); 
                die("致命的なDBエラーが発生しました。設定を確認してください。<br>エラーコード: " . $e->getMessage());
            }
            // その他の例外をキャッチ
            catch (Exception $e) {
                $pdo = null;
                error_log("GENERAL_ERROR: " . $e->getMessage()); 
                die("致命的な一般エラーが発生しました。管理者にご連絡ください。");
            }
        }
        // ドメイン不一致の場合
        else {
            sleep(3); 
            // エラーダイ表示 or リダイレクト
            die("3:認証に失敗しました。ICCのGoogleアカウントでのみログイン可能です。");
        }
    }
    // メールアドレスが取得できなかった場合
    else {
        sleep(3); 
        die("4:認証に失敗しました。メールアドレスが取得できませんでした。");
    }
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