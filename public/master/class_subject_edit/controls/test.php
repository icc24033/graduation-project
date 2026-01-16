<?php
// test.php
require_once __DIR__ . '/../../../../app/classes/repository/RepositoryFactory.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $pdo = RepositoryFactory::getPdo();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB接続失敗: ' . $e->getMessage()]);
    exit;
}

$courseInfo = [   
    'itikumi'       => ['id' => 7, 'grade' => 1],
    'nikumi'        => ['id' => 8, 'grade' => 1],
    'iphasu'        => ['id' => 6, 'grade' => 1],
    'kihon'         => ['id' => 5, 'grade' => 1],
    'applied-info'  => ['id' => 4, 'grade' => 1],
    'multimedia'    => ['id' => 3, 'grade' => 2],
    'system-design' => ['id' => 1, 'grade' => 2],
    'web-creator'   => ['id' => 2, 'grade' => 2]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $title = $_POST['subject_name'] ?? $_POST['title'] ?? '';

    try {
        // 1. 科目IDの取得
        $stmtSub = $pdo->prepare("SELECT subject_id FROM subjects WHERE subject_name = ? LIMIT 1");
        $stmtSub->execute([$title]);
        $subject_id = $stmtSub->fetchColumn();

        if (!$subject_id) throw new Exception("科目「{$title}」が見つかりません。");

        if ($action === 'update_field') {
            $field = $_POST['field']; 
            $new_val = $_POST['value'];
            $course_key = $_POST['course_key'] ?? ''; 
            $course_id = $courseInfo[$course_key]['id'] ?? null;

            if (!$course_id) throw new Exception("コース情報(course_key)が正しく送信されていません。");

            if ($field === 'teacher') {
                $db_field = 'teacher_id';
                // ★確認したSQLに合わせて「teacher」テーブルを参照
                $stmtT = $pdo->prepare("SELECT teacher_id FROM teacher WHERE teacher_name = ? LIMIT 1");
                $stmtT->execute([$new_val]);
                $val_to_save = $stmtT->fetchColumn() ?: 0;
            } else {
                $db_field = 'room_id';
                // ★確認したSQLに合わせて「room」テーブルを参照
                $stmtR = $pdo->prepare("SELECT room_id FROM room WHERE room_name = ? LIMIT 1");
                $stmtR->execute([$new_val]);
                $found_id = $stmtR->fetchColumn();
                $val_to_save = ($found_id !== false) ? $found_id : null;
            }
            
            // 2. 更新実行
            $sql = "UPDATE subject_in_charges SET $db_field = :val 
                    WHERE subject_id = :sid AND course_id = :cid";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':val' => $val_to_save, ':sid' => $subject_id, ':cid' => $course_id]);
            
            echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
            exit;
        } 
        
        // --- コース追加 ---
        // --- コース追加 ---
        elseif ($action === 'add_course') {
            $course_key = $_POST['course_key'] ?? '';
            if (!isset($courseInfo[$course_key])) throw new Exception("無効なコースです。");
            $target = $courseInfo[$course_key];

            // エラーの原因：SQLに course_key を含めてはいけません。
            // course_id, grade, subject_id の3つを正しく指定します。
            $sql = "INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) 
                    VALUES (?, ?, ?, 0, NULL)
                    ON DUPLICATE KEY UPDATE subject_id = subject_id";
            
            $pdo->prepare($sql)->execute([
                $target['id'],    // courseInfoの 'id' (例: 7)
                $target['grade'], // courseInfoの 'grade' (例: 1)
                $subject_id       // subjectsテーブルから取得したID
            ]);

            echo json_encode(['success' => true]);
            exit;
        }
        elseif ($action === 'remove_course') {
            $course_key = $_POST['course_key'] ?? '';
            if (!isset($courseInfo[$course_key])) throw new Exception("無効なコースです。");
            $target_course_id = $courseInfo[$course_key]['id'];

            // subject_in_charges から該当する科目のレコードを削除
            $sql = "DELETE FROM subject_in_charges 
                    WHERE subject_id = :sid AND course_id = :cid";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':sid' => $subject_id,
                ':cid' => $target_course_id
            ]);

            echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
            exit;
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}