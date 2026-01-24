<?php
$host = 'localhost';
$dbname = 'icc_smart_campus';
$user = 'root'; 
$pass = 'root'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $teacher_id = 1; 

    // --- 1. 保存・削除処理 (POST) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $date   = $input['date'];
            $period = (int)filter_var($input['slot'], FILTER_SANITIZE_NUMBER_INT);
            $tid    = $teacher_id;

            // ★追加箇所：削除モードの判定
            if (isset($input['mode']) && $input['mode'] === 'delete') {
                try {
                    $pdo->beginTransaction();

                    // 1. 持ち物データを削除
                    $sql_del_bo = "DELETE FROM bring_object WHERE lesson_date = :ldate AND period = :period AND teacher_id = :tid";
                    $pdo->prepare($sql_del_bo)->execute([':ldate' => $date, ':period' => $period, ':tid' => $tid]);

                    // 2. 授業詳細データを削除
                    $sql_del_cd = "DELETE FROM class_detail WHERE lesson_date = :ldate AND period = :period AND teacher_id = :tid";
                    $pdo->prepare($sql_del_cd)->execute([':ldate' => $date, ':period' => $period, ':tid' => $tid]);

                    $pdo->commit();
                    echo json_encode(['success' => true, 'message' => 'Deleted']);
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    echo json_encode(['error' => $e->getMessage()]);
                    exit;
                }
            }

            // --- 以下、既存の保存処理 ---
            $content    = $input['content'];
            $belongings = $input['belongings'];
            $status     = $input['status'];

            try {
                $pdo->beginTransaction();

                $sql_detail = "INSERT INTO class_detail (lesson_date, period, content, status, teacher_id) 
                               VALUES (:ldate, :period, :content, :status, :tid)
                               ON DUPLICATE KEY UPDATE content = :content, status = :status";
                $stmt = $pdo->prepare($sql_detail);
                $stmt->execute([
                    ':ldate'   => $date, 
                    ':period'  => $period, 
                    ':content' => $content, 
                    ':status'  => $status, 
                    ':tid'     => $tid
                ]);

                $sql_del = "DELETE FROM bring_object WHERE lesson_date = :ldate AND period = :period AND teacher_id = :tid";
                $pdo->prepare($sql_del)->execute([':ldate' => $date, ':period' => $period, ':tid' => $tid]);

                if (!empty($belongings)) {
                    $sql_bring = "INSERT INTO bring_object (lesson_date, period, object_name, teacher_id) VALUES (:ldate, :period, :obj, :tid)";
                    $pdo->prepare($sql_bring)->execute([
                        ':ldate'  => $date, 
                        ':period' => $period, 
                        ':obj'    => $belongings, 
                        ':tid'    => $tid
                    ]);
                }

                $pdo->commit();
                echo json_encode(['success' => true]);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['error' => $e->getMessage()]);
                exit;
            }
        }
    }

    // --- 2. データ取得 (GET) ---
    $sql_timetable = "
        SELECT 
            s.subject_name, 
            c.course_name, 
            sic.grade,
            td.day_of_week, 
            td.period
        FROM subject_in_charges sic
        JOIN subjects s ON sic.subject_id = s.subject_id
        JOIN course c ON sic.course_id = c.course_id
        LEFT JOIN timetables tm ON sic.course_id = tm.course_id
        LEFT JOIN timetable_details td ON tm.timetable_id = td.timetable_id AND sic.subject_id = td.subject_id
        WHERE sic.teacher_id = :tid";
    
    $stmt1 = $pdo->prepare($sql_timetable);
    $stmt1->execute([':tid' => $teacher_id]);
    $timetable = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    $sql_saved = "SELECT cd.lesson_date, cd.period, cd.content, cd.status, bo.object_name 
                  FROM class_detail cd
                  LEFT JOIN bring_object bo ON (cd.lesson_date = bo.lesson_date AND cd.period = bo.period AND cd.teacher_id = bo.teacher_id)
                  WHERE cd.teacher_id = :tid";
    
    $stmt2 = $pdo->prepare($sql_saved);
    $stmt2->execute([':tid' => $teacher_id]);
    $saved_details = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'timetable' => $timetable,
        'saved_details' => $saved_details
    ]);

} catch (PDOException $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>