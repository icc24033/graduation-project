<?php
// HelpController.php

// ヘルプコントローラーの実装
class HelpController {
    /**
     * ヘルプの読み込み
     */
    public function index($back_page) {
        
        if ($back_page == 1) {
            require_once 'help1.html';
        }
        else if ($back_page == 2) {
            require_once 'help2.html';
        }
        else if ($back_page == 3) {
            require_once 'help3.html';
        }
        else if ($back_page == 4) {
            require_once 'help4.html';
        }
        else if ($back_page == 5) {
            require_once 'help5.html';
        }
        else if ($back_page == 6) {
            require_once 'help6.html';
        }
        else {
            // デフォルトのヘルプページ
            require_once 'help1.html';
        }
    }
}