<?php
// ----------------------------------------------------
// ブラウザの戻るボタン対策用の中間ページ
// ----------------------------------------------------

// キャッシュを徹底的に無効化するヘッダーを出力
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// ※ ここで session_start() や条件分岐を行わないことで
//    500エラーのリスクを極限まで減らします。
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
        window.location.replace("../teacher/teacher_home.php");
    </script>
</head>
<body>
    <p>ログインしました。</p>
    <p>ホーム画面へ移動しています...</p>
</body>
</html>