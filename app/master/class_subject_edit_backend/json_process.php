<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// test.php
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
require_once __DIR__ . '/../../services/master/ClassSubjectEditService.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $pdo = RepositoryFactory::getPdo();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB接続失敗: ' . $e->getMessage()]);
    exit;
}

// $courseInfo をサービスから取得（または共通化）
$service = new ClassSubjectEditService();
$courseInfo = $service->getCourseInfoMaster();

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
        // json_process.php の add_course アクション部分
        elseif ($action === 'add_course') {
            $course_id = $_POST['course_key'] ?? ''; 
            $teacher_id = (int)($_POST['teacher_id'] ?? 0); 
            
            $target = $courseInfo[$course_id];
            $target_course_id = $target['id'];

            // 【重要】「本当に何も入っていない枠」を探す
            // teacher_id が NULL、もしくは空文字のレコードを1件探す
            $sqlFindEmpty = "SELECT subject_in_charge_id FROM subject_in_charges 
                            WHERE subject_id = ? AND course_id = ? 
                            AND (teacher_id IS NULL) 
                            LIMIT 1";
            $stmtEmpty = $pdo->prepare($sqlFindEmpty);
            $stmtEmpty->execute([$subject_id, $target_course_id]);
            $emptyRow = $stmtEmpty->fetch();

            if ($emptyRow) {
                // --- A. 空席(NULL)を更新する ---
                $sqlUpdate = "UPDATE subject_in_charges SET teacher_id = ? 
                            WHERE subject_in_charge_id = ?";
                $pdo->prepare($sqlUpdate)->execute([$teacher_id, $emptyRow['subject_in_charge_id']]);
            } else {
                // --- B. 空席がない場合、重複チェックして新規追加 ---
                $sqlCheck = "SELECT COUNT(*) FROM subject_in_charges 
                            WHERE subject_id = ? AND course_id = ? AND teacher_id = ?";
                $stmtCheck = $pdo->prepare($sqlCheck);
                $stmtCheck->execute([$subject_id, $target_course_id, $teacher_id]);

                if ($stmtCheck->fetchColumn() == 0) {
                    $sqlInsert = "INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) 
                                VALUES (?, ?, ?, ?, NULL)";
                    $pdo->prepare($sqlInsert)->execute([$target_course_id, $target['grade'], $subject_id, $teacher_id]);
                }
            }

            echo json_encode(['success' => true]);
            exit;
        }

        // --- コース削除(remove_course) ---
        elseif ($action === 'remove_course') {
            $course_id = $_POST['course_key'] ?? ''; // course_key から course_id に変数名を合わせる
            if (!isset($courseInfo[$course_id])) throw new Exception("無効なコースです。");
            $target_course_id = $courseInfo[$course_id]['id']; // $courseInfo[$course_id] を参照

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