<?php
// UserRoundController.php
// アカウント編集選択画面のコントローラークラス
//セキュリティヘルパーの読み込み
require_once __DIR__ . '/../../../classes/security/SecurityHelper.php';

class UserRoundController
{
    /**
     * メイン処理
     */
    public function index()
    {
        // ここにメイン処理を実装します
        // 例: ビューのレンダリング、データの取得など
        require_once __DIR__ . '/../../../../public/master/user_round/user_round.php';
    }
}