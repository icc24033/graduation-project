<?php
// app/teacher/class_detail_edit/backend_add_detail.php

class BackendAddDetail {

    /**
     * 【追加】カレンダー表示用にすべての授業ステータスを取得する
     */
    public static function getAllClassDetails() {
        $pdo = self::getDbConnection();
        try {
            // ステータスと日付を取得（カレンダーの色分けに必要）
            $sql = "SELECT lesson_date, status FROM class_detail";
            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll();

            $result = [];
            foreach ($rows as $row) {
                // ステータスコードを日本語表示用に変換
                $statusText = ($row['status'] === 'in-progress') ? '作成済み' : '作成中';
                
                // JavaScriptが扱いやすい形式に整形
                $result[$row['lesson_date']] = [
                    "slot" => "1限", // デフォルト表示用（必要ならDBにカラム追加）
                    "status" => $row['status'],
                    "statusText" => $statusText
                ];
            }
            return $result;
        } catch (Exception $e) {
            return []; // エラー時は空配列を返す
        }
    }

    /**
     * 授業詳細と持ち物を保存する
     */
    public static function saveClassDetail($date, $content, $status, $belongings) {
        $pdo = self::getDbConnection();

        try {
            $pdo->beginTransaction();

            // 1. class_detail テーブルへの保存 (上書き)
            $sql1 = "REPLACE INTO class_detail (lesson_date, content, status) VALUES (:lesson_date, :content, :status)";
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute([
                ':lesson_date' => $date,
                ':content'     => $content,
                ':status'      => $status
            ]);

            // 2. bring_object テーブルの既存データを一旦削除
            $sqlDelete = "DELETE FROM bring_object WHERE lesson_date = :lesson_date";
            $stmtDel = $pdo->prepare($sqlDelete);
            $stmtDel->execute([':lesson_date' => $date]);

            // 3. bring_object テーブルへの保存 (カンマ区切りを分割)
            if (!empty($belongings)) {
                $items = explode('、', $belongings); 
                $sql2 = "INSERT INTO bring_object (lesson_date, object_name) VALUES (:lesson_date, :object_name)";
                $stmt2 = $pdo->prepare($sql2);

                foreach ($items as $item) {
                    $trimmedItem = trim($item);
                    if ($trimmedItem !== "") {
                        $stmt2->execute([
                            ':lesson_date' => $date,
                            ':object_name' => $trimmedItem
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
     * 指定日のデータを削除する
     */
    public static function deleteClassDetail($date) {
        $pdo = self::getDbConnection();
        try {
            $pdo->beginTransaction();

            $pdo->prepare("DELETE FROM class_detail WHERE lesson_date = ?")->execute([$date]);
            $pdo->prepare("DELETE FROM bring_object WHERE lesson_date = ?")->execute([$date]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * DB接続の取得
     */
    private static function getDbConnection() {
        $host = 'localhost';
        $dbname = 'icc_smart_campus';
        $user = 'root';
        $pass = 'root'; // MAMPで接続エラーが出る場合は 'root' に書き換えてください

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}