<?php
// user_round.php
// アカウント編集選択画面のビュー
SecurityHelper::requireLogin();
SecurityHelper::applySecureHeaders();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <title>アカウント作成編集</title>
    <meta charset="utf-8">
    <meta name="robots" content="nofollow,noindex">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body id="grade_transfar">
    <div class="app-container">
        <header class="app-header">
            <h1>アカウント編集選択</h1>
            <img class="user-icon" src="images/user-icon.png"alt="ユーザーアイコン">
        </header>
        <main class="main-content">
            <div class="button-container">
                <button class="nav-button" onclick="location.href='../teacher_account_edit/controls/teacher_addition_control.php'">
                <span class="material-symbols-outlined icon-display">co_present</span>
                <div class="title">
                    <p class="title-text">先 生</p>
                </div>
                <p class="text">先生のアカウント作成編集やマスタの付与を行います。</p>
                </button>
                <button class="nav-button" onclick="location.href='../student_account_edit/controls/student_account_edit_control.php'">
                <span class="material-symbols-outlined icon-display">school</span> 
                <div class="title">
                    <p class="title-text">学 生</p>
                </div>
                <p class="text">学生のアカウント作成編集やコース変更、学年移動を行います。</p>
                </button>
            </div>
        </main>
    </div>
    <script src="js/script.js"></script>
</body>
</html>