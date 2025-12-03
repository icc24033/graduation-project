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

//test_studentテーブルから学生情報を取得するSQL
$student_sql = ("SELECT * FROM test_student WHERE course_id = ?"); 

//test_studentに格納されているcourse_idとcourse_nameの変更
$update_course_sql = ("UPDATE test_student SET course_id = ?, course_name = ? WHERE student_id = ?");
//コース情報を取得するSQL
$course_select_sql = ("SELECT * FROM course");

try {
    //データベース接続
    $pdo = new PDO($dsn, $user_name, $user_pass, $options);
    $stmt_course = $pdo->query($course_select_sql);
    $courses = $stmt_course->fetchAll(); // ここで取得されるのは連想配列の配列

    foreach ($courses as $selected_course_id => $student_id) {


    }

}

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

?>