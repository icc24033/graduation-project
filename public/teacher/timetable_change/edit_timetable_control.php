<?php
// edit_timetable_control.php
// ブラウザがアクセスする際のコントローラーを呼び出すファイル

// 1. セキュリティ設定
require_once '../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. コントローラーとヘルパーの読み込み
require_once '../../../app/controllers/teacher/timetable_change/TimetableController.php';
// ViewHelperはビューで使用する
require_once '../../../app/classes/helper/dropdown/ViewHelper.php';

// 3. コントローラーを起動してデータを取得する
$controller = new TimetableController();
$viewData = $controller->edit();

// 4. 配列を展開して変数にする ($courseList, $error_message 等の生成)
extract($viewData);

// 5. ビュー（表示用ファイル）を読み込む
require_once 'edit_timetable.php';