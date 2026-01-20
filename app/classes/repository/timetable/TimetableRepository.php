<?php
// TimetableRepository.php
// 時間割りに関するデータベース接続の操作を行うリポジトリクラス

require_once __DIR__ . '/../BaseRepository.php';

class TimetableRepository extends BaseRepository {

    /*
    * getTimetablesByCourseId
    * 概要：指定されたコースIDに基づいて時間割りデータを取得する
    * 引数：$courseId - 取得するコースのID
    * 戻り値：時間割りデータの配列
    */
    public function getTimetablesByCourseId($courseId) {
        try {
            // status_type を取得カラムに追加
            $sql = "
                SELECT 
                    t.timetable_id,
                    t.course_id,
                    t.start_date,
                    t.end_date,
                    t.status_type,  -- ★ここに追加
                    c.course_name,
                    td.day_of_week,
                    td.period,
                    s.subject_name,
                    te.teacher_name,
                    r.room_name
                FROM timetables t
                JOIN course c ON t.course_id = c.course_id
                LEFT JOIN timetable_details td ON t.timetable_id = td.timetable_id
                LEFT JOIN subjects s ON td.subject_id = s.subject_id
                LEFT JOIN timetable_detail_teachers tdt ON td.detail_id = tdt.detail_id
                LEFT JOIN teacher te ON tdt.teacher_id = te.teacher_id
                LEFT JOIN room r ON tdt.room_id = r.room_id
                WHERE t.course_id = :courseId
                ORDER BY t.timetable_id ASC, td.day_of_week ASC, td.period ASC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':courseId', $courseId, PDO::PARAM_INT);
            $stmt->execute();
            
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 取得したデータをJSON形式で構造化して返す
            return $this->structureTimetableData($rows);

        } catch (PDOException $e) {
            error_log("TimetableRepository Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * structureTimetableData
     * 概要：取得した時間割りデータをJSON形式で構造化する
     * 引数：$rows - データベースから取得した生データの配列
     * 戻り値：構造化された時間割りデータの配列
     */
    private function structureTimetableData($rows) {
        $dayMap = [
            1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金', 6 => '土', 7 => '日'
        ];

        $resultMap = [];

        // データをループして構造化
        foreach ($rows as $row) {
            // 時間割りIDをキーに使用
            $tId = $row['timetable_id'];

            // 該当する時間割りキーのresultMapエントリが存在しない場合、新規作成する
            if (!isset($resultMap[$tId])) {
                $resultMap[$tId] = [
                    'id'         => $tId,
                    'courseId'   => $row['course_id'],
                    'course'     => $row['course_name'],
                    'startDate'  => $row['start_date'],
                    'endDate'    => $row['end_date'],
                    'statusType' => $row['status_type'], 
                    'data'       => [] 
                ];
            }

            // 各時間割り詳細をdata配列に追加
            if (!empty($row['subject_name'])) {
                $dayInt = (int)$row['day_of_week'];
                $dayStr = $dayMap[$dayInt] ?? ''; 

                $resultMap[$tId]['data'][] = [
                    'day'         => $dayStr,
                    'period'      => $row['period'],
                    'className'   => $row['subject_name'],
                    'teacherName' => $row['teacher_name'] ?? '',
                    'roomName'    => $row['room_name'] ?? ''
                ];
            }
        }

        return array_values($resultMap);
    }

    /**
     * createTimetable
     * 概要：新しい時間割りを作成する
     * 動作: 新規作成された時間割りに関して、timetablesテーブルに登録予定のレコードを挿入する
     * 引数：$courseId - コースID, $startDate - 開始日, $endDate - 終了日, $statusType - ステータス（デフォルト1）
     * 戻り値：新規作成された時間割りのID
     */
    public function createTimetable($courseId, $startDate, $endDate, $statusType = 1) {
        $sql = "INSERT INTO timetables (course_id, start_date, end_date, status_type, created_at, updated_at) 
                VALUES (:course_id, :start_date, :end_date, :status_type, NOW(), NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':course_id', $courseId, PDO::PARAM_INT);
        $stmt->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        $stmt->bindValue(':end_date', $endDate, PDO::PARAM_STR);
        $stmt->bindValue(':status_type', $statusType, PDO::PARAM_INT);
        
        $stmt->execute();
        
        // 自動採番された timetable_id を返す（これを子テーブルで使う）
        return $this->pdo->lastInsertId();
    }

    /**
     * createTimetableDetail
     * 概要：新しい時間割り詳細を作成する
     * 動作: 新規作成された時間割り詳細に関して、timetable_detailsテーブルに登録予定のレコードを挿入する
     * 引数：$timetableId - 親時間割りID, $dayOfWeek - 曜日, $period - 時限, $subjectId - 科目ID
     * 戻り値：新規作成された時間割り詳細のID
     */
    public function createTimetableDetail($timetableId, $dayOfWeek, $period, $subjectId) {
        $sql = "INSERT INTO timetable_details (timetable_id, day_of_week, period, subject_id) 
                VALUES (:timetable_id, :day_of_week, :period, :subject_id)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':timetable_id', $timetableId, PDO::PARAM_INT);
        $stmt->bindValue(':day_of_week', $dayOfWeek, PDO::PARAM_INT);
        $stmt->bindValue(':period', $period, PDO::PARAM_INT);
        $stmt->bindValue(':subject_id', $subjectId, PDO::PARAM_INT); // 空きコマならNULLなどの制御が必要
        
        $stmt->execute();
        
        // 自動採番された detail_id を返す（これを孫テーブルで使う）
        return $this->pdo->lastInsertId();
    }

    /**
     * 
     */
    public function createDetailTeacher($detailId, $teacherId, $roomId = null) {
        $sql = "INSERT INTO timetable_detail_teachers (detail_id, teacher_id, room_id) 
                VALUES (:detail_id, :teacher_id, :room_id)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':detail_id', $detailId, PDO::PARAM_INT);
        $stmt->bindValue(':teacher_id', $teacherId, PDO::PARAM_INT);
        $stmt->bindValue(':room_id', $roomId, ($roomId ? PDO::PARAM_INT : PDO::PARAM_NULL));
        
        $stmt->execute();
    }
}