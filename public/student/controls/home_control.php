<?php
// home_control.php
require_once __DIR__ . '/../../../app/session/session_config.php'; // セッション設定を読み込む

require_once '../../../app/classes/security/SecurityHelper.php';
// セキュリティヘッダーの適用
SecurityHelper::applySecureHeaders();

// セッション開始とログイン判定を一括で行う
SecurityHelper::requireLogin();

// セキュリティヘッダーを適用
SecurityHelper::applySecureHeaders();

if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../../app/controllers/student/student_home/StudentHomeController.php';

$controller = new StudentHomeController();
$controller->index();