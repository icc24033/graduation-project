<?php
// Master.php
// マスタアカウントの機能カードを定義

// 親クラスを読み込むパスの指定
require_once __DIR__ . '/User_class.php';

class Master extends User_MasAndTeach {
    
    // コンストラクタで親クラスのコンストラクタを呼び出し、権限を 'master' に設定
    public function __construct(string $userId) {
        parent::__construct($userId, 'master@icc_ac.jp');
    }

    public function getFunctionCardsHtml(array $links): string {
        // マスタアカウント用の全機能カード
        $html = <<<HTML
            <a href="{$links['link_time_table_create']}"><div class="card"><img class="card_icon_calendar-plus" src="images/calendar-plus.png"><p class="card_main">時間割り作成</p><p class="card_sub">期間を設定して時間割を<br>作成します。</p></div></a>
            <a href="{$links['link_time_table_edit']}"><div class="card"><img class="card_icon_square-pen" src="images/square-pen.png"><p class="card_main">時間割り変更</p><p class="card_sub">編集したいコースごとに時間割<br>を変更します。</p></div></a>
            <a href="{$links['link_account_edit']}"><div class="card"><img class="card_icon_user-round" src="images/user-round-cog.png"><p class="card_main">アカウント編集</p><p class="card_sub">アカウントの情報を確認、編集<br>することができます。</p></div></a>
            <a href="{$links['link_notification_edit']}"><div class="card"><img class="card_icon_book" src="images/icon-book.png"><p class="card_main">授業科目編集</p><p class="card_sub">授業科目を確認、編集<br>することができます。</p></div></a>
            <a href="{$links['link_subject_edit']}"><div class="card"><img class="card_icon_clipboard-list" src="images/clipboard-list.png"><p class="card_main">授業詳細編集</p><p class="card_sub">授業詳細を編集します。</p></div></a>
            <a href="{$links['link_time_table_view']}"><div class="card"><img class="card_icon_calendar-clock" src="images/calendar-clock.png"><p class="card_main">時間割り閲覧</p><p class="card_sub">選択したコースごとに<br>時間割を閲覧します。</p></div></a>
HTML;
        return $html;
    }
}