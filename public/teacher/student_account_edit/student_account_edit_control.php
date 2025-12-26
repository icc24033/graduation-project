<?php
// student_account_edit_control.php
// --- デバッグ用：エラーを表示させる設定（解決したら削除してください） ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. セキュリティ設定
require_once '../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../app/controllers/teacher/student_account_editers/StudentAccountEditController.php';
// ViewHelperはビューで使用する
require_once '../../../app/classes/helper/dropdown/ViewHelper.php';

// 3. コントローラーを起動してデータを取得する
$controller = new StudentAccountEditController();
$viewData = $controller->edit();
$basic_data = $controller->basic_info();

// 4. 配列を展開して変数にする ($courseList, $error_message 等の生成)
extract($viewData);
extract($basic_data);

require_once 'student_addition.php';
