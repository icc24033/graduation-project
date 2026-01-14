<?php
// ErrorTeacherTableRepository.php
// エラー教員データを扱うリポジトリクラス

// 自動的にBaseRepositoryを継承し、DB接続を確立する
require_once __DIR__ . '/BaseRepository.php';

class ErrorTeacherTableRepository extends BaseRepository {
    /**
     * すべてのCSVエラーデータの教員一覧を取得する
     * @return array CSVエラーデータの教員情報の配列
     */
    public function getAllErrorTeachers() {
        try {
            $sql = "SELECT * FROM error_teacher_table ORDER BY id ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("ErrorTeacherTableRepository Error: " . $e->getMessage());
            return [];
        } 
    }

    /**
     * csvエラーデータが存在するか確認する
     * @return bool 存在する場合はtrue、存在しない場合はfalse
     */
    public function hasErrorTeachers() {
        try {
            $sql = "SELECT COUNT(*) as count FROM error_teacher_table";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();

            return $result['count'] > 0;

        } catch (PDOException $e) {
            error_log("ErrorTeacherTableRepository Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * エラー教員テーブルを作り直す
     */
    public function createErrorTeacherTable() {
        // error_teacher_tableが存在する場合は削除し、新規作成する
        try {
            $sql_drop = "DROP TABLE IF EXISTS error_teacher_table;";
            $this->pdo->exec($sql_drop);

            $sql_create = "
                CREATE TABLE error_teacher_table (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            $this->pdo->exec($sql_create);

        } catch (PDOException $e) {
            error_log("ErrorTeacherTableRepository Error (createErrorTeacherTable): " . $e->getMessage());
        }
    }
}