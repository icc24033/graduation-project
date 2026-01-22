<?php
$host = 'localhost';
$dbname = 'icc_smart_campus';
$user = 'root'; 
$pass = 'root'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $teacher_id = 24026; // ここでは例として1を使用。実際にはセッションなどから取得。

    // 必要な情報を各テーブルから結合して取得
    $sql = "SELECT 
                s.subject_name, 
                s.subject_id,
                td.day_of_week,
                td.period,
                sic.grade,
                c.course_name
            FROM 
                timetable_detail_teachers tdt
            JOIN 
                timetable_details td ON tdt.detail_id = td.detail_id
            JOIN 
                subjects s ON td.subject_id = s.subject_id
            JOIN 
                timetables tm ON td.timetable_id = tm.timetable_id
            JOIN 
                course c ON tm.course_id = c.course_id
            LEFT JOIN 
                subject_in_charges sic ON (
                    sic.course_id = c.course_id AND 
                    sic.subject_id = s.subject_id AND 
                    sic.teacher_id = tdt.teacher_id
                )
            WHERE 
                tdt.teacher_id = :teacher_id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':teacher_id', $teacher_id, PDO::PARAM_INT);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($results);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>