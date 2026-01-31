<?php
// ClassDailyInfoRepository.php
// 授業詳細（日別）に関するリポジトリクラス
require_once __DIR__ . '/../BaseRepository.php';

class ClassDailyInfoRepository extends BaseRepository {

    /**
     * updateOrCreate
     * 概要：授業詳細情報の登録または更新（Upsert）
     */
    public function updateOrCreate($data) {
        try {
            // 1. 既にデータが存在するか確認
            $sqlCheck = "SELECT daily_info_id FROM class_daily_infos 
                         WHERE date = :date 
                         AND period = :period 
                         AND course_id = :course_id
                         AND subject_id = :subject_id";
            
            $stmtCheck = $this->pdo->prepare($sqlCheck);
            $stmtCheck->execute([
                ':date' => $data['date'],
                ':period' => $data['period'],
                ':course_id' => $data['course_id'],
                ':subject_id' => $data['subject_id']
            ]);
            
            $exists = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($exists) {
                // 2. 更新 (UPDATE)
                $sqlUpdate = "UPDATE class_daily_infos SET 
                                content = :content,
                                belongings = :belongings,
                                status_type = :status_type,
                                teacher_id = :teacher_id,
                                updated_at = NOW()
                              WHERE daily_info_id = :id";
                
                $stmt = $this->pdo->prepare($sqlUpdate);
                return $stmt->execute([
                    ':content' => $data['content'],
                    ':belongings' => $data['belongings'],
                    ':status_type' => $data['status_type'],
                    ':teacher_id' => $data['teacher_id'],
                    ':id' => $exists['daily_info_id']
                ]);
            } else {
                // 3. 新規登録 (INSERT)
                $sqlInsert = "INSERT INTO class_daily_infos 
                                (date, period, course_id, subject_id, teacher_id, content, belongings, status_type, updated_at)
                              VALUES 
                                (:date, :period, :course_id, :subject_id, :teacher_id, :content, :belongings, :status_type, NOW())";
                
                $stmt = $this->pdo->prepare($sqlInsert);
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
        } catch (PDOException $e) {
            // エラーハンドリング・ユーザーへは情報を与えないように設計する
            error_log("ClassDailyInfoRepository Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * deleteDailyInfo
     * 概要：授業詳細情報の削除（物理削除）
     * ※要件により論理削除にする場合はUPDATEでflagを立てる処理に変更してください
     * * @param string $date
     * @param int $period
     * @param int $courseId
     * @param int $subjectId
     * @return bool
     */
    public function deleteDailyInfo($date, $period, $courseId, $subjectId) {
        $sql = "DELETE FROM class_daily_infos 
                WHERE date = :date 
                AND period = :period 
                AND course_id = :course_id
                AND subject_id = :subject_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':date' => $date,
            ':period' => $period,
            ':course_id' => $courseId,
            ':subject_id' => $subjectId
        ]);
    }

    /**
     * getSubstituteClassesByTeacherId
     * 概要：授業変更により代理で担当することになった科目・クラスを取得する
     * * @param int $teacherId 教員ID
     * @return array
     */
    public function getSubstituteClassesByTeacherId($teacherId) {
        try {
            // 修正: timetable_changes は timetable_id を持っており、course_id は timetables テーブルにあるため結合を追加
            $sql = "
                SELECT DISTINCT
                    t.course_id,
                    c.course_name,
                    c.grade,
                    tc.subject_id,
                    s.subject_name
                FROM timetable_change_teachers tct
                INNER JOIN timetable_changes tc ON tct.change_id = tc.change_id
                INNER JOIN timetables t ON tc.timetable_id = t.timetable_id
                INNER JOIN course c ON t.course_id = c.course_id
                INNER JOIN subjects s ON tc.subject_id = s.subject_id
                WHERE tct.teacher_id = :teacherId
                ORDER BY c.grade ASC, t.course_id ASC, tc.subject_id ASC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':teacherId', $teacherId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // エラーログ等が必要であれば記述
            error_log("ClassDailyInfoRepository Error: " . $e->getMessage());
            return [];
        }
    }

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