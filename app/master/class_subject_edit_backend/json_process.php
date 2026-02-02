<?php
// json_process.php
// セッション開始
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
require_once __DIR__ . '/../../services/master/ClassSubjectEditService.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'redirect' => 'connection_error.html']);
    exit;
}

// ★ CSRFトークンの検証
if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'redirect' => 'connection_error.html']);
    exit;
}

try {
    $pdo = RepositoryFactory::getPdo();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'redirect' => 'connection_error.html']);
    exit;
}

$service = new ClassSubjectEditService();
$courseInfo = $service->getCourseInfoMaster();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $title = $_POST['subject_name'] ?? $_POST['title'] ?? '';

    try {
        $stmtSub = $pdo->prepare("SELECT subject_id FROM subjects WHERE subject_name = ? LIMIT 1");
        $stmtSub->execute([$title]);
        $subject_id = $stmtSub->fetchColumn();

        if (!$subject_id) throw new Exception("科目「{$title}」が見つかりません。");

        $stmtRoom = $pdo->prepare("SELECT room_id FROM subject_in_charges WHERE subject_id = ? AND room_id IS NOT NULL LIMIT 1");
        $stmtRoom->execute([$subject_id]);
        $existing_room_id = $stmtRoom->fetchColumn();

        $stmtTeachers = $pdo->prepare("SELECT DISTINCT teacher_id FROM subject_in_charges WHERE subject_id = ?");
        $stmtTeachers->execute([$subject_id]);
        $existing_teachers = $stmtTeachers->fetchAll(PDO::FETCH_COLUMN);

        if (empty($existing_teachers)) {
            $existing_teachers = [ $_POST['teacher_id'] ?? 0 ];
        }

        if ($action === 'update_field') {
            $field = $_POST['field'] ?? '';
            $value = $_POST['value'] ?? '';
            $grade = $_POST['grade'] ?? '';
            $course_key = $_POST['course_key'] ?? '';
            $mode = $_POST['mode'] ?? 'single';

            // --- 講師の追加 (mode === 'add') ---
            if ($field === 'teacher' && $mode === 'add') {
                $stmtT = $pdo->prepare("SELECT teacher_id FROM teacher WHERE teacher_name = ? LIMIT 1");
                $stmtT->execute([$value]);
                $new_teacher_id = $stmtT->fetchColumn();

                if (!$new_teacher_id) throw new Exception("講師「{$value}」が見つかりません。");

                $stmtTargetCourses = $pdo->prepare("SELECT DISTINCT course_id FROM subject_in_charges WHERE subject_id = ? AND grade = ?");
                $stmtTargetCourses->execute([$subject_id, $grade]);
                $target_course_ids = $stmtTargetCourses->fetchAll(PDO::FETCH_COLUMN);

                foreach ($target_course_ids as $cid) {
                    $stmtFindNull = $pdo->prepare("SELECT subject_in_charge_id FROM subject_in_charges WHERE subject_id = ? AND course_id = ? AND (teacher_id IS NULL OR teacher_id = 0) LIMIT 1");
                    $stmtFindNull->execute([$subject_id, $cid]);
                    $null_row_id = $stmtFindNull->fetchColumn();

                    if ($null_row_id) {
                        $sqlUpd = "UPDATE subject_in_charges SET teacher_id = ? WHERE subject_in_charge_id = ?";
                        $pdo->prepare($sqlUpd)->execute([$new_teacher_id, $null_row_id]);
                    } else {
                        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM subject_in_charges WHERE subject_id = ? AND course_id = ? AND teacher_id = ?");
                        $stmtCheck->execute([$subject_id, $cid, $new_teacher_id]);

                        if ($stmtCheck->fetchColumn() == 0) {
                            $sqlIns = "INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) VALUES (?, ?, ?, ?, ?)";
                            $pdo->prepare($sqlIns)->execute([
                                $cid, $grade, $subject_id, $new_teacher_id, $existing_room_id ?: null 
                            ]);
                        }
                    }
                }
                echo json_encode(['success' => true]);
                exit;
            }

            // --- 特定の講師のみ削除 (mode === 'remove_single') ---
            if ($field === 'teacher' && $mode === 'remove_single') {
                $tId = $_POST['teacher_id'] ?? null;
                if (!$tId) throw new Exception("講師IDが指定されていません。");

                $sqlDel = "DELETE FROM subject_in_charges WHERE subject_id = ? AND grade = ? AND teacher_id = ?";
                $pdo->prepare($sqlDel)->execute([$subject_id, $grade, $tId]);
                echo json_encode(['success' => true]);
                exit;
            }

            // --- 共通のコース特定ロジック ---
            if (!isset($courseInfo[$course_key])) throw new Exception("無効なコースです。");
            $target_course_id = $courseInfo[$course_key]['id'];

            $dbField = ($field === 'teacher') ? 'teacher_id' : (($field === 'room') ? 'room_id' : '');
            if (!$dbField) throw new Exception("無効なフィールドです。");
            $finalValue = ($value === '未設定' || $value === '' || $value === '0') ? null : $value;

            if ($field === 'room') {
                $sql = "UPDATE subject_in_charges SET room_id = ? WHERE subject_id = ? AND grade = ?";
                $pdo->prepare($sql)->execute([$finalValue, $subject_id, $grade]);
            } else {
                // --- 【修正ポイント】講師の全員解除・上書き ---
                if ($mode === 'overwrite') {
                    // 学年全体の各コースについて処理
                    $stmtC = $pdo->prepare("SELECT DISTINCT course_id FROM subject_in_charges WHERE subject_id = ? AND grade = ?");
                    $stmtC->execute([$subject_id, $grade]);
                    $cids = $stmtC->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($cids as $cid) {
                        // 1. まずそのコースの該当科目のレコードをすべて削除
                        $pdo->prepare("DELETE FROM subject_in_charges WHERE subject_id = ? AND course_id = ?")->execute([$subject_id, $cid]);
                        // 2. 「未設定」状態のレコードを1件だけ作り直す（重複防止）
                        $sqlIns = "INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) VALUES (?, ?, ?, ?, ?)";
                        $pdo->prepare($sqlIns)->execute([$cid, $grade, $subject_id, $finalValue, $existing_room_id ?: null]);
                    }
                } else {
                    // 特定のコースのみ解除する場合も、一度消して1件にする
                    $pdo->prepare("DELETE FROM subject_in_charges WHERE subject_id = ? AND course_id = ?")->execute([$subject_id, $target_course_id]);
                    $sqlIns = "INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) VALUES (?, ?, ?, ?, ?)";
                    $pdo->prepare($sqlIns)->execute([$target_course_id, $grade, $subject_id, $finalValue, $existing_room_id ?: null]);
                }
            }
            echo json_encode(['success' => true]);
            exit;
        }

        elseif ($action === 'add_course') {
            $course_key = $_POST['course_key'] ?? '';
            $targets = [];
            if (isset($courseInfo[$course_key])) {
                $targets[] = $courseInfo[$course_key];
            } elseif ($course_key === 'all') {
                $targets = $courseInfo;
            } elseif (strpos($course_key, '_all') !== false) {
                $g = (int)$course_key;
                foreach ($courseInfo as $info) {
                    if ((int)$info['grade'] === $g) $targets[] = $info;
                }
            }
            foreach ($targets as $course) {
                $target_course_id = $course['id'];
                $target_grade = $course['grade'];
                foreach ($existing_teachers as $t_id) {
                    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM subject_in_charges WHERE subject_id = ? AND course_id = ? AND (teacher_id = ? OR (teacher_id IS NULL AND ? = 0))");
                    $stmtCheck->execute([$subject_id, $target_course_id, $t_id, $t_id]);
                    if ($stmtCheck->fetchColumn() == 0) {
                        $sqlIns = "INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) VALUES (?, ?, ?, ?, ?)";
                        $pdo->prepare($sqlIns)->execute([ $target_course_id, $target_grade, $subject_id, $t_id ?: null, $existing_room_id ?: null ]);
                    }
                }
            }
            echo json_encode(['success' => true]);
            exit;
        }

        elseif ($action === 'remove_course') {
            $course_key = $_POST['course_key'] ?? '';
            $targets = [];
            if (isset($courseInfo[$course_key])) {
                $targets[] = $courseInfo[$course_key];
            } elseif ($course_key === 'all') {
                $targets = $courseInfo;
            } elseif (strpos($course_key, '_all') !== false) {
                $g = (int)$course_key;
                foreach ($courseInfo as $info) {
                    if ((int)$info['grade'] === $g) $targets[] = $info;
                }
            }
            foreach ($targets as $course) {
                $sql = "DELETE FROM subject_in_charges WHERE subject_id = :sid AND course_id = :cid";
                $pdo->prepare($sql)->execute([':sid' => $subject_id, ':cid' => $course['id']]);
            }
            echo json_encode(['success' => true]);
            exit;
        }
    } catch (Exception $e) {
        http_response_code(500);
        // メッセージ内のHTML特殊文字を無効化して返す
        $safeError = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        echo json_encode(['success' => false, 'error' => $safeError]);
        exit;
    }
}
echo json_encode(['success' => false, 'error' => 'Invalid Request']);