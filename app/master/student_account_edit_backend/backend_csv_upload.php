<?php
session_start();

/////////////////////////////////////
//CSVデータ保存用テーブル作成
/////////////////////////////////////


//CSVデータ保存用テーブル名
$sql_delete_csv_table = "DROP TABLE IF EXISTS csv_table;";
//ーーーーーーCSVデータの書式が確定していないので後回しーーーーーーーーーーーーーーーーーーーー
$sql_create_csv_table = 
    "CREATE TABLE csv_table (
    student_id INT PRIMARY KEY,
    name VARCHAR(100),
    approvalUserAddress VARCHAR(100),
    delete_flg INT DEFAULT 0,
    course_id INT
);";
// course_idの行数取得
$sql_course_id_count = "SELECT COUNT(*) FROM course;";
    

try {
    // RepositoryFactoryを使用してPDOインスタンスを取得
    require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
    $pdo = RepositoryFactory::getPdo();

    //CSVデータ保存用テーブルの削除
    $stmt_delete = $pdo->prepare($sql_delete_csv_table);
    $stmt_delete->execute();

    //course_idの行数取得
    $stmt_course_id = $pdo->prepare($sql_course_id_count);
    $stmt_course_id->execute();
    $course_id_count = $stmt_course_id->fetchColumn();

    //CSVデータ保存用テーブルの作成
    $stmt_create = $pdo->prepare($sql_create_csv_table);
    $stmt_create->execute();
} 
catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}


/////////////////////////////////////
//エラーデータ保存用テーブル作成
/////////////////////////////////////


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

