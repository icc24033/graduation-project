<?php
// create_timetable_control.php

// ----------------------------------------------------
// 0. 基本設定とコントローラーの読み込み
// ----------------------------------------------------

// セキュリティヘッダー
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();
SecurityHelper::requireLogin();

SecurityHelper::requireTransitionToken('from_home_to_create_timetable', false);

// 作成したコントローラーを読み込み
require_once __DIR__ . '/../../../app/controllers/master/timetable_create/CreateTimetableController.php';

// ----------------------------------------------------
// 1. コントローラーの実行
// ----------------------------------------------------
// インスタンス化して、indexメソッド（メイン処理）を実行する
$controller = new CreateTimetableController();
$controller->index();