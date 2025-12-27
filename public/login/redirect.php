<?php
// redirect.php
// ブラウザの戻るボタン対策用の中間ページ

// 0. SecurityHelper.php の呼び出し
require_once __DIR__ . '/../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();

// キャッシュを徹底的に無効化するヘッダーを出力
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="nofollow, noindex">
    <title>リダイレクト中...</title>
    <style>
        body {
            font-family: sans-serif;
            text-align: center;
            margin-top: 50px;
            color: #666;
        }
    </style>
    <script>
        // ページ読み込み完了を待たずに即座にリダイレクト開始
        <?php if($_SESSION['user_grade'] === 'teacher@icc_ac.jp'): ?>
            window.location.replace("../teacher/teacher_home.php");
        <?php elseif($_SESSION['user_grade'] === 'master@icc_ac.jp'): ?>
            window.location.replace("../master/master_home_control.php");
        <?php else: ?>
            window.location.replace("../login/login_error.html");
    </script>
</head>
<body>
    <p>ログインしました。</p>
    <p>ホーム画面へ移動しています...</p>
</body>
</html>