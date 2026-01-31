<?php
// backend_edit_info.php

// 必要なファイルの読み込み
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

// 1. リクエスト検証
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

// 2. データの取得
$indices = $_POST['update_indices'] ?? []; // チェックされた行の番号
$allData = $_POST['teacher_data'] ?? [];    // 全行の入力データ

// 更新対象がない場合は戻る
if (empty($indices)) {
    header("Location: ../../../public/master/teacher_account_edit/controls/teacher_info_control.php");
    exit;
}

try {
    // 3. DB接続
    $pdo = RepositoryFactory::getPdo();

    // 4. トランザクション開始（一括処理の安全性を確保）
    $pdo->beginTransaction();

    // プリペアドステートメントの準備
    $sql = "UPDATE teacher SET teacher_name = ?, teacher_mail = ? WHERE teacher_id = ?";
    $stmt = $pdo->prepare($sql);

    // 5. チェックされた行だけをループして実行
    // ドメインのチェック
    $allowedDomain = '@isahaya-cc.ac.jp';

    foreach ($indices as $index) {
        if (isset($allData[$index])) {
            $data = $allData[$index];
            $id   = (int)$data['id'];
            $name = (string)$data['name'];
            $mail = (string)$data['mail'];

            // 文末が指定のドメインで終わっているかチェック
            if (substr($mail, -strlen($allowedDomain)) !== $allowedDomain) {
                // ドメインが一致しない場合はエラーとして処理を中断、またはスキップ
                // 今回はエラーメッセージを持って元の画面に戻る例
                header("Location: ../../../public/master/teacher_account_edit/controls/teacher_info_control.php?error=invalid_domain");
                exit;
            }

            $stmt->execute([$name, $mail, $id]);
        }
    }

    // 6. すべて成功したら確定
    $pdo->commit();

    // 成功パラメータを付けてリダイレクト
    header("Location: ../../../public/master/teacher_account_edit/controls/teacher_info_control.php?status=success");
    exit;

} catch (PDOException $e) {
    // 失敗した場合はロールバック（変更を取り消す）
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: ../../../public/login/connection_error.html");
    exit;
}