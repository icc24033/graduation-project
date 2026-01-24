<?php
// api/timetable/delete_timetable.php
// 時間割り削除API

// 1. 設定ファイルと必要なクラスの読み込み
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';
require_once __DIR__ . '/../../../app/services/master/timetable_create/TimetableService.php';

// 2. セキュリティヘッダー & JSONヘッダー
SecurityHelper::applySecureHeaders();
header('Content-Type: application/json; charset=utf-8');

// 3. POSTメソッド以外のアクセスを拒否
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// 4. ログインチェック
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'セッションが切れました。再ログインしてください。']);
    exit;
}

// 5. CSRFトークンの検証
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
if (!SecurityHelper::validateCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '不正なリクエストです(CSRFトークンエラー)']);
    exit;
}

// 6. JSON入力の受け取り
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input || !isset($input['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '削除対象のIDが指定されていません']);
    exit;
}

$timetableId = $input['id'];

try {
    // 7. 削除実行 (Service経由)
    $service = new TimetableService();
    $isDeleted = $service->deleteTimetable($timetableId);

    if ($isDeleted) {
        echo json_encode(['success' => true, 'message' => '削除しました']);
    } else {
        echo json_encode(['success' => false, 'message' => '削除対象が見つかりませんでした（すでに削除されている可能性があります）']);
    }

} catch (Exception $e) {
    // エラーログはService側でも出力されますが、ここでもキャッチしてレスポンスを返す
    error_log("Delete API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'サーバーエラーが発生しました']);
}