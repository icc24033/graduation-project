<?php
// ViewHelper.php
// ビュー関連のヘルパークラス
// Webアプリケーション全体で使用されるビュー表示機能(ドロップダウンリストなど)を提供するクラス

class ViewHelper {
    
    /**
     * セレクトボックス（ドロップダウン）のオプションHTMLを生成する
     * * @param array $items データ配列（例: [['id'=>1, 'name'=>'A'], ...]）
     * @param string $valueKey value属性に使うキー名（例: 'course_id'）
     * @param string $labelKey 表示テキストに使うキー名（例: 'course_name'）
     * @param mixed $selectedValue 選択状態にする値（任意）
     * @return string HTML文字列
     */
    public static function renderSelectOptions(array $items, string $valueKey, string $labelKey, bool $hasEmptyOption = true, $selectedValue = null): string {
        $html = '';
        if($hasEmptyOption == true){
            $html .= '<option value="">選択してください</option>';
        }

        foreach ($items as $item) {
            // データ配列にキーが存在するかチェック（安全対策）
            if (!isset($item[$valueKey]) || !isset($item[$labelKey])) {
                continue;
            }
            // XSS対策（エスケープ処理）
            $value = htmlspecialchars($item[$valueKey], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($item[$labelKey], ENT_QUOTES, 'UTF-8');
            
            $selected = ((string)$item[$valueKey] === (string)$selectedValue) ? 'selected' : '';
            
            $html .= "<option value=\"{$value}\"{$selected}>{$label}</option>";
        }
        
        return $html;
    }

    /**
     * ドロップダウンメニュー用のリンク付きリストアイテムを生成する
     * <ul><li><a href="#">Label</a></li></ul> の中身を生成
     * * @param array $items データ配列
     * @param string $valueKey data-value属性に使うキー名
     * @param string $labelKey 表示テキストに使うキー名
     * @return string HTML文字列
     */
    public static function renderDropdownList(array $items, string $valueKey, string $labelKey): string {
        $html = '';
        foreach ($items as $item) {
            $value = htmlspecialchars($item[$valueKey], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($item[$labelKey], ENT_QUOTES, 'UTF-8');
            
            // aタグを使用せず、liタグに直接スタイルとデータを設定
            // cursor-pointerクラスでクリック可能であることを視覚的に示します
            $html .= "<li data-value=\"{$value}\" class=\"px-4 py-2 hover:bg-gray-100 cursor-pointer\"><a href=\"#\">{$label}</a></li>";
        }
        return $html;
    }

    /**
     * Master_classのように、任意のリストHTMLを生成する汎用メソッド例
     * サイドバーのリスト表示などで利用可能
     */
    public static function renderListItems(array $items, string $idKey, string $textKey): string {
        $html = '';
        foreach ($items as $item) {
            $id = htmlspecialchars($item[$idKey], ENT_QUOTES, 'UTF-8');
            $text = htmlspecialchars($item[$textKey], ENT_QUOTES, 'UTF-8');
            
            // 必要に応じてクラスや属性を調整してください
            $html .= "<li class=\"p-2 hover:bg-gray-100 cursor-pointer\" data-id=\"{$id}\">{$text}</li>";
        }
        return $html;
    }
}
