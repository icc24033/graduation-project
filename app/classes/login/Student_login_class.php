<?php
// Student_login_class.php
// studentを識別するために必要な情報を管理するクラス
// LoginUser インターフェイスを実装
require_once __DIR__ . '/LoginUser.php';

// Student_login_class.php
class StudentLogin implements LoginUser {
    private string $studentId;
    private string $userGrade; // DBから取得したgrade
    private string $courseId;  // DBから取得したcourse_id
    
    // コンストラクタで必要な情報をすべて受け取る
    public function __construct(string $studentId, string $userGrade, string $courseId) {
        $this->studentId = $studentId;
        $this->userGrade = $userGrade;
        $this->courseId  = $courseId;
    }

    public function getUserId(): string {
        return $this->studentId;
    }

    public function getUserGrade(): string {
        return $this->userGrade; // DBから取得した値を返す
    }
    
    // 生徒特有のメソッド: コースIDを返す
    public function getCourseId(): string {
        return $this->courseId;
    }

    public function getHomeUrl(): string {
        return '../student/student_home_dummy.html';
    }
}