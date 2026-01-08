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

        // 5月なら卒業生を削除するかどうかの判断をする
        if (date('n') === '5') {
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