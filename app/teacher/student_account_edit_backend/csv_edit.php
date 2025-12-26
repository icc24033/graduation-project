<?php

// --- デバッグ用：エラーを表示させる設定（解決したら削除してください） ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// POSTから取得した学生情報を格納するSQLクエリ
$insert_student_sql = ("INSERT IGNORE INTO 
                            student (student_id, student_mail, student_name, course_id, grade) 
                        VALUES 
                            (?, ?, ?, ?, ?);"
                        );

// POSTから取得した学生情報をstudent_login_tableに格納するSQLクエリ
$insert_student_login_sql = ("INSERT INTO 
                                student_login_table (id, student_id, user_grade) 
                              SELECT 
                                ?, ?, 'student@icc_ac.jp' 
                              WHERE NOT EXISTS 
                                (SELECT 1 FROM student_login_table WHERE student_id = ?);"
                            );

//student_login_tableに格納してある値の数を取得するSQLクエリ
$student_login_count_sql = ("SELECT COUNT(*) FROM student_login_table;");

//エラーデータ保存用テーブル作成
$sql_delete_error_table = "DROP TABLE IF EXISTS error_student_table;";
//ーーーーーーCSVデータの書式が確定していないので後回しーーーーーーーーーーーーーーーーーーーー
//↓user_idをVARCHAR型にしてるのは、不正な形式のユーザーIDも格納するため
$sql_create_error_table = 
    "CREATE TABLE error_student_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(100),
    name VARCHAR(100),
    approvalUserAddress VARCHAR(100),
    error_id INT,
    course_id INT
);";

