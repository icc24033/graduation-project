<?php
// public/teacher/class_detail_edit/class_detail_edit_control.php

// 【重要】エラーを画面に出して原因を特定できるようにします
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // 1. パスの修正（画像に基づき調整）
    $base_path = dirname(__DIR__, 3); // graduation-project フォルダを指す
    
    $security_helper = $base_path . '/app/classes/security/SecurityHelper.php';
    $backend_file = $base_path . '/app/teacher/class_detail_edit/backend_add_detail.php';

    if (file_exists($security_helper)) {
        require_once $security_helper;
        // SecurityHelper::applySecureHeaders(); // エラーが出る場合は一旦コメントアウト
    }

    if (!file_exists($backend_file)) {
        throw new Exception("ファイルが見つかりません: " . $backend_file);
    }
    require_once $backend_file;

    // 2. JSからのデータ取得リクエスト (GET)
    if (isset($_GET['action']) && $_GET['action'] === 'fetch') {
        header('Content-Type: application/json; charset=UTF-8');
        $data = BackendAddDetail::getAllClassDetails();
        echo json_encode($data ?: new stdClass());
        exit;
    }

    // 3. JSからの保存リクエスト (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=UTF-8');
        $action = $_POST['action'] ?? '';
        $date = $_POST['date'] ?? '';

        if ($action === 'delete') {
            BackendAddDetail::deleteClassDetail($date);
            echo json_encode(["status" => "success", "message" => "削除しました"]);
        } else {
            $content = $_POST['content'] ?? '';
            $status = $_POST['status'] ?? '';
            $belongings = $_POST['belongings'] ?? '';
            BackendAddDetail::saveClassDetail($date, $content, $status, $belongings);
            echo json_encode(["status" => "success", "message" => "保存に成功しました"]);
        }
        exit;
    }

    // 4. 通常の画面表示
    include __DIR__ . '/class_detail_edit.php';

} catch (Throwable $e) {
    // エラーが起きたらJSON形式で内容を返す
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);
    exit;
}