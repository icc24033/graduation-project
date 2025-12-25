<?php
// CourseRepository.php
// コース情報を取得するリポジトリクラス

// 自動的にBaseRepositoryを継承し、DB接続を確立する
require_once __DIR__ . '/BaseRepository.php';

class CourseRepository extends BaseRepository {

    /**
     * すべてのコース一覧を取得する
     * ドロップダウンリスト表示用
     * @return array コース情報の配列
     */
    public function getAllCourses() {
        try {
            // SQLの準備
            $sql = "SELECT course_id, course_name FROM course ORDER BY course_id ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("CourseRepository Error: " . $e->getMessage());
            // エラー時は空配列を返して画面が止まらないようにする
            return [];
        }
    }

    public static function getCoursesDropdown() {
        $html = <<<HTML
            <li> </li>
HTML;
        return $html;
    }
}