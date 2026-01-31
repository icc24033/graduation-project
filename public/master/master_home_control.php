<?php

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
$controller->index();

