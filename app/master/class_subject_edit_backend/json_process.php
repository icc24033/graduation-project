<?php
// test.php
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

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
            $grade = $_POST['grade'] ?? null;

            if (!$course_id) throw new Exception("コース情報が正しくありません。");

            // --- 講師(teacher)の処理 ---
            if ($field === 'teacher') {
                $stmtT = $pdo->prepare("SELECT teacher_id FROM teacher WHERE teacher_name = ? LIMIT 1");
                $stmtT->execute([$new_val]);
                $teacher_id = $stmtT->fetchColumn();

                // 「全員解除」の場合
                if ($new_val === '未設定' || $teacher_id === false) {
                    $pdo->beginTransaction();
                    try {
                        $pdo->prepare("DELETE FROM subject_in_charges WHERE subject_id = :sid AND course_id = :cid")
                            ->execute([':sid' => $subject_id, ':cid' => $course_id]);
                        $pdo->prepare("INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) VALUES (?, ?, ?, 0, NULL)")
                            ->execute([$course_id, $grade, $subject_id]);
                        $pdo->commit();
                        echo json_encode(['success' => true]);
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                    exit;
                }

                // 講師を追加する場合
                $stmtR = $pdo->prepare("SELECT room_id FROM subject_in_charges WHERE subject_id = ? AND course_id = ? LIMIT 1");
                $stmtR->execute([$subject_id, $course_id]);
                $current_room_id = $stmtR->fetchColumn() ?: null;

                $pdo->prepare("DELETE FROM subject_in_charges WHERE subject_id = :sid AND course_id = :cid AND teacher_id = 0")
                    ->execute([':sid' => $subject_id, ':cid' => $course_id]);

                $sql = "INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) 
                        VALUES (:cid, :grade, :sid, :tid, :rid)
                        ON DUPLICATE KEY UPDATE room_id = VALUES(room_id)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':tid'   => $teacher_id,
                    ':sid'   => $subject_id,
                    ':cid'   => $course_id,
                    ':grade' => $grade,
                    ':rid'   => $current_room_id
                ]);

                echo json_encode(['success' => true]);
                exit;

            // --- 教室(room)の処理 ---
            } else if ($field === 'room') {
                $stmtR = $pdo->prepare("SELECT room_id FROM room WHERE room_name = ? LIMIT 1");
                $stmtR->execute([$new_val]);
                $found_id = $stmtR->fetchColumn();
                $room_id = ($found_id !== false) ? $found_id : null;

                // 教室は担当講師全員分をまとめて更新
                $sql = "UPDATE subject_in_charges SET room_id = :rid 
                        WHERE subject_id = :sid AND course_id = :cid";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':rid' => $room_id, ':sid' => $subject_id, ':cid' => $course_id]);
                
                echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
                exit;
            }
        } 
        
        // --- コース追加(add_course) ---
        elseif ($action === 'add_course') {
            $course_key = $_POST['course_key'] ?? '';
            if (!isset($courseInfo[$course_key])) throw new Exception("無効なコースです。");
            $target = $courseInfo[$course_key];

            $sql = "INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) 
                    VALUES (?, ?, ?, 0, NULL)
                    ON DUPLICATE KEY UPDATE subject_id = subject_id";
            $pdo->prepare($sql)->execute([$target['id'], $target['grade'], $subject_id]);

            echo json_encode(['success' => true]);
            exit;
        }

        // --- コース削除(remove_course) ---
        elseif ($action === 'remove_course') {
            $course_key = $_POST['course_key'] ?? '';
            if (!isset($courseInfo[$course_key])) throw new Exception("無効なコースです。");
            $target_course_id = $courseInfo[$course_key]['id'];

            $sql = "DELETE FROM subject_in_charges WHERE subject_id = :sid AND course_id = :cid";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':sid' => $subject_id, ':cid' => $target_course_id]);

            echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
            exit;
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}