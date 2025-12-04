<?

// POSTで受けとった値を変数に格納
$selected_student = $_POST['students'] ?? [];

//データベース接続情報
$host = 'localhost';
$db_name = 'icc_smart_campus';
$user_name = 'root';
$user_pass = 'root';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

//UPDATE test_student SET course_id = 1, course_name = "システムデザインコース" WHERE student_id = 25004;

//test_studentに格納されているcourse_idとcourse_nameの変更
$update_course_sql = ("UPDATE test_student SET course_id = ?, course_name = ? WHERE student_id = ?");
//コース情報を取得するSQL
$course_select_sql = ("SELECT * FROM course");

try {
    //データベース接続
    $pdo = new PDO($dsn, $user_name, $user_pass, $options);
    $stmt_course = $pdo->query($course_select_sql);
    $all_courses = $stmt_course->fetchAll(); // コースIDとコース名を取得

    // コースIDをキー、コース名を値とする連想配列を作成
    $courses = [];
    foreach ($all_courses as $course) {
        $courses[$course['course_id']] = $course;
    }

    $stmt_update = $pdo->prepare($update_course_sql);

    //var_dump($courses);


    foreach ($selected_student as $student_id => $course_id) {
        // update_course_sqlのWHERE句のstudent_idに対応するレコードのcourse_idとcourse_nameを更新

        $course_id = (int)$course_id;

        var_dump($course_id, $courses[$course_id]['course_name'], $student_id);

        $stmt_update->execute([$course_id, $courses[$course_id]['course_name'], $student_id]);
    }

}
catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

echo "学生のコース情報を更新しました。";
/*
$_SESSION['student_account'] = [
    'success' => true,
    'before' => 'teacher_home',
    'database_connection' => $dsn,
    'database_user_name' => $user_name,
    'database_user_pass' => $user_pass,
    'database_options' => $options,
    'course_sql' => $course_sql,
    'course_id' => $received_course_id,
    'student_sql' => $student_sql,
    'current_year' => $received_current_year
];
*/
?>