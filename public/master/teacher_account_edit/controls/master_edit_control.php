<?php
// master_edit_control.php

require_once '../../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../../../app/controllers/master/teacher_account_editers/TeacherAccountEditController.php';

$controller = new TeacherAccountEditController();
$controller->index_master();