<?php
// ログインしていない場合は強制的にログイン画面へリダイレクト
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.html'); // login.html は同じディレクトリにあると仮定
    exit();
}
// ブラウザの戻るボタンで認証ページに戻るのを防ぐためのファイル

// ----------------------------------------------------
// キャッシュを無効化
// ----------------------------------------------------
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
// ----------------------------------------------------
// 履歴スタックを操作
// ----------------------------------------------------
?>
<!DOCTYPE html>
<html>
<head>
    <title>リダイレクト中...</title>
    <script>
        // window.location.replace()で履歴からこのページを消去
        window.location.replace("../teacher/teacher_home.php");
    </script>
</head>
<body>
    <p>ログイン成功。ホーム画面に移動しています...</p>
</body>
</html>
<?php exit; ?>