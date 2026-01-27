<?php
// class_detail_api.php
// 授業詳細編集API

// 1. 設定・セキュリティ
require_once '../../../app/classes/security/SecurityHelper.php';
require_once '../../../app/services/teacher/ClassDetailEditService.php';

// SecurityHelper::applySecureHeaders();
// SecurityHelper::requireLogin();

// ログインユーザーIDの取得
$currentTeacherId = $_SESSION['teacher_id'] ?? null;

// 2. リクエストの取得
$method = $_SERVER['REQUEST_METHOD'];
$service = new ClassDetailEditService();

try {
    if ($method === 'GET') {
        // ---------------------------------------------------
        // カレンダーデータの取得
        // ---------------------------------------------------
        $action = $_GET['action'] ?? '';

        if ($action === 'fetch_calendar') {
            $year = $_GET['year'] ?? date('Y');
            $month = $_GET['month'] ?? date('n');
            $subjectId = $_GET['subject_id'] ?? null;
            $courseIds = $_GET['course_ids'] ?? [];

            // 配列チェック
            if (!is_array($courseIds)) {
                // GETパラメータで配列が正しく渡らない場合の保険
                // 例: ?course_ids=1 のようなケース
                $courseIds = [$courseIds];
            }

            if (!$subjectId || empty($courseIds)) {
                echo json_encode([]); 
                exit;
            }

            // サービス実行
            $data = $service->getCalendarData($currentTeacherId, $subjectId, $courseIds, $year, $month);
            
            echo json_encode($data);
            exit;
        }

    } elseif ($method === 'POST') {
        // ---------------------------------------------------
        // 保存・削除処理
        // ---------------------------------------------------
        // JSON入力の取得
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        if ($action === 'save') {
            $input['teacher_id'] = $currentTeacherId;
            $result = $service->saveClassDetail($input);
            echo json_encode(['success' => $result, 'message' => $result ? 'Saved' : 'Failed']);
            exit;
        }
        
        if ($action === 'delete') {
            $date = $input['date'];
            $slot = $input['slot'];
            $courseIds = $input['course_ids'] ?? [];
            
            $result = $service->deleteClassDetail($date, $slot, $courseIds);
            echo json_encode(['success' => $result]);
            exit;
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}