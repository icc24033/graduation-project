<?php
// ----------------------------------------------------
// google_login.php を安全に読み込む
// ----------------------------------------------------

$login_logic_path = __DIR__ . '/../../app/login/google_login.php';

// ファイルが存在するか確認
if (!file_exists($login_logic_path)) {
    // 開発中のエラー。本番環境ではサーバーエラーとして処理すべき
    die("致命的なエラー: ログイン処理ファイルが見つかりません。パスを確認してください: " . $login_logic_path);
}

// 外部からアクセスできない google_login.php の内容を実行する
require_once $login_logic_path;
?>