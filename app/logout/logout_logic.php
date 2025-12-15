<?php
// 1. セッション設定の共通化と開始
require_once __DIR__ . '/../session/session_config.php'; 

session_start();

// 2. セッションデータの破棄
$_SESSION = array(); 

// 3. セッションCookieの破棄
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. セッションファイルの削除
session_destroy();