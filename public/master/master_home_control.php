<?php
// --- デバッグ用：エラーを表示させる設定（解決したら削除してください） ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// master_home_control.php
// ブラウザがアクセスする際のコントローラーを呼び出すファイル
// セッション開始とログイン判定を一括で行う
// 1. セキュリティ設定
require_once '../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();
SecurityHelper::requireLogin();

// 2. コントローラーとクラスの読み込み
require_once '../../app/controllers/master/home/MasterHomeController.php';

// 3. コントローラーのインスタンス化と処理実行
$controller = new MasterHomeController();
$user_data = $controller->getHomeDataByUserdate();
$user_instance = $controller->create_user_instance($user_data['user_grade'],$user_data['current_user_id']);

try {
    SecurityHelper::requireLogin();
}
catch (Exception $e) {
    // ログインしていない場合はlogin_error.htmlを表示
    require_once '../login/login_error.html';
    exit();
}

// user_instanceがMasterクラスのインスタンスであれば、関数カードのHTMLを生成し、ビューを読み込む
if ($user_instance !== null) {
    $links = $controller->html_links();
    $function_cards_html = $controller->generate_function_cards_html($user_instance, $links);
    extract($links);
    extract($user_data);
    require_once 'master_home.php';
}
else {
    // 権限がない場合はlogin_error.htmlを表示
    require_once '../login/login_error.html';
    exit();
}

