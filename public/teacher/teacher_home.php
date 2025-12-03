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
            <!-- 機能 -->
            <section class="tool">
                <img class="title_icon" src="./images/icon_tool.png" alt="機能アイコン">
                <p class="title_name">機能</p>
            </section>
            <div class="background">
                <!-- 時間割り作成カード -->
                 <div class="card">
                    <a href="">
                        <img class="card_icon_calendar-plus" src="images/calendar-plus.png">
                        <p class="card_main">時間割り作成</p>
                        <p class="card_sub">期間を設定して<br>時間割を作成します。</p>
                    </a>
                </div>
                <!-- 時間割り編集カード -->
                <div class="card">
                    <a href="">
                        <img class="card_icon_square-pen" src="images/square-pen.png">
                        <p class="card_main">時間割り編集</p>
                        <p class="card_sub">編集したいコースごとに<br>時間割を編集します。</p>
                    </a>
                </div>
                <!-- アカウント編集カード -->
                <div class="card">
                    <a href="">
                        <img class="card_icon_user-round" src="images/user-round-cog.png">
                        <p class="card_main">アカウント編集</p>
                        <p class="card_sub">アカウントの情報を確認、編集<br>することができます。</p>
                    </a>
                </div>
                <!-- 権限付与カード -->
                <div class="card">
                    <a href="">
                        <img class="card_icon_shield-check" src="images/shield-check.png">
                        <p class="card_main">権限付与</p>
                        <p class="card_sub">通知事項を編集します。</p>
                    </a>
                </div>
                <!-- 通知事項編集カード -->
                <div class="card">
                    <a href="">
                        <img class="card_icon_bell-dot" src="images/bell-dot.png">
                        <p class="card_main">通知事項編集</p>
                        <p class="card_sub">通知事項を編集します。</p>
                    </a>
                </div>
                <!-- 授業詳細編集カード -->
                <div class="card">
                    <a href="">
                        <img class="card_icon_clipboard-list" src="images/clipboard-list.png">
                        <p class="card_main">授業詳細編集</p>
                        <p class="card_sub">授業詳細を編集します。</p>
                    </a>
                </div>
                <!-- 時間割り閲覧カード -->
                <div class="card">
                    <a href="">
                        <img class="card_icon_calendar-clock" src="images/calendar-clock.png">
                        <p class="card_main">時間割り閲覧</p>
                        <p class="card_sub">先4週間分を選択したコースごとに<br>閲覧します。</p>
                    </a>
                </div>
                <!-- 送信先設定カード -->
                <div class="card">
                    <a href="">
                        <img class="card_icon_mails" src="images/mails.png">
                        <p class="card_main">送信先設定</p>
                        <p class="card_sub">期間を設定して<br>時間割を作成します。</p>
                    </a>
                </div>
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