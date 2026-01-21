<?php
// app/teacher/class_detail_edit/backend_add_detail.php

class BackendAddDetail {

    /**
     * 指定された先生が授業を持っている曜日を取得
     */
    public static function getTeacherScheduleDays($teacher_id) {
        $pdo = self::getDbConnection();
        try {
            $sql = "SELECT DISTINCT d.day_of_week 
                    FROM timetable_details d
                    JOIN timetable_detail_teachers dt ON d.detail_id = dt.detail_id
                    WHERE dt.teacher_id = :teacher_id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':teacher_id' => $teacher_id]);
            $days = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return array_map(function($d) {
                return ($d == 7) ? 0 : (int)$d;
            }, $days);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * カレンダー表示用に自分のデータ(teacher_id)だけを正確に取得する
     */
    public static function getAllClassDetails($teacher_id) {
        $pdo = self::getDbConnection();
        try {
            // 自分の teacher_id に紐づく授業詳細のみを取得
            $sql = "SELECT lesson_date, content, status FROM class_detail WHERE teacher_id = :teacher_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':teacher_id' => $teacher_id]);
            $rows = $stmt->fetchAll();

            $result = [];
            foreach ($rows as $row) {
                $date = $row['lesson_date'];

                // 自分の teacher_id に紐づく持ち物のみを取得
                $stmt2 = $pdo->prepare("SELECT object_name FROM bring_object WHERE lesson_date = :date AND teacher_id = :teacher_id");
                $stmt2->execute([':date' => $date, ':teacher_id' => $teacher_id]);
                $belongingsArray = $stmt2->fetchAll(PDO::FETCH_COLUMN);

                $result[$date] = [
                    "slot" => "1限", 
                    "content" => $row['content'] ?? "", 
                    "belongings" => implode('、', $belongingsArray), 
                    "status" => $row['status']
                ];
            }
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * テンプレートリストを取得
     */
    public static function getTemplateObjects($teacher_id) {
        $pdo = self::getDbConnection();
        try {
            $stmt = $pdo->prepare("SELECT object_name FROM template_objects WHERE teacher_id = :tid");
            $stmt->execute([':tid' => $teacher_id]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * テンプレートを保存
     */
    public static function saveTemplateObject($name, $teacher_id) {
        $pdo = self::getDbConnection();
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO template_objects (teacher_id, object_name) VALUES (:tid, :name)");
            return $stmt->execute([':tid' => $teacher_id, ':name' => $name]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * テンプレートを削除
     */
    public static function deleteTemplateObject($name, $teacher_id) {
        $pdo = self::getDbConnection();
        try {
            $stmt = $pdo->prepare("DELETE FROM template_objects WHERE teacher_id = :tid AND object_name = :name");
            return $stmt->execute([':tid' => $teacher_id, ':name' => $name]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 教科一覧を取得
     */
    public static function getTeacherSubjects($teacher_id) {
        $pdo = self::getDbConnection();
        try {
            $stmt = $pdo->prepare("SELECT subject_id, subject_name FROM subjects WHERE teacher_id = :teacher_id");
            $stmt->execute([':teacher_id' => $teacher_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 先生ごとにデータを保存・上書きする処理
     */
    public static function saveClassDetail($date, $content, $status, $belongings, $teacher_id) {
        $pdo = self::getDbConnection();
        try {
            $pdo->beginTransaction();

            // INSERT ... ON DUPLICATE KEY UPDATE を使用
            // 「日付 + 先生ID」が既に存在すれば中身を更新、なければ新規作成
            $sql1 = "INSERT INTO class_detail (lesson_date, content, status, teacher_id) 
                     VALUES (:lesson_date, :content, :status, :teacher_id)
                     ON DUPLICATE KEY UPDATE 
                        content = VALUES(content), 
                        status = VALUES(status)";
            
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute([
                ':lesson_date' => $date,
                ':content'     => $content,
                ':status'      => $status,
                ':teacher_id'  => $teacher_id
            ]);

            // 自分の持ち物データのみを削除して再登録
            $stmtDel = $pdo->prepare("DELETE FROM bring_object WHERE lesson_date = :lesson_date AND teacher_id = :teacher_id");
            $stmtDel->execute([':lesson_date' => $date, ':teacher_id' => $teacher_id]);

            if (!empty($belongings)) {
                $items = explode('、', $belongings); 
                $stmt2 = $pdo->prepare("INSERT INTO bring_object (lesson_date, object_name, teacher_id) VALUES (:lesson_date, :object_name, :teacher_id)");
                foreach ($items as $item) {
                    $trimmedItem = trim($item);
                    if ($trimmedItem !== "") {
                        $stmt2->execute([
                            ':lesson_date' => $date, 
                            ':object_name' => $trimmedItem,
                            ':teacher_id'  => $teacher_id
                        ]);
                    }
                }
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * 削除処理（自分のデータだけを対象にする）
     */
    public static function deleteClassDetail($date, $teacher_id) {
        $pdo = self::getDbConnection();
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM class_detail WHERE lesson_date = ? AND teacher_id = ?")->execute([$date, $teacher_id]);
            $pdo->prepare("DELETE FROM bring_object WHERE lesson_date = ? AND teacher_id = ?")->execute([$date, $teacher_id]);
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * DB接続
     */
    private static function getDbConnection() {
        $dsn = "mysql:host=localhost;dbname=icc_smart_campus;charset=utf8mb4";
        return new PDO($dsn, 'root', 'root', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}