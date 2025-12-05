<?php
// 0.サーバーのセッションの有効期限とクライアント側Cookieの有効期限を設定

// 7日間SSOを維持するための設定
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

// セッションを開始
session_start();

// ログインしていない場合は強制的にログイン画面へリダイレクト
if (!isset($_SESSION['user_email'])) {
    header('Location: ../login/login.html');
    exit();
}

// セッションから画像URLを取得
$user_picture = $_SESSION['user_picture'] ?? 'images/default_icon.png'; // デフォルト画像を準備

// クラスファイルを読み込む
// パスは teacher_home.php の位置から /app/classes/ への相対パス
$base_path = __DIR__ . '/../../app/classes/';

require_once $base_path . 'User_class.php'; 
require_once $base_path . 'Teacher_class.php'; 
require_once $base_path . 'Master_class.php'; 

// ユーザーの権限レベルと固有IDをセッションから取得
$user_grade = $_SESSION['user_grade'] ?? 'student'; 
$current_user_id = $_SESSION['user_id'] ?? '';
$user_picture = $_SESSION['user_picture'] ?? 'images/default_icon.png';

// 遷移先ファイルの定義（クラスに渡すため配列化）
// リンク先にIDは含めず、遷移先でセッションからIDを読み取らせる設計
$links = [
    // 開発状況に応じてリンクを修正する
    'link_time_table_create' => "time_table_create.php",
    'link_time_table_edit'   => "time_table_edit.php",
    'link_account_edit'      => "account_edit.php",
    'link_permission_grant'  => "permission_grant.php",
    'link_notification_edit' => "notification_edit.php",
    'link_subject_edit'      => "subject_edit.php", 
    'link_time_table_view'   => "time_table_view.php",
    'link_send_setting'      => "send_setting.php"
];

$user_object = null;

// 権限に応じて適切なユーザーオブジェクトを生成
switch ($user_grade) {
    case 'master@icc_ac.jp':
        $user_object = new Master($current_user_id);
        break;
    case 'teacher@icc_ac.jp':
        $user_object = new Teacher($current_user_id);
        break;
    default:
        // 権限がない場合や未定義の場合、オブジェクトは生成しない
        break;
}

$function_cards_html = '';

// オブジェクトが生成されていれば、メソッドを呼び出してHTMLを取得
if ($user_object instanceof User_MasAndTeach) {
    $function_cards_html = $user_object->getFunctionCardsHtml($links);
}
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <title>QOLマスタアカウント</title>
        <meta charset="utf-8">
        <meta name="description" content="">
        <meta name="keywords" content="">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="robots" content="nofollow,noindex">
        <link rel="stylesheet" type="text/css" href="css/reset.css">
        <link rel="stylesheet" type="text/css" href="css/common.css">
        <link rel="stylesheet" type="text/css" href="css/teacher_home/style.css">
        <link rel="stylesheet" type="text/css" href="css/teacher_home/user_menu.css">

    </head>
    <body>
        <header> 
            <div class="user-avatar" id="userAvatar">
                <img src="<?= htmlspecialchars($user_picture) ?>" alt="ユーザーアイコン" class="avatar-image">
            </div>
            <!-- ユーザーメニューポップアップ (仮)-->
            <div class="user-menu-popup" id="userMenuPopup">
                <a href="../logout/logout.php" class="logout-button">
                    <span class="icon-key"></span>
                    ICCスマートキャンパスからログアウト
                </a>
            </div>
        </header>

        <div class="main">
            <section class="tool">
                <img class="title_icon" src="images/icon_tool.png" alt="機能アイコン">
                <p class="title_name">機能</p>
            </section>
            
            <div class="background">
                <?= $function_cards_html ?> 
            </div>
        </div>
        <!-- ここから仮置きのコード -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const userAvatar = document.getElementById('userAvatar');
                const userMenuPopup = document.getElementById('userMenuPopup');

                // アイコンをクリックした時の処理
                userAvatar.addEventListener('click', function(event) {
                    // ポップアップの表示・非表示を切り替える
                    userMenuPopup.classList.toggle('is-visible');
                    // イベントの伝播を停止して、ドキュメント全体へのクリックイベントがすぐに実行されるのを防ぐ
                    event.stopPropagation();
                });

                // ポップアップの外側をクリックした時に閉じる処理
                document.addEventListener('click', function(event) {
                    // クリックされた要素がアイコンでもポップアップ内でもない場合
                    if (!userMenuPopup.contains(event.target) && !userAvatar.contains(event.target)) {
                        userMenuPopup.classList.remove('is-visible');
                    }
                });
            });
        </script>
    </body>
</html>