<?php
// master_home.php
// マスター用ホーム画面

require_once __DIR__ . '/../../app/classes/security/SecurityHelper.php';

// セッション開始とログイン判定を一括で行う
SecurityHelper::requireLogin();

// セキュリティヘッダーを適用
SecurityHelper::applySecureHeaders();

?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <title>ICCスマートキャンパス・マスターアカウント</title>
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
        <header class="app-header">
            <h1>ホーム画面</h1>
            <img class="header-icon" src="images/icon-house.png"alt="ヘッダーアイコン"> 
            <div class="user-avatar" id="userAvatar" style="position: absolute; right: 20px; top: 5px;">
                <img src="<?= htmlspecialchars($user_picture) ?>" alt="ユーザーアイコン" class="avatar-image">   
            </div>
            <div class="user-menu-popup" id="userMenuPopup">
                    <a href="../logout/logout.php" class="logout-button">
                        <span class="icon-key"></span>
                        アプリからログアウト
                    </a>
                    <a href="../help/help_control.php?back_page=1" class="help-button" target="_blank" rel="noopener noreferrer">
                        <span class="icon-lightbulb"></span> ヘルプ
                    </a>
                    <a href="../student/controls/home_control.php" class="student-home-button" target="_blank" rel="noopener noreferrer">
                        <span class="icon-home"></span> 生徒ホーム画面
                    </a>
                </div>
            <!--  ICCスマートキャンパスロゴ -->
            <img src="<?= htmlspecialchars($smartcampus_picture) ?>" alt="Webアプリアイコン" width="200" height="60" style="position: absolute; left: 20px; top: 5px;">  
        </header>

        <div class="main">
            <section class="tool">
            <div class="background">
                <?= $function_cards_html ?> 
            </div>
            </section>
        </div>
        <script>
            window.addEventListener('pageshow', (event) => {
                // キャッシュから表示された場合（ブラウザバック等）
                if (event.persisted || (performance.getEntriesByType('navigation')[0].type === 'back_forward')) {
                    // ページをリロードして、PHP側の SecurityHelper::setTransitionToken を再実行させる
                    // これにより、再度「時間割り作成」へ遷移するための有効な手形が発行されます
                    window.location.reload();
                }
            });
        </script>
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