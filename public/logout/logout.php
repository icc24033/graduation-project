<?php
// ログアウト処理実行ファイル (public側)
// 1. appフォルダ内のログアウトロジックを呼び出し、Webアプリケーションのセッションを破棄
require_once __DIR__ . '/../../app/logout/logout_logic.php'; 

$redirect_path = '../login/login.html';
header('Location: ' . $redirect_path);
exit();