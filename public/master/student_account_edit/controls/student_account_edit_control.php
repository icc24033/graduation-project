<?php
require_once '../../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../../../app/controllers/master/student_account_editers/StudentAccountEditController.php';

$controller = new StudentAccountEditController();
$controller->index_addition();