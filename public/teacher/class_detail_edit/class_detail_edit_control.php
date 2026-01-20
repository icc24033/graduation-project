<?php
session_start();
// エラーを表示する設定
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['teacher_id'] = 1; 
}
$teacher_id = $_SESSION['teacher_id'];

try {
    $base_path = dirname(__DIR__, 3); 
    $backend_file = $base_path . '/app/teacher/class_detail_edit/backend_add_detail.php';

    if (!file_exists($backend_file)) {
        throw new Exception("Backend file not found: " . $backend_file);
    }
    require_once $backend_file;

    // --- APIとしての挙動 (JSONを返す) ---
    if (isset($_GET['action'])) {
        header('Content-Type: application/json; charset=UTF-8');
        if ($_GET['action'] === 'fetch_templates') {
            echo json_encode(BackendAddDetail::getTemplateObjects($teacher_id));
            exit;
        }
        if ($_GET['action'] === 'fetch') {
            echo json_encode(BackendAddDetail::getAllClassDetails($teacher_id));
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=UTF-8');
        $action = $_POST['action'] ?? '';

        if ($action === 'save_template') {
            BackendAddDetail::saveTemplateObject($_POST['template_name'], $teacher_id);
            echo json_encode(["status" => "success"]);
            exit;
        }
        if ($action === 'delete_template') {
            BackendAddDetail::deleteTemplateObject($_POST['template_name'], $teacher_id);
            echo json_encode(["status" => "success"]);
            exit;
        }
        if ($action === 'delete') {
            BackendAddDetail::deleteClassDetail($_POST['date'], $teacher_id);
            echo json_encode(["status" => "success"]);
            exit;
        }
        
        // 通常の授業保存
        BackendAddDetail::saveClassDetail(
            $_POST['date'], 
            $_POST['content'] ?? '', 
            $_POST['status'] ?? 'creating', 
            $_POST['belongings'] ?? '', 
            $teacher_id
        );
        echo json_encode(["status" => "success"]);
        exit;
    }

    // --- 通常の画面表示 ---
    $subjects = BackendAddDetail::getTeacherSubjects($teacher_id);
    include __DIR__ . '/class_detail_edit.php';

} catch (Throwable $e) {
    // 画面が真っ白にならないようエラー内容を出す
    echo "<h1>システムエラーが発生しました</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}