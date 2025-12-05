<?
//セッション開始
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. 削除対象の学生IDの配列 (JavaScriptで 'delete_student_id[]' と定義)
    $delete_student_id = $_POST['delete_student_id'] ?? []; 
    
    
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

    // POSTで受け取った受け取ったdelete_student_idｗも元にテーブル内の学生情報を削除するSQLクエリ
    try {
        $pdo = new PDO($dsn, $user_name, $user_pass, $options);
        
        $student_delete_sql = ("DELETE FROM test_student WHERE student_id = ?;");
        $stmt = $pdo->prepare($student_delete_sql);
        foreach ($delete_student_id as $student_id) {
            $stmt->execute([$student_id]);
        }
    } catch (PDOException $e) {
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }


    if (isset($_POST['course_id']) && isset($_POST['current_year'])) {
        // コースIDを取得
        $received_course_id = $_POST['course_id'];
        // 年度の取得
        $received_current_year = $_POST['current_year'];
    } else {
        // データ受信に失敗した場合
        $received_course_id = 1; // デフォルト値を設定（例: 1）
    
        $received_current_year = date("Y"); 
        
        // $received_current_year の下2桁を取得
        $received_current_year = substr($received_current_year, -2);
    }

    //コース情報取得SQLクエリ
    $course_sql = ("SELECT * FROM course;");
    //テストstudentに格納されている学生情報の取得
    $student_sql = ("SELECT * FROM test_student WHERE course_id = ?;");

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

    // ★ student_addition.php にリダイレクトして処理を終了
    header("Location: ../../../public/teacher/student_account_edit/student_delete.php");
    exit(); // リダイレクト後は必ず処理を終了
}
else {
    $received_course_id = 1; // デフォルト値を設定（例: 1）

    $received_current_year = date("Y"); 
    
    // $received_current_year の下2桁を取得
    $received_current_year = substr($received_current_year, -2);

        
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

    //コース情報取得SQLクエリ
    $course_sql = ("SELECT * FROM course;");
    //テストstudentに格納されている学生情報の取得
    $student_sql = ("SELECT * FROM test_student WHERE course_id = ?;");

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

    // ★ student_addition.php にリダイレクトして処理を終了
    header("Location: ../../../public/teacher/student_account_edit/student_delete.php");
    exit();
}
?>