<?php
// ログアウト処理実行ファイル (public側)

// 0. SecurityHelper.php の呼び出し
require_once __DIR__ . '/../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();

// 1. appフォルダ内のログアウトロジックを呼び出し、Webアプリケーションのセッションを破棄
require_once __DIR__ . '/../../app/logout/logout_logic.php'; 

$redirect_path = '../login/login.php';
header('Location: ' . $redirect_path);
exit();