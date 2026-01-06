<?php
// --- デバッグ用：エラーを表示させる設定（解決したら削除してください） ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// create_timetable_control.php

// ----------------------------------------------------
// 0. 基本設定とコントローラーの読み込み
// ----------------------------------------------------

// セキュリティヘッダー
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();

// 作成したコントローラーを読み込み
require_once __DIR__ . '/../../../app/controllers/master/timetable_create/CreateTimetableController.php';

// ----------------------------------------------------
// 1. コントローラーの実行
// ----------------------------------------------------
// インスタンス化して、indexメソッド（メイン処理）を実行する
$controller = new CreateTimetableController();
$controller->index();

// ----------------------------------------------------
// 2. ビュー（画面）の読み込み
// ----------------------------------------------------
// コントローラーから見たViewファイルのパスを指定
// create_timetable.php を読み込む
require_once __DIR__ . '/create_timetable.php';