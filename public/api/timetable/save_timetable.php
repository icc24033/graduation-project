<?php
// save_timetable.php
// 時間割り保存API

// 1. 設定ファイルと必要なクラスの読み込み
// パスは実際のフォルダ構成に合わせて調整してください
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';
require_once __DIR__ . '/../../../app/services/master/timetable_create/TimetableService.php';

// 2. セキュリティヘッダーの適用
SecurityHelper::applySecureHeaders();

// JSONレスポンス用ヘッダー
header('Content-Type: application/json; charset=utf-8');

// 3. POSTメソッド以外のアクセスを拒否
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// 4. ログインチェック (SecurityHelperを使用)
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'ログインセッションが切れました。再ログインしてください。']);
    exit;
}

// 5. CSRFトークンの検証
// ※JS側でヘッダーに 'X-CSRF-Token' を含める
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
if (!SecurityHelper::validateCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '不正なリクエストです(CSRFトークンエラー)']);
    exit;
}

// 6. JSON入力の受け取り
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '不正なデータ形式です']);
    exit;
}

try {
    // 7. サービスの呼び出し
    $service = new TimetableService();
    $savedId = $service->saveTimetable($input);

    echo json_encode([
        'success' => true,
        'id' => $savedId,
        'message' => '保存が完了しました'
    ]);

} catch (Exception $e) {
    // エラーログへの記録 (本番環境ではユーザーに詳細なエラーを見せない)
    error_log("Timetable Save Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '保存処理中にエラーが発生しました。'
    ]);
}