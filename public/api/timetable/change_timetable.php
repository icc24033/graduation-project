<?php
// api/timetable/change_timetable.php
// 時間割変更保存API

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
SecurityHelper::requireLogin();

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

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'データ形式が不正です']);
    exit;
}

// 必須データのチェック
if (empty($input['timetable_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '時間割IDが指定されていません']);
    exit;
}

// 7. 保存処理実行
try {
    $timetableId = $input['timetable_id'];
    $changes = $input['changes'] ?? []; // 変更がない場合は空配列（=全変更削除）

    $service = new TimetableService();
    $service->saveTimetableChanges($timetableId, $changes);

    echo json_encode(['success' => true, 'message' => '変更を保存しました']);

} catch (Exception $e) {
    http_response_code(500);
    // 開発中は $e->getMessage() を出すが、本番では 'サーバーエラーが発生しました' 等に留めるのが安全
    echo json_encode(['success' => false, 'message' => '保存エラー: ' . $e->getMessage()]);
}