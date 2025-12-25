<?
//セッション開始
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. 削除対象の学生IDの配列 (JavaScriptで 'delete_student_id[]' と定義)
    $delete_student_id = $_POST['delete_student_id'] ?? []; 
    
    

    // POSTで受け取った受け取ったdelete_student_idｗも元にテーブル内の学生情報を削除するSQLクエリ
    try {
        // RepositoryFactoryを使用してPDOインスタンスを取得
        require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
        $pdo = RepositoryFactory::getPdo();

        
        //studentテーブルの削除
        $student_delete_sql = ("DELETE FROM student WHERE student_id = ?;");
        $stmt = $pdo->prepare($student_delete_sql);

        //student_loginテーブルの削除
        $student_login_delete_sql = ("DELETE FROM student_login_table WHERE student_id = ?;");
        $stmt_login = $pdo->prepare($student_login_delete_sql);


        foreach ($delete_student_id as $student_id) {
            // delete_student_idのstudent_idに対応するレコードを削除
            $stmt_login->execute([$student_id]);

            // studentテーブルの削除を実行
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
    //studentテーブルに格納されている学生情報の取得
    $student_sql = ("SELECT 
                        S.student_id,
                        S.student_name,
                        S.course_id,
                        C.course_name 
                    FROM
                        student AS S 
                    INNER JOIN 
                        course AS C 
                    ON 
                        S.course_id = C.course_id 
                    WHERE 
                        S.course_id = ?;"
                    );

    $_SESSION['student_account'] = [
        'success' => true,
        'before' => 'teacher_home',
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

    //コース情報取得SQLクエリ
    $course_sql = ("SELECT * FROM course;");
    //テストstudentに格納されている学生情報の取得
    $student_sql = ("SELECT * FROM test_student WHERE course_id = ?;");

    $_SESSION['student_account'] = [
        'success' => true,
        'before' => 'teacher_home',
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