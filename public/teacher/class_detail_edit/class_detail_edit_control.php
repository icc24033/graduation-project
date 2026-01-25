<?php
// class_detail_edit_control.php

$host = 'localhost';
$dbname = 'icc_smart_campus';
$user = 'root'; 
$pass = 'root'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $teacher_id = 0;

    // --- 1. 保存・削除処理 (POST) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $date   = $input['date'];
            $period = (int)filter_var($input['slot'], FILTER_SANITIZE_NUMBER_INT);
            $tid    = $teacher_id;

            // --- 削除処理 ---
            if (isset($input['mode']) && $input['mode'] === 'delete') {
                try {
                    $pdo->beginTransaction();

                    // 【修正】timetable_details の更新（unlink）は不要になったので削除

                    // 実データの削除のみ実行
                    $sql_del_bo = "DELETE FROM bring_object WHERE lesson_date = :ldate AND period = :period AND teacher_id = :tid";
                    $pdo->prepare($sql_del_bo)->execute([':ldate' => $date, ':period' => $period, ':tid' => $tid]);

                    $sql_del_cd = "DELETE FROM class_detail WHERE lesson_date = :ldate AND period = :period AND teacher_id = :tid";
                    $pdo->prepare($sql_del_cd)->execute([':ldate' => $date, ':period' => $period, ':tid' => $tid]);

                    $pdo->commit();
                    echo json_encode(['success' => true]);
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    echo json_encode(['error' => $e->getMessage()]);
                    exit;
                }
            }

            // --- 保存・更新処理 ---
            $content    = $input['content'];
            $belongings = $input['belongings'];
            $status     = $input['status'];

            try {
                $pdo->beginTransaction();

                // ① 該当する時間割の枠 (detail_id) を特定する
                $day_of_week = date('w', strtotime($date));
                if($day_of_week == 0) $day_of_week = 7; 

                $sql_find_detail = "
                    SELECT td.detail_id 
                    FROM timetable_details td
                    JOIN timetables tm ON td.timetable_id = tm.timetable_id
                    JOIN subject_in_charges sic ON tm.course_id = sic.course_id AND td.subject_id = sic.subject_id
                    WHERE sic.teacher_id = :tid AND td.day_of_week = :dow AND td.period = :period
                    LIMIT 1";
                
                $stmt_find = $pdo->prepare($sql_find_detail);
                $stmt_find->execute([':tid' => $tid, ':dow' => $day_of_week, ':period' => $period]);
                $found_detail_id = $stmt_find->fetchColumn() ?: NULL;

                // ② 持ち物データの保存
                $sql_del_bo = "DELETE FROM bring_object WHERE lesson_date = :ldate AND period = :period AND teacher_id = :tid";
                $pdo->prepare($sql_del_bo)->execute([':ldate' => $date, ':period' => $period, ':tid' => $tid]);

                $new_bo_id = NULL;
                if (!empty($belongings)) {
                    $sql_bring = "INSERT INTO bring_object (lesson_date, period, object_name, teacher_id, detail_id) 
                                  VALUES (:ldate, :period, :obj, :tid, :did)";
                    $stmt_bo = $pdo->prepare($sql_bring);
                    $stmt_bo->execute([
                        ':ldate' => $date, ':period' => $period, ':obj' => $belongings, 
                        ':tid' => $tid, ':did' => $found_detail_id
                    ]);
                    $new_bo_id = $pdo->lastInsertId();
                }

                // ③ 授業詳細の保存（bring_object_id と detail_id を持たせる）
                $sql_detail = "INSERT INTO class_detail (lesson_date, period, content, status, teacher_id, bring_object_id, detail_id) 
                               VALUES (:ldate, :period, :content, :status, :tid, :bid, :did)
                               ON DUPLICATE KEY UPDATE 
                                 content = :content, status = :status, bring_object_id = :bid, detail_id = :did";
                
                $stmt_cd = $pdo->prepare($sql_detail);
                $stmt_cd->execute([
                    ':ldate'   => $date, ':period' => $period, ':content' => $content, 
                    ':status'  => $status, ':tid' => $tid, ':bid' => $new_bo_id, ':did' => $found_detail_id
                ]);

                // 【修正】timetable_details 側への書き戻し（逆方向の紐付け）は不要になったので削除

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
    // 時間割の基本情報を取得
    $sql_timetable = "
        SELECT 
            s.subject_name, 
            c.course_name, 
            sic.grade,
            td.day_of_week, 
            td.period,
            td.detail_id
        FROM subject_in_charges sic
        JOIN subjects s ON sic.subject_id = s.subject_id
        JOIN course c ON sic.course_id = c.course_id
        LEFT JOIN timetables tm ON sic.course_id = tm.course_id
        LEFT JOIN timetable_details td ON tm.timetable_id = td.timetable_id AND sic.subject_id = td.subject_id
        WHERE sic.teacher_id = :tid";
    
    $stmt1 = $pdo->prepare($sql_timetable);
    $stmt1->execute([':tid' => $teacher_id]);
    $timetable = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // 保存済みの詳細情報を取得
    $sql_saved = "SELECT cd.lesson_date, cd.period, cd.content, cd.status, bo.object_name, cd.detail_id
                  FROM class_detail cd
                  LEFT JOIN bring_object bo ON cd.bring_object_id = bo.bring_object_id
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