<?php
// クラスの読み込み（パスは環境に合わせて調整してください）
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';

class TimetableService {

    /**
     * getAllTimetableData
     * 概要：すべてのコースの時間割データを取得し、JSが読み込める形式で返す関数
     * 戻り値：すべての時間割データの配列
     */
    public function getAllTimetableData() {
        $allTimetables = [];

        try {
            // 1. 各リポジトリの取得
            $courseRepo = RepositoryFactory::getCourseRepository();
            $timetableRepo = RepositoryFactory::getTimetableRepository();

            // 2. コース全取得
            $courses = $courseRepo->getAllCourses();

            // 3. コースごとにループして時間割を取得
            foreach ($courses as $course) {
                $courseId = $course['course_id'];
                
                // 特定のコースの時間割を取得
                $timetables = $timetableRepo->getTimetablesByCourseId($courseId);

                // データがあれば結合
                if (!empty($timetables)) {
                    // array_mergeだとキーが連番でリセットされる可能性があるため、単純に追加
                    foreach ($timetables as $t) {
                        $allTimetables[] = $t;
                    }
                }
            }

        } catch (Exception $e) {
            // エラーログ出力など
            error_log("TimetableService Error: " . $e->getMessage());
            return []; // エラー時は空配列を返す
        }

        return $allTimetables;
    }
}