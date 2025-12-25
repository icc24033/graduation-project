<?php
require_once __DIR__ . '/../../../config/secrets_local.php';
// SecurityHelper.php
// セキュリティ関連のヘルパークラス
// Webアプリケーション全体で使用されるセキュリティ機能を提供するクラス
class SecurityHelper {
    /**
     * １．クロスサイトスクリプティング（XSS）対策のためのエスケープ処理
     * 概要：・特殊文字を HTML エンティティに変換することで、悪意のあるスクリプトの実行を防ぐ
     * 　　　・HTML を表示する際は、必ずこのメソッドを使用してエスケープ処理を行う
     * @param string $input
     * @return string
     */
    public static function escapeHtml(string $input): string {
        return htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * ２．ページ全体のセキュリティヘッダーを設定
     * 概要：・XSS やクリックジャッキングなどの攻撃を防ぐために、適切なセキュリティヘッダーを設定する
     * 　　　・このメソッドは、必ず各ページの最初に呼び出す
     * 使用方法： SecurityHelper::applySecureHeaders();　これをページの最初に書く
     * @param void
     * @return void
     */
    public static function applySecureHeaders(): void {
        // クリックジャッキング対策 (iframeでの読み込み禁止)
        header('X-Frame-Options: DENY');
        
        // XSS対策 (ブラウザのXSSフィルターを強制有効化)
        header('X-XSS-Protection: 1; mode=block');
        
        // MIMEタイプスニフィング対策 (ファイルの内容をブラウザに勝手に推測させない)
        header('X-Content-Type-Options: nosniff');
        
        // HTTPS強制 (HSTS) ※常時SSL化されている場合のみ有効
        // header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        
        // リファラポリシー (プライバシー保護: ドメインまたぎのリファラ（アクセス経路）を送らない)
        header('Referrer-Policy: same-origin');
    }

    /**
     * ３．ログインチェック（ゲートキーパー）
     * 概要：・セッションにログイン情報が存在するかを確認し、未ログインの場合はログインページへリダイレクトする
     * 　　　・このメソッドは、各ページの最初に呼び出す
     * 使用方法： SecurityHelper::requireLogin();　これをページの最初に書く
     * @param void
     * @return void
     */
    public static function requireLogin(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            // ログイン画面へリダイレクト
            // ※呼び出し元の階層に合わせてパスを調整する（絶対パスを使用）
            header('Location: ' . LOGIN_PAGE_URL);
            exit();
        }
    }

    /**
     * ４．ページ遷移順序の管理
     * 概要：・特定のページに直接アクセスすることを防ぎ、正しい遷移順序を強制する
     * 　　　・このメソッドは、各ページの最初に呼び出す
     *
     * メソッド1：setTransitionToken
     * 概要：・セッションに遷移トークンを保存し、遷移元ページで生成したトークンをセッションに保存する
     * 　　　・遷移先ページでこのトークンを確認することで、正しい遷移順序を強制する(requireTransitionTokenとセットで使用)
     * 　　　・呼び出す場所は遷移元ページの最後
     * 使用方法： SecurityHelper::setTransitionToken('unique_key');　これを遷移元ページの最後に書く
     * @param string $key 手形の名前
     * @return void
     * 
     * メソッド2：requireTransitionToken
     * 概要：・セッションに保存されたトークンと、リクエストで送信されたトークンを比較し、一致しない場合はエラーページへリダイレクトする
     * 　　　・トークンは、遷移元ページで生成し、セッションに保存しておく必要がある
     *　　　 ・呼び出す場所は遷移先ページの最初
     * 使用方法： SecurityHelper::requireTransitionToken('unique_key');　これを遷移先ページの最初に書く
     * @param string $key 手形の名前
     * @param bool $keepToken trueなら確認後も手形を残し（リロード対策）、falseなら没収（遷移の厳格化）
     * @return void
     */

    // メソッド1：setTransitionToken
    public static function setTransitionToken(string $key): void {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // セッションに遷移トークンを保存
        $_SESSION['transition_tokens'][$key] = bin2hex(random_bytes(16)); // ランダムなトークンを生成して保存
    }

    //  メソッド2：requireTransitionToken
    public static function requireTransitionToken(string $key, bool $keepToken = true): void {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['transition_tokens'][$key])) {
            // 手形がない場合、不正な遷移とみなしてログイン画面またはエラー画面へ
            header('Location: ' . LOGIN_PAGE_URL);
            exit();
        }

        // $keepToken が false の場合は、一度使ったら手形を破棄する
        if (!$keepToken) {
            unset($_SESSION['transition_tokens'][$key]);
        }
    }

    /**
     * ５．CSRFトークンの生成と検証
     * 概要：・フォーム送信時にCSRFトークンを生成し、セッションに保存する
     * 　　　・フォーム送信時にトークンを検証し、不正なリクエストを防ぐ
     * 
     * メソッド1：generateCsrfToken
     * 概要：・セッションにCSRFトークンを生成し保存する
     * 　　　・フォームページでこのメソッドを呼び出し、生成されたトークンをフォームに埋め込む
     *　　　 ・呼び出す場所はフォームページの最初
     * 使用方法： SecurityHelper::generateCsrfToken();　これをフォームページの最初に書く
     * @param void
     * @return string 生成されたCSRFトークン
     * 
     * メソッド2：validateCsrfToken
     * 概要：・セッションに保存されたCSRFトークンと、リクエストで送信されたトークンを比較し、一致しない場合は不正なリクエストとみなす
     * 　　　・呼び出す場所はフォーム処理ページの最初
     * 使用方法： SecurityHelper::validateCsrfToken($_POST['csrf_token']);　これをフォーム処理ページの最初に書く
     * @param string|null $token リクエストで送信されたCSRFトークン
     * @return bool トークンが有効かどうか
     */

    // メソッド1：generateCsrfToken
    public static function generateCsrfToken(): string {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // メソッド2：validateCsrfToken
    public static function validateCsrfToken(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        // hash_equals はタイミング攻撃に強い比較関数
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * ６．セッション固定攻撃対策
     * 概要：・セッションIDを定期的に再生成し、セッション固定攻撃を防ぐ
     * 　　　・ログイン直後や重要な操作の直後にこのメソッドを呼び出す
     * 使用方法： SecurityHelper::regenerateSession();　これをログイン直後や重要操作直後に書く
     * @param void
     */
    public static function regenerateSession(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // セッションIDを新しくし、古いセッションファイルを削除する
        session_regenerate_id(true);
    }
}
