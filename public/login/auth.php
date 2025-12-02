<?php
// ----------------------------------------------------
// google_login.php を読み込む
// ----------------------------------------------------

$login_logic_path = __DIR__ . '/../../app/login/google_login.php';

// ファイルが存在するか確認
if (!file_exists($login_logic_path)) {
    die("致命的なエラー: ログイン処理ファイルが見つかりません。パスを確認してください: " . $login_logic_path);
}

// google_login.php の内容を実行
require_once $login_logic_path;
?>