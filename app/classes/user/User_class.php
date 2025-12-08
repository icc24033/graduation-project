<?php
// User_class.php
// すべてのユーザータイプに共通する基本機能と構造を定義する抽象クラス

abstract class User_MasAndTeach {
    protected string $userId; // 固有ID
    protected string $userGrade; // 権限レベル

    // コンストラクタで固有IDと権限を初期化
    public function __construct(string $userId, string $userGrade) {
        $this->userId = $userId;
        $this->userGrade = $userGrade;
    }

    // 権限に応じた機能カードのHTMLを生成する抽象メソッド
    abstract public function getFunctionCardsHtml(array $links): string;

    // 固有IDを取得するメソッド（授業編集などで利用）
    public function getUserId(): string {
        return $this->userId;
    }
}