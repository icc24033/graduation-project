<?php
// student_account_course_control.php

require_once '../../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();
if (session_status() === PHP_SESSION_NONE) session_start();

// --- 修正箇所: ViewHelper を読み込む ---
require_once '../../../../app/classes/helper/dropdown/ViewHelper.php';

require_once '../../../../app/controllers/master/student_account_editers/StudentAccountEditController.php';

$controller = new StudentAccountEditController();
// index_course を呼び出す
$controller->index_course($_GET['course_id'] ?? null, $_GET['current_year'] ?? null);