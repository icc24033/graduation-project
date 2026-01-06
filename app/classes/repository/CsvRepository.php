<?php
// CsvRepository.php
// CSVファイル操作用リポジトリクラス

// 自動的にBaseRepositoryを継承し、DB接続を確立する
require_once __DIR__ . '/BaseRepository.php';

class CsvRepository extends BaseRepository {

    /**
     * CSVデータ保存用テーブル作成
     */
    public function createCsvTable() {
        try {
            // csv_tableが存在する場合は削除してから作成
            $delete_csv_table_sql = "DROP TABLE IF EXISTS csv_table;";

            // csv_table作成SQL
            $create_csv_table_sql = 
                "CREATE TABLE csv_table (
                student_id INT PRIMARY KEY,
                name VARCHAR(100),
                approvalUserAddress VARCHAR(100),
                delete_flg INT DEFAULT 0,
                course_id INT
            );";

            // SQL実行
            $stmt_delete = $this->pdo->prepare($delete_csv_table_sql);
            $stmt_delete->execute();

            $stmt_create = $this->pdo->prepare($create_csv_table_sql);
            $stmt_create->execute();
        
        } catch (PDOException $e) {
            error_log("CsvRepository Error: " . $e->getMessage());
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * CSVデータを挿入する
     */
    public function insertCsvData($student_id, $name, $approvalUserAddress, $course_id) {
        try {
            $insert_csv_data_sql = 
                "INSERT INTO csv_table (student_id, name, approvalUserAddress, course_id)
                VALUES (?, ?, ?, ?);";

            $stmt = $this->pdo->prepare($insert_csv_data_sql);
            $stmt->execute([$student_id, $name, $approvalUserAddress, $course_id]);

        } catch (PDOException $e) {
            error_log("CsvRepository Error: " . $e->getMessage());
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * CSVデータの件数を取得する
     * @return int 件数
     */
    public function countCsvData() {
        try {
            $count_csv_data_sql = "SELECT COUNT(*) AS count FROM csv_table;";
            $stmt = $this->pdo->prepare($count_csv_data_sql);
            $stmt->execute();
            $result = $stmt->fetch();
            return (int)$result['count'];

        } catch (PDOException $e) {
            error_log("CsvRepository Error: " . $e->getMessage());
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * CSVデータを全件取得する
     * @return array CSVデータの配列
     */
    public function getAllCsvData() {
        try {
            $get_all_csv_data_sql = "SELECT * FROM csv_table;";
            $stmt = $this->pdo->prepare($get_all_csv_data_sql);
            $stmt->execute();
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("CsvRepository Error: " . $e->getMessage());
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
}