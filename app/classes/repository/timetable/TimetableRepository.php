<?php
// TimetableRepository.php
// 時間割りに関するデータベース接続の操作を行うリポジトリクラス

require_once __DIR__ . '/../BaseRepository.php';

class TimetableRepository extends BaseRepository {

    /*
    * getTimetablesByCourseId
    * 概要：指定されたコースIDに基づいて、そのコースの時間割りデータを取得する
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
                    t.status_type,
                    c.course_name,
                    td.day_of_week,
                    td.period,
                    s.subject_name,
                    te.teacher_name,
                    r.room_name,
                    s.subject_id,
                    te.teacher_id,
                    r.room_id
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

        foreach ($rows as $row) {
            $tId = $row['timetable_id'];

            // 1. 時間割自体の初期化
            if (!isset($resultMap[$tId])) {
                $resultMap[$tId] = [
                    'id'         => $tId,
                    'courseId'   => $row['course_id'],
                    'course'     => $row['course_name'],
                    'startDate'  => $row['start_date'],
                    'endDate'    => $row['end_date'],
                    'statusType' => $row['status_type'], 
                    'data'       => [] // ここは後で values() で配列化する
                ];
                // 詳細データ一時保存用（キー: "曜日_時限"）
                $resultMap[$tId]['_temp_details'] = [];
            }

            // 科目がない行はスキップ
            if (empty($row['subject_name'])) {
                continue;
            }

            $dayInt = (int)$row['day_of_week'];
            $period = (int)$row['period'];
            $dayStr = $dayMap[$dayInt] ?? '';
            
            // ユニークキーを作成
            $detailKey = "{$dayInt}_{$period}";

            // 2. まだそのコマのデータ枠がなければ作成
            if (!isset($resultMap[$tId]['_temp_details'][$detailKey])) {
                $resultMap[$tId]['_temp_details'][$detailKey] = [
                    'day'           => $dayStr,
                    'period'        => $period,
                    'className'     => $row['subject_name'],
                    'subjectId'     => $row['subject_id'] ?? null, // IDも必要なら取得SQLに追加推奨
                    'teacherNames'  => [], // 配列で管理
                    'roomNames'     => [], // 配列で管理
                    'teacherIds'    => [], // 必要なら
                    'roomIds'       => []  // 必要なら
                ];
            }

            // 3. 先生・教室データを配列に追加（重複排除）
            if (!empty($row['teacher_name'])) {
                $tName = $row['teacher_name'];
                if (!in_array($tName, $resultMap[$tId]['_temp_details'][$detailKey]['teacherNames'])) {
                    $resultMap[$tId]['_temp_details'][$detailKey]['teacherNames'][] = $tName;
                }
                // IDも同様に追加（JSでdatasetに入れるためSQLで取得している前提）
                if (!empty($row['teacher_id'])) {
                     $tid = $row['teacher_id'];
                     if (!in_array($tid, $resultMap[$tId]['_temp_details'][$detailKey]['teacherIds'])) {
                         $resultMap[$tId]['_temp_details'][$detailKey]['teacherIds'][] = $tid;
                     }
                }
            }
            if (!empty($row['room_name'])) {
                $rName = $row['room_name'];
                if (!in_array($rName, $resultMap[$tId]['_temp_details'][$detailKey]['roomNames'])) {
                    $resultMap[$tId]['_temp_details'][$detailKey]['roomNames'][] = $rName;
                }
                if (!empty($row['room_id'])) {
                     $rid = $row['room_id'];
                     if (!in_array($rid, $resultMap[$tId]['_temp_details'][$detailKey]['roomIds'])) {
                         $resultMap[$tId]['_temp_details'][$detailKey]['roomIds'][] = $rid;
                     }
                }
            }
        }

        // 4. 整形処理（配列を文字列に変換、またはJSが扱いやすい形にする）
        // ここではJSの表示ロジックに合わせて、カンマ区切り文字列にする例
        // ※JS側でteacherNames配列を受け取れるなら配列のままの方が良いですが、
        //  既存JSが `teacherName` (単数文字列) を期待している場合に合わせて結合します。
        
        $finalList = [];
        foreach ($resultMap as $tId => $timetable) {
            $details = [];
            if (isset($timetable['_temp_details'])) {
                foreach ($timetable['_temp_details'] as $dt) {
                    // 表示用に結合する
                    // 今回はJS側へ配列で渡すために、teacherIds/roomIds をJSON化してdatasetに入れる想定
                    
                    // シンプルに表示用文字列を作成
                    $dt['teacherName'] = implode('・', $dt['teacherNames']);
                    $dt['roomName']    = implode('・', $dt['roomNames']);
                    
                    $dt['teacherIds'] = $dt['teacherIds']; 
                    $dt['roomIds']    = $dt['roomIds'];
                    
                    // 不要な一時キーを削除
                    unset($dt['teacherNames']);
                    unset($dt['roomNames']);
                    
                    $details[] = $dt;
                }
            }
            $timetable['data'] = $details;
            unset($timetable['_temp_details']); // 一時データを消す
            $finalList[] = $timetable;
        }

        return $finalList;
    }

    /**
     * createTimetable
     * 概要: 親テーブル(timetables)に新規レコードを作成する
     * 引数: $courseId - コースID
     *       $startDate - 開始日
     *       $endDate - 終了日
     *       $name - 時間割り名（デフォルトは コース名）
     * 戻り値: 作成された時間割りID
     */
    public function createTimetable($courseId, $startDate, $endDate, $name='新規時間割') {
        $sql = "INSERT INTO timetables (course_id, start_date, end_date, timetable_name, status_type) 
                VALUES (:courseId, :startDate, :endDate, :name, 1)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':courseId', $courseId, PDO::PARAM_INT);
        $stmt->bindValue(':startDate', $startDate, PDO::PARAM_STR);
        $stmt->bindValue(':endDate', $endDate, PDO::PARAM_STR);

