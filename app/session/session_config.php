<?php
// session_config.php

// 既にセッションが開始されている場合は何もしない（二重起動防止）
if (session_status() === PHP_SESSION_ACTIVE) {
    return;
}

// 7日間SSOを維持するための設定
$session_duration = 604800; // 7日間

// サーバー側GCの有効期限
ini_set('session.gc_maxlifetime', (string)$session_duration);

// クライアント側（ブラウザ）のCookie有効期限
session_set_cookie_params([
    'lifetime' => $session_duration,
    'path'     => '/', // 全ディレクトリで有効化
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
