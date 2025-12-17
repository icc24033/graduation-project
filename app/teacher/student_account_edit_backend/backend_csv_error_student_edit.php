<?php

session_start();

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

//error_student_tableの情報を更新するためのSQL文
$sql_update_error_student = "UPDATE 
                                error_student_table
                             SET 
                                student_id = ?, name = ?, approvalUserAddress = ?, error_id = ?
                             WHERE
                                id = ?;"; 

// csv_tableに格納するsql文
$sql_insert_csv_table = "INSERT INTO 
                            csv_table (student_id, name, approvalUserAddress, course_id) 
                         VALUES 
                            (?, ?, ?, ?);";

// csv_tableに格納したerror_idを削除するsql文
$sql_delete_csv_error_id = "DELETE FROM error_student_table WHERE id = ?;";


try {
    $pdo = new PDO($dsn, $user_name, $user_pass, $options);

    if (isset($_POST['students']) && is_array($_POST['students'])) {
        
        //データ件数を取得する変数
        $insert_count = 0;
        $column_number = 0;
 
        // 現在の年を取得
        $current_year = date('Y');
        // $current_yearの下2桁を取得し、int型に変換
        $current_year = (int)(substr($current_year, -2));
 
        // 現在の月を0埋めなしで取得
        $current_month = (int)date('n');
        if ($current_month < 4) {
            $school_year = [ $current_year, $current_year - 1, $current_year - 2 ];             
        }
        else {
            $school_year = [ $current_year, $current_year - 1 ];
        }

        // $_POST['students'] をループするだけで、更新に必要な全データが取得できる
        foreach ($_POST['students'] as $student_id => $student_data) {
            
            $id = $student_data['id']; // レコードID
            $student_id = $student_data['student_id']; // 学生番号
            $name = $student_data['name']; // 氏名
            $email = $student_data['approvalUserAddress']; // メールアドレス
            $course_id = $student_data['course_id']; // コースID
            
            // 正の整数かどうかを確認
            if (ctype_digit($student_id) === true) {
                $user_id_prefix = floor((int)($student_id) / 1000);
                // 今の年度に対応する学生番号かどうかを判断するフラグ
                $flg = 0;
                foreach ($school_year as $year) {
                    //学年が正規のものか確認
                    if ($year == $user_id_prefix) {
                        $flg = 1;
                        $column_user_id = (int)$student_id;
                        break;
                    }
                }
                if ($flg == 0) {
                    // error_student_tableを更新するSQL文
                    try {
                        $stmt_error_student = $pdo->prepare($sql_update_error_student);
                        //SQL文を実行
                        $error_id = 1001; //学年部分不正エラー
                        $stmt_error_student->execute([$student_id, $name, $email, $error_id, $id]);
                    }
                    catch (PDOException $e) {
                        //エラー処理
                        echo "error_student_tableに格納できませんでした： " . $e->getMessage();
                    }
                    continue;
                }
            }
            // 学生番号が正の整数ではなかった場合
            else {
                //error_student_tableに格納
                try {
                    $stmt_error_student = $pdo->prepare($sql_update_error_student);
                    //SQL文を実行
                    $error_id = 1002; //ユーザーID形式不正エラー
                    $stmt_error_student->execute([$student_id, $name, $email, $error_id, $id]);
                }
                catch (PDOException $e) {
                    //エラー処理
                    echo "error_student_tableに格納できませんでした： " . $e->getMessage();
                }
                continue;
            }
            // 名前の取得
            $column_name = $name;

            // メールアドレスの確認
            if (preg_match("/^icc$column_user_id@isahaya-cc\\.ac\\.jp$/", $email) == true) {
                $column_email = $email;
            } 
            else {
                //error_student_tableに格納
                try {
                    $stmt = $pdo->prepare($sql_update_error_student);
                    //SQL文を実行
                    $error_id = 2001; //メールアドレス形式不正エラー
                    $stmt->execute([$student_id, $name, $email, $error_id, $id]);
                }
                catch (PDOException $e) {
                    //エラー処理
                    echo "error_student_tableに格納できませんでした： " . $e->getMessage();
                }
                continue;
            }
            // csv_tableに格納
            try {
                $stmt_csv = $pdo->prepare($sql_insert_csv_table);
                //SQL文を実行
                $stmt_csv->execute([$student_id, $name, $email, $course_id]);
                $insert_count++;
                // csv_error_tableから該当するcsv_idのレコードを削除
                $stmt_delete_error = $pdo->prepare($sql_delete_csv_error_id);
                $stmt_delete_error->execute([$id]);
            }
            catch (PDOException $e) {
                //エラー処理
                echo "csv_tableに格納できませんでした： " . $e->getMessage();
            }
        }
        // エラー件数を取得 (error_student_tableのレコード数を数える)
        try {
            $sql_error_count = "SELECT COUNT(*) AS error_count FROM error_student_table;";
            $stmt = $pdo->query($sql_error_count);
            $error_result = $stmt->fetch();
            $error_count = $error_result['error_count'];
        }
        catch (PDOException $e) {
            echo "エラー件数の取得に失敗しました: " . $e->getMessage();
        }
        // DB接続を閉じる
        $pdo = null;

        if ($error_count > 0) {
            $error_count_flag = true;
            $csv_error_table_sql = "SELECT * FROM error_student_table;";
        }
        else {
            $error_count_flag = false;
            $csv_error_table_sql = null;
        }
        //csv_tableに格納されている学生情報の取得
        $csv_table_student_sql = ("SELECT * FROM csv_table;");
        
        //コース情報取得SQLクエリ
        $course_sql = ("SELECT * FROM course;");

        $_SESSION['student_account'] = [
            'success' => true,
            'backend' => 'csv_upload',
            'error_csv' => $error_count_flag,
            'before' => 'teacher_home',
            'database_options' => $options, 
            'csv_table_student_sql' => $csv_table_student_sql,
            'course_sql' => $course_sql,
            'csv_error_table_sql' => $csv_error_table_sql
        ];

    }
    else {
        // 不正なアクセスの場合
        $_SESSION['student_account'] = [
            'success' => false,
            'message' => '不正なアクセスです。'
        ];
    }
// ★ CSV_edit.php にリダイレクトして処理を終了
header("Location: ../../../public/teacher/student_account_edit/student_addition.php");
exit(); // リダイレクト後は必ず処理を終了
} 
catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

?>