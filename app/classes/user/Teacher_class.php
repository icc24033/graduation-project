<?php
// Teacher.php
// 先生アカウントの機能カードを定義

// 親クラスを読み込むパスの指定
require_once __DIR__ . '/User_class.php';

class Teacher extends User_MasAndTeach {

    // コンストラクタで親クラスのコンストラクタを呼び出し、権限を 'teacher' に設定
    public function __construct(string $userId) {
        parent::__construct($userId, 'teacher@icc_ac.jp');
    }

    public function getFunctionCardsHtml(array $links): string {
        // 先生アカウント用のカード (授業詳細編集、時間割り閲覧、アカウント編集)
        $html = <<<HTML
            <a href="{$links['link_time_table_edit']}"><div class="card"><img class="card_icon_square-pen" src="images/square-pen.png"><p class="card_main">時間割り編集</p><p class="card_sub">編集したいコースごとに<br>時間割を編集します。</p></div></a>
            <a href="{$links['link_time_table_view']}"><div class="card"><img class="card_icon_calendar-clock" src="images/calendar-clock.png"><p class="card_main">時間割り閲覧</p><p class="card_sub">選択したコースごとに<br>時間割を閲覧します。</p></div></a>
            <a href="{$links['link_subject_edit']}"><div class="card"><img class="card_icon_clipboard-list" src="images/clipboard-list.png"><p class="card_main">授業詳細編集</p><p class="card_sub">受け持つ授業詳細を編集します。</p></div></a>
HTML;
        return $html;
    }
}