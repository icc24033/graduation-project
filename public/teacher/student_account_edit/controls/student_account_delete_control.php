<?php
// 1. セキュリティ設定
require_once '../../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. 必要なクラスを読み込む
// アカウント作成コントローラーの読み込み
require_once '../../../../app/controllers/master/student_account_editers/StudentAccountEditController.php';
// 表示機能ヘルパーの読み込み
require_once '../../../../app/classes/helper/dropdown/ViewHelper.php';
// データベース操作用リポジトリファクトリーの読み込み
require_once '../../../../app/classes/repository/RepositoryFactory.php';

// 3. コントローラーを起動してデータを取得する
$controller = new StudentAccountEditController();
$viewData = $controller->edit(); // コースリストの取得
$basic_data = $controller->student_delete_basic_info(   // 基本情報の取得
    $_GET['course_id'] ?? null,
    $_GET['current_year'] ?? null
);

// 4. 配列を展開して変数にする ($courseList, $error_message 等の生成)
extract($viewData);
extract($basic_data);

require_once '../student_delete.php';
