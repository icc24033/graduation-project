<?php
// json_process.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
require_once __DIR__ . '/../../services/master/ClassSubjectEditService.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $pdo = RepositoryFactory::getPdo();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB接続失敗: ' . $e->getMessage()]);
    exit;
}

// $courseInfo をサービスから取得
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

        // --- フィールド更新 (講師・教室) ---
        if ($action === 'update_field') {
            $field = $_POST['field'] ?? '';
            $value = $_POST['value'] ?? '';
            $mode  = $_POST['mode'] ?? 'overwrite';
            $course_key = $_POST['course_key'] ?? '';
            
            if (!isset($courseInfo[$course_key])) throw new Exception("無効なコースです。");
            
            $target_course_id = $courseInfo[$course_key]['id'];
            $target_grade     = $courseInfo[$course_key]['grade']; // 学年を取得しておく

            // --- 講師(teacher)の処理 ---
            if ($field === 'teacher') {
                // A. 【追加】個別講師削除モード
                if ($mode === 'remove_single') {
                    $target_teacher_id = $_POST['teacher_id'] ?? 0;

                    $sqlDel = "DELETE FROM subject_in_charges 
                               WHERE subject_id = ? AND course_id = ? AND teacher_id = ?";
                    $pdo->prepare($sqlDel)->execute([$subject_id, $target_course_id, $target_teacher_id]);

                    // 削除後、0人になったら「未設定」を補充
                    $sqlCheck = "SELECT COUNT(*) FROM subject_in_charges WHERE subject_id = ? AND course_id = ?";
                    $stmtCheck = $pdo->prepare($sqlCheck);
                    $stmtCheck->execute([$subject_id, $target_course_id]);
                    
                    if ($stmtCheck->fetchColumn() == 0) {
                        $pdo->prepare("INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) VALUES (?, ?, ?, NULL, NULL)")
                            ->execute([$target_course_id, $target_grade, $subject_id]);
                    }

                    echo json_encode(['success' => true]);
                    exit;
                }

                // --- 以降、通常の更新・追加処理 ---
                $stmtT = $pdo->prepare("SELECT teacher_id FROM teacher WHERE teacher_name = ? LIMIT 1");
                $stmtT->execute([$value]); // $value を使用
                $teacher_id = $stmtT->fetchColumn();

                // B. 「全員解除」の場合
                if ($value === '未設定' || $teacher_id === false) {
                    $pdo->beginTransaction();
                    try {
                        $pdo->prepare("DELETE FROM subject_in_charges WHERE subject_id = :sid AND course_id = :cid")
                            ->execute([':sid' => $subject_id, ':cid' => $target_course_id]);
                        $pdo->prepare("INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) VALUES (?, ?, ?, NULL, NULL)")
                            ->execute([$target_course_id, $target_grade, $subject_id]);
                        $pdo->commit();
                        echo json_encode(['success' => true]);
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                    exit;
                }

                // C. 講師を追加・上書きする場合
                $sqlFindEmpty = "SELECT subject_in_charge_id FROM subject_in_charges 
                                 WHERE subject_id = ? AND course_id = ? 
                                 AND (teacher_id IS NULL OR teacher_id = 0) 
                                 LIMIT 1";
                $stmtEmpty = $pdo->prepare($sqlFindEmpty);
                $stmtEmpty->execute([$subject_id, $target_course_id]);
                $emptyRow = $stmtEmpty->fetch();

                if ($emptyRow) {
                    $sqlUpdate = "UPDATE subject_in_charges SET teacher_id = ? WHERE subject_in_charge_id = ?";
                    $pdo->prepare($sqlUpdate)->execute([$teacher_id, $emptyRow['subject_in_charge_id']]);
                } else {
                    $sqlCheck = "SELECT COUNT(*) FROM subject_in_charges 
                                 WHERE subject_id = ? AND course_id = ? AND teacher_id = ?";
                    $stmtCheck = $pdo->prepare($sqlCheck);
                    $stmtCheck->execute([$subject_id, $target_course_id, $teacher_id]);
    
                    if ($stmtCheck->fetchColumn() == 0) {
                        $sqlInsert = "INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) 
                                      VALUES (?, ?, ?, ?, NULL)";
                        $pdo->prepare($sqlInsert)->execute([$target_course_id, $target_grade, $subject_id, $teacher_id]);
                    }
                }

                echo json_encode(['success' => true]);
                exit;

            // --- 教室(room)の処理 ---
            } else if ($field === 'room') {
                // $value には room_id (数値) が入ってくるので、そのまま利用する
                // 「未設定」の場合は null にする
                $room_id = (is_numeric($value) && $value > 0) ? (int)$value : null;
            
                // 現在のカード（科目）に関連するすべてのコースを更新したい場合は
                // JSから送られてくる course_key ではなく、subject_id をキーに一括更新するのがスムーズです
                $sql = "UPDATE subject_in_charges 
                        SET room_id = :rid 
                        WHERE subject_id = :sid";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':rid' => $room_id,
                    ':sid' => $subject_id
                ]);
                
                echo json_encode(['success' => true]);
                exit;
            }
        }
        
        // --- コース追加(add_course) ---
        elseif ($action === 'add_course') {
            $course_key = $_POST['course_key'] ?? ''; 
            $teacher_id = (int)($_POST['teacher_id'] ?? 0); 
            $target = $courseInfo[$course_key];
            $target_course_id = $target['id'];
            $target_grade = $target['grade'];

            $sqlFindEmpty = "SELECT subject_in_charge_id FROM subject_in_charges 
                             WHERE subject_id = ? AND course_id = ? 
                             AND (teacher_id IS NULL OR teacher_id = 0) 
                             LIMIT 1";
            $stmtEmpty = $pdo->prepare($sqlFindEmpty);
            $stmtEmpty->execute([$subject_id, $target_course_id]);
            $emptyRow = $stmtEmpty->fetch();

            if ($emptyRow) {
                $pdo->prepare("UPDATE subject_in_charges SET teacher_id = ? WHERE subject_in_charge_id = ?")
                    ->execute([$teacher_id, $emptyRow['subject_in_charge_id']]);
            } else {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM subject_in_charges WHERE subject_id = ? AND course_id = ? AND teacher_id = ?");
                $stmtCheck->execute([$subject_id, $target_course_id, $teacher_id]);
                if ($stmtCheck->fetchColumn() == 0) {
                    $pdo->prepare("INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) VALUES (?, ?, ?, ?, NULL)")
                        ->execute([$target_course_id, $target_grade, $subject_id, $teacher_id]);
                }
            }
            echo json_encode(['success' => true]);
            exit;
        }

        // --- コース削除(remove_course) ---
        elseif ($action === 'remove_course') {
            $course_key = $_POST['course_key'] ?? '';
            if (!isset($courseInfo[$course_key])) throw new Exception("無効なコースです。");
            $target_course_id = $courseInfo[$course_key]['id'];

            $sql = "DELETE FROM subject_in_charges WHERE subject_id = :sid AND course_id = :cid";
            $pdo->prepare($sql)->execute([':sid' => $subject_id, ':cid' => $target_course_id]);

            echo json_encode(['success' => true]);
            exit;
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}