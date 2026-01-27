<?php
// --- デバッグ用：エラーを表示させる設定（解決したら削除してください） ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// teacher_home_control.php
// ブラウザがアクセスする際のコントローラーを呼び出すファイル
// セッション開始とログイン判定を一括で行う

// 1. セキュリティ設定
require_once '../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();

// 2. コントローラーとヘルパーの読み込み
require_once '../../../app/controllers/teacher/class_detail_edit/ClassDetailEditersController.php';

$controller = new ClassDetailEditorsController();
$controller->index();