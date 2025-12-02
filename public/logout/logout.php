<?php
// ログアウト処理実行ファイル (public側)

// 1. appフォルダ内のログアウトロジックを呼び出し、Webアプリケーションのセッションを破棄
// 注意: public/logout.php から app/logout_logic.php を参照するパスに修正
require_once __DIR__ . '/../../app/logout/logout_logic.php'; 

// 2. Googleのログアウト処理を挟む
//    リダイレクト先は、Googleがセッションをクリアした後に戻るURL（ログイン画面）
//    このURLは、public/login/login.html への絶対パスである必要があります。
//$google_redirect_uri = 'http://localhost/2025/sotsuken/graduation-project/public/login/login.html'; 
//$google_redirect_uri = 'http://localhost/';
// GoogleのOAuth2.0ログアウトエンドポイントへリダイレクト
//$encoded_redirect_uri = urlencode(urlencode($google_redirect_uri));
//$google_logout_url = 'https://accounts.google.com/Logout?continue=' . $encoded_redirect_uri;

$redirect_path = '../login/login.html';
header('Location: ' . $redirect_path);
exit();