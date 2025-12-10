<?php
require_once __DIR__ . '/LoginUser.php';

// Student_login_class.php
// 生徒アカウントのログイン情報を管理するクラス
// LoginUser インターフェースを実装
class StudentLogin implements LoginUser {
    private string $studentId;
    
    public function __construct(string $studentId) {
        $this->studentId = $studentId;
    }

    public function getUserId(): string {
        return $this->studentId;
    }

    public function getUserGrade(): string {
        return 'student';
    }

    public function getHomeUrl(): string {
        // 生徒用ホームへのパス
        return '../student/student_home_dummy.html';
    }
}