<?php
// CreateTimetableController.php

// 必要なファイルの読み込み
require_once __DIR__ . '/../../../classes/security/SecurityHelper.php';
require_once __DIR__ . '/../../../classes/repository/home/HomeRepository.php';

class CreateTimetableController extends HomeRepository
{
    /**
     * メイン画面表示処理
     */
    public function index()
    {
        // 1. セッション設定（SSO維持など）
        // ※HomeRepositoryなどの共通クラスに依存
        parent::session_resetting();

        // 2. ログインチェック
        // 未ログインなら弾く処理はコントローラー（またはその親）の責務
        SecurityHelper::requireLogin();

        // 3. 表示に必要なデータの取得
        // 本来はここでRepositoryを使ってDBからデータを取ってきます。
        // 今はセッションからアイコンを取得する処理のみ記述します。
        $user_picture = $_SESSION['user_picture'] ?? 'images/default_icon.png';
        
    }
}