<?php
// app/teacher/class_detail_edit/backend_add_detail.php

class BackendAddDetail {

    /**
     * カレンダー表示・データ復旧用に全データを取得する
     */
    public static function getAllClassDetails($teacher_id) {
        $pdo = self::getDbConnection();
        try {
            // 1. まずは授業の基本データ(日付、内容、ステータス)を取得
            $sql = "SELECT lesson_date, content, status FROM class_detail WHERE teacher_id = :teacher_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':teacher_id' => $teacher_id]);
            $rows = $stmt->fetchAll();

            $result = [];
            foreach ($rows as $row) {
                $date = $row['lesson_date'];

                // 2. その日の「持ち物」を bring_object テーブルから取得
                $stmt2 = $pdo->prepare("SELECT object_name FROM bring_object WHERE lesson_date = :date");
                $stmt2->execute([':date' => $date]);
                $belongingsArray = $stmt2->fetchAll(PDO::FETCH_COLUMN);

                // 3. JSが画面に表示するために必要な形式へ整理
                $result[$date] = [
                    "slot" => "1限", 
                    "content" => $row['content'] ?? "", // 授業詳細
                    "belongings" => implode('、', $belongingsArray), // 持ち物を「、」で結合
                    "status" => $row['status']
                ];
            }
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * テンプレート（よく使う持ち物）リストを取得
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

    public static function getTeacherSubjects($teacher_id) {
        $pdo = self::getDbConnection();
        try {
            $stmt = $pdo->prepare("SELECT teacher_id, subject_name FROM subjects WHERE teacher_id = :teacher_id");
            $stmt->execute([':teacher_id' => $teacher_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public static function saveClassDetail($date, $content, $status, $belongings, $teacher_id) {
        $pdo = self::getDbConnection();
        try {
            $pdo->beginTransaction();

            // 授業詳細の保存
            $sql1 = "REPLACE INTO class_detail (lesson_date, content, status, teacher_id) 
                     VALUES (:lesson_date, :content, :status, :teacher_id)";
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute([
                ':lesson_date' => $date,
                ':content'     => $content,
                ':status'      => $status,
                ':teacher_id'  => $teacher_id
            ]);

            // 持ち物の更新（一旦消して入れ直す）
            $stmtDel = $pdo->prepare("DELETE FROM bring_object WHERE lesson_date = :lesson_date");
            $stmtDel->execute([':lesson_date' => $date]);

            if (!empty($belongings)) {
                $items = explode('、', $belongings); 
                $stmt2 = $pdo->prepare("INSERT INTO bring_object (lesson_date, object_name) VALUES (:lesson_date, :object_name)");
                foreach ($items as $item) {
                    $trimmedItem = trim($item);
                    if ($trimmedItem !== "") {
                        $stmt2->execute([':lesson_date' => $date, ':object_name' => $trimmedItem]);
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

    public static function deleteClassDetail($date, $teacher_id) {
        $pdo = self::getDbConnection();
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM class_detail WHERE lesson_date = ? AND teacher_id = ?")->execute([$date, $teacher_id]);
            $pdo->prepare("DELETE FROM bring_object WHERE lesson_date = ?")->execute([$date]);
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function getDbConnection() {
        $dsn = "mysql:host=localhost;dbname=icc_smart_campus;charset=utf8mb4";
        return new PDO($dsn, 'root', 'root', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}