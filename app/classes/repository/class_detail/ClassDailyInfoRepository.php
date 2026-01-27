<?php
// ClassDailyInfoRepository.php
// 授業詳細（日別）に関するリポジトリクラス
require_once __DIR__ . '/../BaseRepository.php';

class ClassDailyInfoRepository extends BaseRepository {

    /**
     * findByDateAndSlot
     * 概要：指定した条件の授業詳細データを取得する
     * (日付、時限、コースIDで特定)
     * 引数：
     * @param string $date 日付 (YYYY-MM-DD)
     * @param int $period 時限
     * @param int $courseId コースID
     * 戻り値：
     * @return array|null
     * 連想配列の例：
     * [
     *  'date' => '2024-06-15',
     *  'period' => 2,
     *  'course_id' => 101,
     *  'subject_id' => 5,
     *  'teacher_id' => 12,
     *  'content' => '授業内容の詳細',
     *  'belongings' => '持ち物の詳細',
     *  'status_type' => 0:未作成, 1:一時保存, 2:作成済み
     * ]
     */
    public function findByDateAndSlot($date, $period, $courseId) {
        $sql = "SELECT * FROM class_daily_infos 
                WHERE date = :date AND period = :period AND course_id = :course_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':date' => $date,
            ':period' => $period,
            ':course_id' => $courseId
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * save
     * 概要：授業詳細データの保存（新規登録または更新）
     * @param array $data 授業詳細データの連想配列
     * @return bool
     */
    public function save($data) {
        $sql = "INSERT INTO class_daily_infos 
                (date, period, course_id, subject_id, teacher_id, content, belongings, status_type)
                VALUES (:date, :period, :course_id, :subject_id, :teacher_id, :content, :belongings, :status_type)
                ON DUPLICATE KEY UPDATE
                subject_id = VALUES(subject_id),
                teacher_id = VALUES(teacher_id),
                content = VALUES(content),
                belongings = VALUES(belongings),
                status_type = VALUES(status_type)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':date' => $data['date'],
            ':period' => $data['period'],
            ':course_id' => $data['course_id'],
            ':subject_id' => $data['subject_id'],
            ':teacher_id' => $data['teacher_id'],
            ':content' => $data['content'],
            ':belongings' => $data['belongings'],
            ':status_type' => $data['status_type']
        ]);
    }

    /**
     * delete
     * 概要：データの削除
     * @param string $date 日付 (YYYY-MM-DD)
     * @param int $period 時限
     * @param int $courseId コースID
     * @return bool
     */
    public function delete($date, $period, $courseId) {
        $sql = "DELETE FROM class_daily_infos 
                WHERE date = :date AND period = :period AND course_id = :course_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':date' => $date,
            ':period' => $period,
            ':course_id' => $courseId
        ]);
    }

    /**
     * findByMonthAndSubject
     * 概要：月間のデータをまとめて取得（カレンダー表示用）
     * 指定した年月の範囲にあるデータを取得します
     * 引数：
     * @param int $year 年 (YYYY)
     * @param int $month 月 (MM)
     * @param int $subjectId 科目ID
     * @param int $courseId コースID
     * 戻り値：
     * @return array
     * 連想配列の例：
     * [
     * [
     * 'date' => '2024-06-01',
     * 'period' => 1,
     * 'course_id' => 101,
     * 'subject_id' => 5,
     * 'teacher_id' => 12,
     * 'content' => '授業内容の詳細',
     * 'belongings' => '持ち物の詳細',
     * 'status_type' => 2
     * ],
     * ...
     * ]
     */
    public function findByMonthAndSubject($year, $month, $subjectId, $courseId) {
        // 月初と月末を計算
        $startDate = "$year-$month-01";
        $endDate = date("Y-m-t", strtotime($startDate));

        $sql = "SELECT * FROM class_daily_infos 
                WHERE subject_id = :subject_id
                AND course_id = :course_id
                AND date BETWEEN :start_date AND :end_date";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':subject_id' => $subjectId,
            ':course_id' => $courseId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}