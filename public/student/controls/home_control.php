<?php
// home_control.php

require_once '../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../../app/controllers/student/student_home/StudentHomeController.php';

$controller = new StudentHomeController();
$controller->index();