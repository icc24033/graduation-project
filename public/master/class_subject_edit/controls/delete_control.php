<?php
// delete_control.php
require_once __DIR__ . '/../../../../app/session/session_config.php'; // セッション設定を読み込む

require_once '../../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../../../app/controllers/master/class_subject_edit/ClassSubjectEditController.php';

$controller = new ClassSubjectEditController();
$controller->index_delete($_GET['search_grade'] ?? 'all', $_GET['search_course'] ?? 'all');