//error_idの外部キー設定
$sql_error_id_foreign_key = 
    "ALTER TABLE error_student_table
    ADD CONSTRAINT fk_error_id
    FOREIGN KEY (error_id) REFERENCES error_table(error_id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION;
    ";

//error_student_tableの格納SQLクエリ
$sql_insert_error_student = "INSERT INTO 
                                error_student_table (student_id, name, approvalUserAddress, error_id, course_id) 
                             VALUES 
                                (?, ?, ?, ?, ?);";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // RepositoryFactoryを使用してPDOインスタンスを取得
        require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
        $pdo = RepositoryFactory::getPdo();

        //エラーデータ保存用テーブルの削除
        $stmt_delete_error = $pdo->prepare($sql_delete_error_table);
        $stmt_delete_error->execute();

        //エラーデータ保存用テーブルの作成
        $stmt_create_error = $pdo->prepare($sql_create_error_table);
        $stmt_create_error->execute();

        //error_idの外部キー設定
        $stmt_foreign_key = $pdo->prepare($sql_error_id_foreign_key);
        $stmt_foreign_key->execute();
    } 
    catch (PDOException $e) {
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }

    //データ件数を取得する変数
    $insert_count = 0;
    $error_count = 0;

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

    foreach ($_POST['students'] as $index => $student_data) {

        /////////////////////////////
        //////// 学生IDの確認 ////////
        /////////////////////////////
        if (ctype_digit($student_data['student_id'])) {
            $student_id_prefix = floor((int)($student_data['student_id'] / 1000));
            // 今の年度に対応する学生番号かどうかを判断するフラグ
            $flg = false;
            foreach ($school_year as $year) {
                if ($year == $student_id_prefix) {
                    $flg = true;
                    $column_student_id = (int)$student_data['student_id'];
                    break; 
                }
            }
            if ($flg === false) {
                // 不正な学生番号の場合、エラーテーブルに挿入
                try {
                    $stmt_error_data_insert = $pdo->prepare($sql_insert_error_student);
                    //SQL実行
                    $error_id = 1001; // 不正な学生番号エラー

                    // student_emailの生成
                    $student_address = 'icc' . $student_data['student_id'] . '@isahaya-cc.ac.jp';

                    $stmt_error_data_insert->execute([
                        $student_data['student_id'],
                        $student_data['name'],
                        $student_address,
                        $error_id,
                        $student_data['course_id']
                    ]);
                    // エラーカウントをインクリメント
                    $error_count++;
                } 
                catch (PDOException $e) {
                    throw new PDOException($e->getMessage(), (int)$e->getCode());
                }
                continue; // 次のループへ
            }

            ////////////////////////////
            //////// 名前の確認 /////////
            ////////////////////////////

            if (strlen($student_data['name']) > 0) {
                $column_name = $student_data['name'];
            } 
            else {
                // 名前が空欄の場合、エラーテーブルに挿入
                try {
                    $stmt_error_data_insert = $pdo->prepare($sql_insert_error_student);
                    //SQL実行
                    $error_id = 1002; // 名前が空欄エラー
                    // student_emailの生成
                    $student_address = 'icc' . $student_data['student_id'] . '@isahaya-cc.ac.jp';
                    $stmt_error_data_insert->execute([
                        $student_data['student_id'],
                        $student_data['name'],
                        $student_address,
                        $error_id,
                        $student_data['course_id']
                    ]);
                    // エラーカウントをインクリメント
                    $error_count++;
                } 
                catch (PDOException $e) {
                    throw new PDOException($e->getMessage(), (int)$e->getCode());
                }
                continue; // 次のループへ
            }
        }
        // student_idが数字でない場合
        else {
            // 不正な学生番号の場合、エラーテーブルに挿入
            try {
                $stmt_error_data_insert = $pdo->prepare($sql_insert_error_student);
                //SQL実行
                $error_id = 1002; // 学生番号が数字でないエラー
                // student_emailの生成
                $student_address = 'icc' . $student_data['student_id'] . '@isahaya-cc.ac.jp';
                $stmt_error_data_insert->execute([
                    $student_data['student_id'],
                    $student_data['name'],
                    $student_address,
                    $error_id,
                    $student_data['course_id']
                ]);
                // エラーカウントをインクリメント
                $error_count++;
            } 
            catch (PDOException $e) {
                throw new PDOException($e->getMessage(), (int)$e->getCode());
            }
            continue; // 次のループへ
        }
        // 正常データ件数をカウント
        $insert_count++;

        //////////////////////////////////
        //////// 正常データを挿入 /////////
        /////////////////////////////////

        try {
            // student_login_tableに格納してある値の数を取得
            $stmt_login_count = $pdo->prepare($student_login_count_sql);
            $stmt_login_count->execute();
            $login_count_result = $stmt_login_count->fetch();

            $total_login_users = $login_count_result['COUNT(*)'] + 1; // 新しいIDの開始点のために1を加算

            // studentテーブルに学生情報を挿入
            $stmt_insert_student = $pdo->prepare($insert_student_sql);
            // student_emailの生成
            $student_address = 'icc' . $student_data['student_id'] . '@isahaya-cc.ac.jp';
            $stmt_insert_student->execute([
                $column_student_id,
                $student_address,
                $column_name,
                $student_data['course_id'],
                1 ///////////////////////後から変更
            ]);

            //student_login_tableに学生情報を挿入
            $stmt_insert_login = $pdo->prepare($insert_student_login_sql);
            $stmt_insert_login->execute([   
                $total_login_users,
                $column_student_id,
                $column_student_id
            ]);

            // 次のユーザーIDにインクリメント
            $total_login_users++;
        }
        catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    // エラー件数に応じてフラグとSQLクエリを設定
    if ($error_count > 0) {
        $error_count_flag = true;
        $csv_error_table_sql = "SELECT * FROM error_student_table;";
    }
    else {
        $error_count_flag = false;
        $csv_error_table_sql = null;
    }

    //コース情報取得SQLクエリ
    $course_sql = ("SELECT * FROM course;");

    //studentに格納されている学生情報の数の取得
    $student_count_sql = ("SELECT COUNT(*)  FROM student WHERE LEFT(student_id, 2) = ?;");  

    $_SESSION['student_account'] = [
        'success' => true,
        'backend' => 'student_addition',
        'error_csv' => $error_count_flag,
        'csv_error_table_sql' => $csv_error_table_sql,
        'before' => 'teacher_home',
        'course_sql' => $course_sql,
        'student_count_sql' => $student_count_sql
    ];

    // データベース接続を閉じる
    $pdo = null;
}

// ★ student_addition.php にリダイレクトして処理を終了
header("Location: ../../../public/teacher/student_account_edit/student_addition.php");
exit(); // リダイレクト後は必ず処理を終了

?>