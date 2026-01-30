<?php
// backend_csv_error_teacher_edit.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php'; // 追加

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('不正なアクセスです。');
}

// CSRF検証を追加
if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    die('CSRFトークンが無効です。');
}

// 正常データ用テーブル (temp_teacher_csv) に挿入するSQL
$sql_insert_temp_table = "INSERT INTO temp_teacher_csv (name, email) VALUES (?, ?);";

// エラーテーブルから削除するSQL
$sql_delete_error_id = "DELETE FROM error_teacher_table WHERE id = ?;";

try {
    require_once __DIR__ . '/../../../app/classes/repository/RepositoryFactory.php';
    $pdo = RepositoryFactory::getPdo();

    // 画面から送られてきた修正データ（teachers配列）をループ処理
    if (isset($_POST['teachers']) && is_array($_POST['teachers'])) {
        
        var_dump($_POST['teachers']); // デバッグ用出力

        foreach ($_POST['teachers'] as $id => $data) {
            $name = trim($data['name'] ?? '');
            $email = trim($data['email'] ?? '');

            // 再バリデーション（ここでもドメインチェックを行う）
            if (!empty($name) && filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@isahaya-cc\.ac\.jp$/i', $email)) {
                
                try {
                    $pdo->beginTransaction();

                    // 1. 正常用の一時テーブルに挿入
                    $stmt_csv = $pdo->prepare($sql_insert_temp_table);
                    $stmt_csv->execute([$name, $email]);

                    // 2. エラーテーブルから削除
                    $stmt_delete_error = $pdo->prepare($sql_delete_error_id);
                    $stmt_delete_error->execute([$id]);

                    $pdo->commit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    echo "修正データの反映に失敗しました: " . $e->getMessage();
                }
            }
        }
    }
} catch (PDOException $e) {
    die("DBエラー: " . $e->getMessage());
}

// 処理が終わったら元の画面に戻る
header("Location: ../../../public/master/teacher_account_edit/controls/teacher_addition_control.php?backend=csv_upload");
exit();