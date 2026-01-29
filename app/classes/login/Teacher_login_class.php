<?php
// Teacher_login_class.php
// 先生アカウントのログイン情報を管理するクラス
// LoginUser インターフェースを実装
require_once __DIR__ . '/LoginUser.php';

class TeacherLogin implements LoginUser {
    private string $teacherId;
    private string $gradeName; // 'master@icc_ac.jp' など

    public function __construct(string $teacherId, string $gradeName) {
        $this->teacherId = $teacherId;
        $this->gradeName = $gradeName;

        $month = date('n');
        if ($month === '4') {
            // 4月は進級処理
            require_once __DIR__ . '/../../functions/master/grade_increment.php';
        } elseif ($month === '5') {
            // 5月は卒業生削除処理
            require_once __DIR__ . '/../../functions/master/graduate_delete.php';
        }
    }

    public function getUserId(): string {
        return $this->teacherId;
    }

    public function getUserGrade(): string {
        return $this->gradeName;
    }

    public function getHomeUrl(): string {
        // 先生用ホーム（リダイレクト用）へのパス
        return 'redirect.php';
    }
}