<?php
// public/teacher/class_detail_edit/class_detail_edit_control.php

session_start();
// エラーを表示する設定（本番環境では 0 にすることを推奨）
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * 1. ログインチェック
 * 実際にはログイン画面から遷移してくる必要があります。
 */
if (!isset($_SESSION['teacher_id'])) {
    // 開発中のテスト用。本来は header('Location: login.php'); exit; などにします。
    $_SESSION['teacher_id'] = 24026; 
}
// テスト用に強制的に 24026 を代入する
$_SESSION['teacher_id'] = 24026; 
$teacher_id = $_SESSION['user_id'];

try {
    $base_path = dirname(__DIR__, 3); 
    $backend_file = $base_path . '/app/teacher/class_detail_edit/backend_add_detail.php';

    if (!file_exists($backend_file)) {
        throw new Exception("Backend file not found: " . $backend_file);
    }
    require_once $backend_file;

    // --- APIとしての挙動 (JavaScriptの fetch() から呼び出される部分) ---
    if (isset($_GET['action'])) {
        header('Content-Type: application/json; charset=UTF-8');

        // テンプレート取得
        if ($_GET['action'] === 'fetch_templates') {
            echo json_encode(BackendAddDetail::getTemplateObjects($teacher_id));
            exit;
        }

        // 授業データ取得 (教科IDが指定されていれば、その教科で絞り込む)
        if ($_GET['action'] === 'fetch') {
            $subject_id = $_GET['subject_id'] ?? null;
            
            if ($subject_id) {
                echo json_encode(BackendAddDetail::getAllClassDetails($teacher_id, $subject_id));
            } else {
                echo json_encode(BackendAddDetail::getAllClassDetails($teacher_id));
            }
            exit;
        }
    }

    // --- POSTリクエスト (保存・削除処理) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=UTF-8');
        $action = $_POST['action'] ?? '';

        // テンプレート保存
        if ($action === 'save_template') {
            BackendAddDetail::saveTemplateObject($_POST['template_name'], $teacher_id);
            echo json_encode(["status" => "success"]);
            exit;
        }

        // テンプレート削除
        if ($action === 'delete_template') {
            BackendAddDetail::deleteTemplateObject($_POST['template_name'], $teacher_id);
            echo json_encode(["status" => "success"]);
            exit;
        }

        // 授業データ削除
        if ($action === 'delete') {
            BackendAddDetail::deleteClassDetail($_POST['date'], $teacher_id);
            echo json_encode(["status" => "success"]);
            exit;
        }
        
        // 授業データ保存 (新規・更新)
        $subject_id = $_POST['subject_id'] ?? null;

        BackendAddDetail::saveClassDetail(
            $_POST['date'], 
            $_POST['content'] ?? '', 
            $_POST['status'] ?? 'creating', 
            $_POST['belongings'] ?? '', 
            $teacher_id,
            $subject_id
        );
        echo json_encode(["status" => "success"]);
        exit;
    }

    // --- 通常の画面表示 (最初のアクセス時) ---
    
    // 1. その先生が担当している教科一覧を取得
    $subjects = BackendAddDetail::getTeacherSubjects($teacher_id);
    
    // 2. ★追加：その先生が担当している曜日のリストを取得し、JSON形式にする
    // ステップ1で backend_add_detail.php に追加したメソッドを呼び出します
    $teacher_schedule_list = BackendAddDetail::getTeacherScheduleDays($teacher_id);
    $teacher_schedule_json = json_encode($teacher_schedule_list);

    // 3. 表示用ファイル（HTML側）を読み込み
    include __DIR__ . '/class_detail_edit.php';

} catch (Throwable $e) {
    // エラーハンドリング
    if (isset($_GET['action']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=UTF-8', true, 500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    } else {
        echo "<h1>システムエラーが発生しました</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
}