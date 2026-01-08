<?php
// Master_login_class.php
// マスターアカウントのログイン情報を管理するクラス
// LoginUser インターフェースを実装
require_once __DIR__ . '/LoginUser.php';

class MasterLogin implements LoginUser {
    private string $masterId;
    private string $gradeName; // 'master@icc_ac.jp' など

    public function __construct(string $masterId, string $gradeName) {
        $this->masterId = $masterId;
        $this->gradeName = $gradeName;

        //　if () 追加予定
    }

    public function getUserId(): string {
        return $this->masterId;
    }

    public function getUserGrade(): string {
        return $this->gradeName;
    }

    public function getHomeUrl(): string {
        // マスター用ホーム（リダイレクト用）へのパス
        return 'redirect.php';
    }
}