try {
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


/////////////////////////////////////
//CSVデータ処理
/////////////////////////////////////


//error_student_tableの格納SQLクエリ
$sql_insert_error_student = "INSERT INTO 
                                error_student_table (student_id, name, approvalUserAddress, error_id, course_id) 
                            VALUES 
                                (?, ?, ?, ?, ?);";


//CSVファイルがアップロードされたか確認
if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] === UPLOAD_ERR_OK) {
     
    //アップロードされたファイルの一時的なパスを取得
    $tmp_file = $_FILES['csvFile']['tmp_name'];

    //CSVファイルを開く
    if (($handle = fopen($tmp_file, 'r')) !== FALSE) {
        
        $bom = fread($handle, 3);
        if ($bom !== "\xef\xbb\xbf") {
            // BOMがなければ、ポインタをファイルの先頭に戻す
            fseek($handle, 0); 
        }
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

        //CVSファイルの内容を1行ずつ処理
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $column_number++;
            //csvの各列のデータを取得
            //$data[0]（学生番号）が正の整数かどうかの確認
            if (ctype_digit($data[0]) === true) {
                $user_id_prefix = floor((int)($data[0]) / 1000);
                //今の年度に対応する学生番号かどうかを判断するフラグ
                $flg = 0;
                foreach ($school_year as $year) {
                    //学年が正規のものか確認
                    if ($year == $user_id_prefix) {
                        $flg = 1;
                        $column_user_id = (int)$data[0];
                        break;
                    }
                }
                if ($flg == 0) {
                    //error_student_tableに格納
                    try {
                        $stmt = $pdo->prepare($sql_insert_error_student);
                        //SQL文を実行
                        $error_id = 1001; //学年部分不正エラー
                        $stmt->execute([$data[0], $data[1], $data[2], $error_id, $data[3]]);
                    }
                    catch (PDOException $e) {
                        //エラー処理
                        echo "error_student_tableに格納できませんでした： " . $e->getMessage();
                    }
                    continue;
                }
            }
            //$data[0]（学生番号）が正の整数ではなかった場合
            else {
                //error_student_tableに格納
                try {
                    $stmt = $pdo->prepare($sql_insert_error_student);
                    //SQL文を実行
                    $error_id = 1002; //ユーザーID形式不正エラー
                    $stmt->execute([$data[0], $data[1], $data[2], $error_id, $data[3]]);
                }
                catch (PDOException $e) {
                    //エラー処理
                    echo "error_student_tableに格納できませんでした： " . $e->getMessage();
                }
                continue;
            }
            //名前の取得
            $column_name = $data[1];

            //メールアドレスの取得
            if (preg_match("/^icc$column_user_id@isahaya-cc\\.ac\\.jp$/", $data[2]) == true) {
                $column_address = $data[2];
            }
            else {
                try {
                    $stmt = $pdo->prepare($sql_insert_error_student);
                    //SQL文を実行
                    $error_id = 2001; //メールアドレス形式不正エラー
                    $stmt->execute([$data[0], $data[1], $data[2], $error_id, $data[3]]);
                }
                catch (PDOException $e) {
                    //エラー処理
                    echo "error_student_tableに格納できませんでした： " . $e->getMessage();
                }
                continue;
            }

            // コースIDの確認
            if (isset($data[3]) && ctype_digit($data[3]) === true) {
                $column_course_id = (int)$data[3];
                if ($column_course_id >= 1 && $column_course_id <= $course_id_count) {
                    // 正常なコースID
                }
                else {
                    //error_student_tableに格納
                    try {
                        $stmt = $pdo->prepare($sql_insert_error_student);
                        //SQL文を実行
                        $error_id = 1001; //コースID不正エラー
                        $stmt->execute([$data[0], $data[1], $data[2], $error_id, $data[3]]);
                    }
                    catch (PDOException $e) {
                        //エラー処理
                        echo "error_student_tableに格納できませんでした： " . $e->getMessage();
                    }
                    continue;
                }
            }
            else {
                //error_student_tableに格納
                try {
                    $stmt = $pdo->prepare($sql_insert_error_student);
                    //SQL文を実行
                    $error_id = 1002; //コースID形式不正エラー
                    $stmt->execute([$data[0], $data[1], $data[2], $error_id, $data[3]]);
                }
                catch (PDOException $e) {
                    //エラー処理
                    echo "error_student_tableに格納できませんでした： " . $e->getMessage();
                }
                continue;
            }
            
            $sql_1 = "INSERT INTO csv_table (student_id, name, approvalUserAddress, course_id) VALUES (?, ?, ?, ?);";

            try {
                $stmt = $pdo->prepare($sql_1);
                //SQL文を実行
                $stmt->execute([$column_user_id, $column_name, $column_address, $column_course_id]);

                $insert_count++;
            }
            catch (PDOException $e) {
                //エラー処理
                echo "エラーが発生しました: " . $e->getMessage();
                continue;
            }
        }
        //ファイルを閉じる
        fclose($handle);
        
        // ★ 処理結果をセッションに格納
        if (isset($pdo)) {
            // エラー件数と正常データ件数を取得 (error_student_tableとcsv_tableのレコード数を数える)
            try {
                $sql_error_count = "SELECT COUNT(*) AS error_count FROM error_student_table;";
                $stmt = $pdo->query($sql_error_count);
                $error_result = $stmt->fetch();
                $error_count = $error_result['error_count'];

                $sql_success_count = "SELECT COUNT(*) AS success_count FROM csv_table;";
                $stmt = $pdo->query($sql_success_count);
                $success_result = $stmt->fetch();
                $success_count = $success_result['success_count'];
            }
            catch (PDOException $e) {
                echo "エラー件数の取得に失敗しました: " . $e->getMessage();
            }
            // DB接続を閉じる
            $pdo = null;
        } else {
            // DB接続が失敗した場合などのエラー
            $error_count = -1; 
        }


    }
    else {
        //echo "CSVファイルを開くことができませんでした。";
    }

}
else {
    //echo "CSVファイルがアップロードされていません。";
    $_SESSION['upload_status']['success'] = false;
    $_SESSION['upload_status']['message'] = "ファイルがアップロードされていません。エラーコード: " . ($_FILES['csvFile']['error'] ?? 'N/A');
}


// ★ CSV_edit.php にリダイレクトして処理を終了
header("Location: ../../../public/master/student_account_edit/controls/student_account_edit_control.php?backend=csv_upload");
exit(); // リダイレクト後は必ず処理を終了

?>