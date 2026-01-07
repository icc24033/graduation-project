<?php
// teacher_home_control.php
// ブラウザがアクセスする際のコントローラーを呼び出すファイル
// セッション開始とログイン判定を一括で行う

// 1. セキュリティ設定
require_once '../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();

// 2. コントローラーとヘルパーの読み込み
require_once '../../app/controllers/teacher/home/TeacherHomeController.php';

$controller = new TeacherHomeController();
$controller->index();