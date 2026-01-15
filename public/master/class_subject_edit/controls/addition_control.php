<?php
// addition_control.php

// --- デバッグ用：エラーを表示させる設定（解決したら削除してください） ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../../../app/controllers/master/class_subject_edit/ClassSubjectEditController.php';

$controller = new ClassSubjectEditController();
$controller->index_addition();