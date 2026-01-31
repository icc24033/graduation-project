<?php
// class_detail_api.php
// 授業詳細編集API

// 1. 設定・セキュリティ
require_once '../../../app/classes/security/SecurityHelper.php';
require_once '../../../app/services/teacher/ClassDetailEditService.php';

SecurityHelper::applySecureHeaders();
SecurityHelper::requireLogin();

// ログインユーザーIDの取得
$currentTeacherId = $_SESSION['user_id'] ?? null;

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
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        if ($action === 'save') {
            // セッションから先生IDを取得
            $input['teacher_id'] = $currentTeacherId;
            
            $result = $service->saveClassDetail($input);
            
            echo json_encode([
                'success' => $result, 
                'message' => $result ? 'Saved successfully' : 'Failed to save'
            ]);
            exit;
        }
        
        if ($action === 'delete') {
            $date = $input['date'];
            $slot = $input['slot'];
            
            // JSから送られてくるキー(course_id, subject_id)に合わせて取得
            $courseId = $input['course_id']; 
            $subjectId = $input['subject_id'];
            
            // 引数を4つ渡す
            $result = $service->deleteClassDetail($date, $slot, $courseId, $subjectId);
            
            if ($result === false) {
                throw new Exception("削除処理に失敗しました");
            }

            echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
            exit;
        }
    }

} catch (Exception $e) {
    // エラーハンドリング・ユーザーへは情報を与えないように設計する
    error_log("ClassDetailEditAPI Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request.'
    ]);
}