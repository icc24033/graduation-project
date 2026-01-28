<?php
require_once __DIR__ . '/../../../../app/session/session_config.php'; // セッション設定を読み込む

require_once '../../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../../../app/controllers/master/student_account_editers/StudentAccountEditController.php';

$controller = new StudentAccountEditController();
$controller->index_transfer($_GET['course_id'] ?? null, $_GET['current_year'] ?? null);