<?php
// SecurityHelperの読み込み
require_once __DIR__ . '/../../app/classes/security/SecurityHelper.php';

// セキュリティヘッダーの適用
SecurityHelper::applySecureHeaders();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <title>ICCスマートキャンパス</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="nofollow, noindex">
    <link rel="stylesheet" href="css/login_css/style.css"> 
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body>
    <video class="bg-video" autoplay muted playsinline>
        <source src="video/login.mp4" type="video/mp4">
        お使いのブラウザは動画タグに対応していません。
    </video>

    <div id="transition-overlay"></div>

    <div class="container">   
        <h1 class="app-name fade-in">ICCスマートキャンパス</h1>
        <h2 class="tagline fade-in">学生生活をサポートする<br>タスク管理ツール</h2>
        
        <div class="action-area fade-in">
            <a href="auth.php" class="start-button-link">
                <button class="start-button">始める</button>
            </a>
        </div>

        <h3 class="feature-title fade-in">ICCスマートキャンパスが選ばれる理由</h3>
        <p class="feature-text fade-in">
            ICCスマートキャンパスを使えば、時間割管理の効率性と学校生活の満足度が向上します
        </p>
    </div>

    <script src="js/login.js"></script>
</body>
</html>