        // コース名を割り当てる
        try {
            $courseSql = "SELECT course_name FROM course WHERE course_id = :courseId";
            $courseStmt = $this->pdo->prepare($courseSql);
            $courseStmt->bindValue(':courseId', $courseId, PDO::PARAM_INT);
            $courseStmt->execute();
            $courseRow = $courseStmt->fetch(PDO::FETCH_ASSOC);
            if ($courseRow) {
                $name = $courseRow['course_name'];
            }
        } catch (PDOException $e) {
            error_log("TimetableRepository Error (fetching course name): " . $e->getMessage());
        }

        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * updateTimetable
     * 概要: 親テーブル(timetables)の日付などを更新
     * 引数: $id - 更新対象の時間割りID
     *       $startDate - 新しい開始日
     *       $endDate - 新しい終了日
     * 戻り値: なし
     */
    public function updateTimetable($id, $startDate, $endDate) {
        $sql = "UPDATE timetables SET start_date = :startDate, end_date = :endDate WHERE timetable_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':startDate', $startDate, PDO::PARAM_STR);
        $stmt->bindValue(':endDate', $endDate, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * deleteDetailsByTimetableId
     * 概要: 指定された時間割IDに関連する詳細データを全削除する
     * 引数: $timetableId - 削除対象の時間割ID
     * ※外部キー制約(ON DELETE CASCADE)により、timetable_detail_teachers も自動で消える設計になっています
     * 戻り値: なし
     */
    public function deleteDetailsByTimetableId($timetableId) {
        $sql = "DELETE FROM timetable_details WHERE timetable_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $timetableId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * addDetail
     * 概要: timetable_details（コマと科目の紐づけ）を登録
     * 引数: $timetableId - 時間割ID
     *       $day - 曜日 (1=月曜, 2=火曜, ...)
     *       $period - 時限 (1,2,3,...)
     *       $subjectId - 科目ID
     * 戻り値: 新しく作られた detail_id
     */
    public function addDetail($timetableId, $day, $period, $subjectId) {
        $sql = "INSERT INTO timetable_details (timetable_id, day_of_week, period, subject_id) 
                VALUES (:tId, :day, :period, :sId)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':tId', $timetableId, PDO::PARAM_INT);
        $stmt->bindValue(':day', $day, PDO::PARAM_INT);
        $stmt->bindValue(':period', $period, PDO::PARAM_INT);
        $stmt->bindValue(':sId', $subjectId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * addDetailTeacher
     * 概要: timetable_detail_teachers（先生・教室の割り当て）を登録
     * 引数: $detailId - 対応する timetable_details の detail_id
     *       $teacherId - 担当教員ID
     *       $roomId - 教室ID（省略可能）
     * 戻り値: なし
     */
    public function addDetailTeacher($detailId, $teacherId, $roomId = null) {
        $sql = "INSERT INTO timetable_detail_teachers (detail_id, teacher_id, room_id) 
                VALUES (:dId, :teacherId, :roomId)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':dId', $detailId, PDO::PARAM_INT);
        $stmt->bindValue(':teacherId', $teacherId, PDO::PARAM_INT);
        
        if ($roomId) {
            $stmt->bindValue(':roomId', $roomId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':roomId', null, PDO::PARAM_NULL);
        }
        
        $stmt->execute();
    }

    /**
     * deleteTimetable
     * 概要: 指定された時間割りIDのレコードを削除する
     * 引数: $id - 削除対象の時間割りID
     * 戻り値: int 削除された行数 (1なら成功、0なら対象なし)
     */
    public function deleteTimetable($id) {
        $sql = "DELETE FROM timetables WHERE timetable_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}