<?php
// セッションを開始
session_start();

// ログインしていない場合は強制的にログイン画面へリダイレクト
if (!isset($_SESSION['user_email'])) {
    header('Location: ../login/login.html');
    exit();
}
$user_picture = $_SESSION['user_picture'] ?? 'assets/default_avatar.png';


// セッションから画像URLを取得
$user_picture = $_SESSION['user_picture'] ?? 'assets/default_avatar.png'; // デフォルト画像を準備しておくのが安全
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
        <link rel="stylesheet" type="text/css" href="css/teacher_home/style.css">
        <link rel="stylesheet" type="text/css" href="css/reset.css">
        <link rel="stylesheet" type="text/css" href="css/common.css">
    </head>
    <body>
        <header class="header">
            <h1 class="header-title">ホーム</h1>
            
            <div class="user-avatar">
                <img src="<?= htmlspecialchars($user_picture) ?>" alt="ユーザーアイコン" class="avatar-image">
            </div>
        </header>

        <div class="main">
            <!-- 機能 -->
            <section class="tool">
                <img class="title_icon" src="images/icon_tool.png" alt="機能アイコン">
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
            <!-- メッセージ履歴 -->
            <section class="message_history">
                <img class="title_icon" src="images/icon_mail.png" alt="機能アイコン">
                <p class="title_name">メッセージ履歴</p>
            </section>
            <div class="background">
                <a href="">
                    <div class="list-item">
                        
                        <div class="profile-image"></div>
                        <div class="content">
                            <div class="name">送信先</div>
                            <div class="text">テキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキスト</div>
                        </div>
                        <div class="date">○月○日</div>
                    </div>
                </a>
                <a href="">
                    <div class="list-item">
                        
                        <div class="profile-image"></div>
                        <div class="content">
                            <div class="name">送信先</div>
                            <div class="text">テキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキスト</div>
                        </div>
                        <div class="date">○月○日</div>
                    </div>
                </a>
                <a href="">
                    <div class="list-item">
                        
                        <div class="profile-image"></div>
                        <div class="content">
                            <div class="name">送信先</div>
                            <div class="text">テキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキスト</div>
                        </div>
                        <div class="date">○月○日</div>
                    </div>
                </a>
                <a href="">
                    <div class="list-item">
                        
                        <div class="profile-image"></div>
                        <div class="content">
                            <div class="name">送信先</div>
                            <div class="text">テキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキストテキスト</div>
                        </div>
                        <div class="date">○月○日</div>
                    </div>
                </a>
            </div>
        </div>
    </body>
