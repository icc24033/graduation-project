<?php
// user_round_control.php
// アカウント編集選択画面のコントローラー

// ----------------------------------------------------
// 0. 基本設定とコントローラーの読み込み
// ----------------------------------------------------

// セキュリティヘッダー
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();
SecurityHelper::requireLogin();

// 作成したコントローラーを読み込み
require_once __DIR__ . '/../../../app/controllers/master/user_round/UserRoundController.php';

// ----------------------------------------------------
// 1. コントローラーの実行
// ----------------------------------------------------
// インスタンス化して、indexメソッド（メイン処理）を実行する
$controller = new UserRoundController();
$controller->index();