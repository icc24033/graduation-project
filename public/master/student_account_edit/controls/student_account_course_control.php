<?php
// student_account_course_control.php
require_once __DIR__ . '/../../../../app/session/session_config.php'; // セッション設定を読み込む

require_once '../../../../app/classes/security/SecurityHelper.php';
// セキュリティヘッダーの適用
SecurityHelper::applySecureHeaders();

// セッション開始とログイン判定を一括で行う
SecurityHelper::requireLogin();

// --- 修正箇所: ViewHelper を読み込む ---
require_once '../../../../app/classes/helper/dropdown/ViewHelper.php';
require_once '../../../../app/controllers/master/student_account_editers/StudentAccountEditController.php';

$controller = new StudentAccountEditController();
// index_course を呼び出す
$controller->index_course($_GET['course_id'] ?? null, $_GET['current_year'] ?? null);