<?php
// TimetableController.php

// 必要なリポジトリやヘルパーを読み込む
// ディレクトリ構成図に基づき、相対パスを調整
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';

class TimetableController {
    
    /**
     * 授業変更画面の初期表示データを取得する
     * @return array ビューに渡すデータの配列
     */
    public function edit() {
        $data = [
            'courseList' => [],
            'error_message' => ''
        ];

        try {
            // リポジトリからデータを取得
            $courseRepo = RepositoryFactory::getCourseRepository();
            $data['courseList'] = $courseRepo->getAllCourses();
            
            // データ取得後にDB接続を閉じる
            RepositoryFactory::closePdo();

        } catch (Exception $e) {
            error_log("TimetableController Error: " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }

        return $data;
    }
}