<?php
// TimeTableDetailsRepository.php
// ディレクトリ: classes/repository/timetable_details/TimeTableDetailsRepository.php

require_once __DIR__ . '/../BaseRepository.php';

class TimeTableDetailsRepository extends BaseRepository {

    /**
     * 指定された曜日とタイムテーブルIDに基づき授業詳細を取得する
     * @param int $day 曜日番号 (1:月 ~ 7:日)
     * @param int $timetableId タイムテーブルID (コースID)
     * @return array
     */
    public function findByDayAndTimetableId($day, $timetableId) {
        try {
            $sql = "SELECT td.*, s.subject_name 
                    FROM timetable_details td
                    LEFT JOIN subjects s ON td.subject_id = s.subject_id
                    WHERE td.day_of_week = :day 
                    AND td.timetable_id = :tid 
                    ORDER BY td.period ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':day' => $day, 
                ':tid' => $timetableId
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TimeTableDetailsRepository Error: " . $e->getMessage());
            return [];
        }
    }
}