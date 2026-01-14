<?php
// TempTeacherCsvRepository.php
// 教員の一時CSVデータを扱うリポジトリクラス

// 自動的にBaseRepositoryを継承し、DB接続を確立する
require_once __DIR__ . '/BaseRepository.php';

class TempTeacherCsvRepository extends BaseRepository {

    /**
     * すべてのCSVデータの教員一覧を取得する
     * @return array CSVデータの教員情報の配列
     */
    public function getAllTempTeachers() {
        try {
            $sql = "SELECT * FROM temp_teacher_csv ORDER BY id ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("TempTeacherCsvRepository Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * csvデータが存在するか確認する
     * @return bool 存在する場合はtrue、存在しない場合はfalse
     */
    public function hasTempTeachers() {
        try {
            $sql = "SELECT COUNT(*) as count FROM temp_teacher_csv";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();

            return $result['count'] > 0;

        } catch (PDOException $e) {
            error_log("TempTeacherCsvRepository Error: " . $e->getMessage());
            return false;
        }
    }
}