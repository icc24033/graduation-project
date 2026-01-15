<?php
// RoomRepository.php
// 教室情報を取得するリポジトリクラス

// 自動的にBaseRepositoryを継承し、DB接続を確立する
require_once __DIR__ . '/BaseRepository.php';

class RoomRepository extends BaseRepository {

    /**
     * すべての教室一覧を取得する
     * @return array 教室情報の配列
     */
    public function getAllRooms() {
        try {
            $sql = "SELECT * FROM room";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("RoomRepository Error: " . $e->getMessage());
            return [];
        }
    }
}