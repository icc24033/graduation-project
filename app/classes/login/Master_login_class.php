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

        // 5月なら卒業生を削除するかどうかの判断をする
        if (date('n') === '5') {
            require_once __DIR__ . '/../../functions/master/graduate_delete.php';
        }
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