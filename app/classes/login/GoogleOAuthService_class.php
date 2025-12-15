<?php
// GoogleOAuthService_class.php
// Google OAuth 2.0 認証を扱うサービスクラス
// クライアントID、クライアントシークレット、リダイレクトURIを管理し、
// 認証URLの生成、トークン交換、ユーザー情報取得のメソッドを提供する

class GoogleOAuthService {
    private string $clientId;     // クライアントID
    private string $clientSecret; // クライアントシークレット
    private string $redirectUri;  // リダイレクトURI

    // 各エンドポイントをクラス定数として定義
    // const (変更不可)
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    private const USERINFO_ENDPOINT = 'https://openidconnect.googleapis.com/v1/userinfo';

    // コンストラクタでクライアント情報を初期化
    public function __construct(string $id, string $secret, string $uri) {
        $this->clientId = $id;
        $this->clientSecret = $secret;
        $this->redirectUri = $uri;
    }

    // 認証URLを生成するメソッド
    // - state パラメーターを引数として受け取る
    public function getAuthUrl(string $state): string {
        $params = [
            'client_id'     => $this->clientId,     // クライアントID
            'redirect_uri'  => $this->redirectUri,  // リダイレクトURI
            'response_type' => 'code',              // 認可コードを要求
            'scope'         => 'email profile',     // 要求するスコープ
            'state'         => $state,              // CSRF対策のstateパラメーター
            'prompt'        => 'select_account',    // アカウント選択画面を強制
            'access_type'   => 'online'             // オンラインアクセス
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    // 認可コードをアクセストークンに交換するメソッド
    // - code　パラメーターを引数として受け取る（認可コード）
    // - 認証を受けた場合、アクセストークンを返す
    public function fetchAccessToken(string $code): string {
        $params = [
            'code'          => $code,               // 認可コード
            'client_id'     => $this->clientId,     // クライアントID
            'client_secret' => $this->clientSecret, // クライアントシークレット
            'redirect_uri'  => $this->redirectUri,  // リダイレクトURI
            'grant_type'    => 'authorization_code' // 認可コードグラント(認証を受けた場合、アクセストークンを取得するフロー)
        ];
        // cURLリクエストを送信する共通メソッドを呼び出す
        $result = $this->sendRequest(self::TOKEN_ENDPOINT, $params, null, "トークン交換");
        return $result['access_token'];
    }

    // トークンを使ってユーザー情報を取得するメソッド
    // - token パラメーターを引数として受け取る（アクセストークン）
    public function fetchUserInfo(string $token): array {
        return $this->sendRequest(self::USERINFO_ENDPOINT, null, $token, "ユーザー情報取得");
    }

    // cURLリクエストを送信する共通メソッド
    // - url: エンドポイントURL
    // - data: POSTデータ（nullの場合はGETリクエスト）
    // - accessToken: アクセストークン（nullの場合はAuthorizationヘッダーを設定しない）
    // - error_prefix: エラーメッセージの接頭辞
    // - 戻り値: レスポンスの連想配列
    private function sendRequest(string $url, ?array $data, ?string $accessToken, string $error_prefix): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // POSTデータがある場合はPOSTリクエストに設定
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        // アクセストークンがある場合はAuthorizationヘッダーを設定
        $headers = [];
        if ($accessToken !== null) {
            $headers[] = "Authorization: Bearer {$accessToken}";
        }
        // ヘッダーが設定されている場合はcURLオプションにセット
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        // SSL設定
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // CA証明書のパスを指定
        $cafile_path = __DIR__ . '/../../../config/cacert.pem';
        curl_setopt($ch, CURLOPT_CAINFO, $cafile_path);

        // cURLリクエストを実行
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);

        // cURLセッションを閉じる
        curl_close($ch);
        
        // エラーチェック
        if ($curl_error) {
            die("{$error_prefix} cURLエラー: " . $curl_error);
        }
        if ($http_code !== 200) {
            die("{$error_prefix} HTTPコード {$http_code} レスポンス: {$response}");
        }
        
        $result = json_decode($response, true);
        if (isset($result['error'])) {
            $desc = $result['error_description'] ?? $result['error'];
            die("{$error_prefix} Google APIエラー: " . $desc);
        }

        // 正常終了、レスポンスを返す
        return $result;
    }
}