<?php

// 0.サーバーのセッションの有効期限とクライアント側Cookieの有効期限を設定

// 7日間SSOを維持するための設定 (session_start() より前) ★★★
$session_duration = 604800; // 7日間 (秒単位: 7 * 24 * 60 * 60)

// 0.1. サーバー側GCの有効期限を設定
ini_set('session.gc_maxlifetime', $session_duration);

// 0.2. クライアント側（ブラウザ）のCookie有効期限を設定
// 'lifetime' に $session_duration を設定することで、7日間はログイン状態を保持する
// secure => true: 本番環境で HTTPS でのみCookieを送信
// httponly => true: JavaScriptからのアクセスを禁止
session_set_cookie_params([
    'lifetime' => $session_duration,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // HTTPSならtrue
    'httponly' => true,
    'samesite' => 'Lax'
]);

?>