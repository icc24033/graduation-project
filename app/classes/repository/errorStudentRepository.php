<?php
// errorStudentRepository.php
// エラーデータ操作用リポジトリクラス

// 自動的にBaseRepositoryを継承し、DB接続を確立する
require_once __DIR__ . '/BaseRepository.php';

class errorStudentRepository extends BaseRepository {

    /**
     * エラーデータ保存用テーブル作成
     */
    public function createErrorDataTable() {
        try {
            // error_student_tableが存在する場合は削除してから作成
            $delete_error_data_table_sql = "DROP TABLE IF EXISTS error_student_table;";

            // error_student_table作成SQL
            //↓user_idをVARCHAR型にしてるのは、不正な形式のユーザーIDも格納するため
            $create_error_data_table_sql =
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

            // SQL実行
            $stmt_delete = $this->pdo->prepare($delete_error_data_table_sql);
            $stmt_delete->execute();

            $stmt_create = $this->pdo->prepare($create_error_data_table_sql);
            $stmt_create->execute();

            $stmt_foreign = $this->pdo->prepare($sql_error_id_foreign_key);
            $stmt_foreign->execute();

        } catch (PDOException $e) {
            error_log("CsvRepository Error: " . $e->getMessage());
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
    
    /**
     * エラーデータを挿入する
     */
    public function insertErrorData($student_id, $name, $approvalUserAddress, $error_id, $course_id) {
        try {
            $insert_error_data_sql = 
                "INSERT INTO error_student_table 
                (student_id, name, approvalUserAddress, error_id, course_id) 
                VALUES (?, ?, ?, ?, ?);";

            $stmt = $this->pdo->prepare($insert_error_data_sql);
            $stmt->execute([
                $student_id,
                $name,
                $approvalUserAddress,
                $error_id,
                $course_id
            ]);

        } catch (PDOException $e) {
            error_log("errorStudentRepository Error: " . $e->getMessage());
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * エラーデータの件数を取得する
     * @return int エラーデータの件数
     */
    public function countErrorData() {
        try {
            $count_error_data_sql = "SELECT COUNT(*) AS count FROM error_student_table;";
            $stmt = $this->pdo->prepare($count_error_data_sql);
            $stmt->execute();
            $result = $stmt->fetch();
            return (int)$result['count'];

        } catch (PDOException $e) {
            error_log("errorStudentRepository Error: " . $e->getMessage());
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * エラーデータをすべて取得する
     * @return array エラーデータの配列
     */
    public function getAllErrorData() {
        try {
            $sql = "SELECT * FROM error_student_table;";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("errorStudentRepository Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * エラーデータ保存用テーブルを更新する
     */
    public function updataErrorDataTable($student_id, $name, $approvalUserAddress, $error_id, $id) {
        try {
            // error_student_tableの内容を更新
            $update_error_data_sql = 
                "UPDATE error_student_table
                 SET student_id = ?, name = ?, approvalUserAddress = ?, error_id = ?
                 WHERE id = ?;";
            $stmt = $this->pdo->prepare($update_error_data_sql);
            $stmt->execute([
                $student_id,
                $name,
                $approvalUserAddress,
                $error_id,
                $id
            ]);
        } catch (PDOException $e) {
            error_log("errorStudentRepository Error: " . $e->getMessage());
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * エラーデータ保存用テーブルに格納されているデータをIDで削除する
     */
    public function deleteErrorDataById($id) {
        try {
            $delete_error_data_sql = "DELETE FROM error_student_table WHERE id = ?;";
            $stmt = $this->pdo->prepare($delete_error_data_sql);
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("errorStudentRepository Error: " . $e->getMessage());
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
}