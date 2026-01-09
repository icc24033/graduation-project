<?php
// ClassDetailEditersController.php
// 授業詳細編集に関するコントローラークラス(class_detail_edit)

require_once __DIR__ . '/../../../classes/security/SecurityHelper.php';
require_once __DIR__ . '/../../../classes/repository/home/HomeRepository.php';

class ClassDetailEditersController
{
    /**
     * メイン画面表示処理
     */
    public function index()
    {
        // 1. セッション設定（SSO維持など）
        // ※必要に応じてセッション設定を行うコードを追加します。
        HomeRepository::session_resetting();

        // 2. ログインチェック
        // 未ログインなら弾く処理はコントローラー（またはその親）の責務
        SecurityHelper::requireLogin();

        // 3. 表示に必要なデータの取得
        // 必要に応じてデータベースからデータを取得するコードを追加します。
        
        // ビュー（画面）の読み込み
        // class_detail_editers.php を読み込む
        require_once __DIR__ . '/../../../../public/teacher/class_detail_edit/class_detail_edit.php';
    }
}