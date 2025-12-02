<?php
// =================================================================
// 0. 設定と初期化
// =================================================================

// データベース接続設定 (DSNをあなたの環境に合わせて修正してください)
$dsn = "mysql:host=localhost;dbname=test;charset=utf8mb4"; 
$user = "root";
$pass = "root";

// === 日付と曜日の判定ロジック ===
date_default_timezone_set('Asia/Tokyo');

// ユーザーがフォームから送信した日付を取得。なければ本日の日付を設定。
$selected_date = $_POST['selected_date'] ?? date('Y-m-d');

// 選択された日付から曜日を計算
$timestamp = strtotime($selected_date);
$day_map_full = [
    0 => '日', 1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金', 6 => '土'
];
$day_map_weekday = ['月', '火', '水', '木', '金'];

// データベース検索用の曜日（例: '月'）を決定
$selected_day_jp = $day_map_full[date('w', $timestamp)];

// 授業がある平日かどうかを判定
$is_weekday_schedule = in_array($selected_day_jp, $day_map_weekday);

// =================================================================
// 1. HTMLフォームの出力
// =================================================================
echo '<!DOCTYPE html>';
echo '<html lang="ja">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<title>日付指定時間割</title>';
echo '<style>
        body { font-family: sans-serif; margin: 20px; }
        h1 { font-size: 24px; }
        h2 { font-size: 20px; color: #333; }
        .error { color: red; font-weight: bold; }
        table { border-collapse: collapse; margin-top: 15px; width: 50%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .no-schedule { color: #888; font-weight: bold; }
      </style>';
echo '</head>';
echo '<body>';
echo '<h1>日付指定時間割表示</h1>';

// 日付選択フォーム (type="date"を使用)
echo '<form method="POST" action="">';
echo '<label for="selected_date">日付を選択してください:</label>';
echo '<input type="date" name="selected_date" id="selected_date" value="' . htmlspecialchars($selected_date) . '">';
echo '<button type="submit">時間割を表示</button>';
echo '</form>';

echo "<h2>" . htmlspecialchars($selected_date) . "（{$selected_day_jp}曜日）の授業</h2>";

// 土日判定
if (!$is_weekday_schedule) {
    echo '<p class="no-schedule">' . $selected_day_jp . '曜日のため、通常授業はありません。</p>';
    echo '</body></html>';
    exit;
}

// =================================================================
// 2. データベースへの接続とデータ取得
// =================================================================
try {
    // 接続
    $db = new PDO($dsn, $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQLクエリの準備 (WHERE句で計算した曜日を絞り込み)
    // 注意: subject_name はあなたのデータベースの科目列名に一致している必要があります。
    $sql = "SELECT period, subject_name, teacher, room FROM subject WHERE day_of_week = :day ORDER BY period ASC;";

    // 準備したSQLクエリをセット
    $stmt = $db->prepare($sql);

    // パラメータをバインド
    $stmt->bindParam(':day', $selected_day_jp); // '月'〜'金'の曜日を渡す
    
    // 実行
    $res = $stmt->execute();

    // 値の取得と表示
    if($res) {
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($all)) {
            echo "<p>選択された日付（{$selected_day_jp}曜日）の時間割データは登録されていません。</p>";
        } else {
            // HTMLテーブルで整形して表示
            echo '<table>';
            echo '<tr><th>時限</th><th>科目名</th><th>担当</th><th>教室</th></tr>';
            
            foreach($all as $item) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($item["period"]) . '</td>';
                echo '<td>' . htmlspecialchars($item["subject_name"]) . '</td>';
                echo '<td>' . htmlspecialchars($item["teacher"]) . '</td>';
                echo '<td>' . htmlspecialchars($item["room"]) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        }
    }

} catch(PDOException $e) {
    // エラーメッセージの出力
    echo "<p class='error'>データベース接続またはデータ取得失敗</p>";
    echo "エラー詳細: " . htmlspecialchars($e->getMessage());
}
// =================================================================
// 3. 画面遷移リンク（フッター）
// =================================================================
echo '<footer>';
echo '<div class="footer-container">';

// ↓↓↓ ここを修正！余計なパスを書かず、ファイル名だけにします ↓↓↓
echo '<a href="index.html" class="back-button">前のページに戻る</a>'; 

echo '</div>';
echo '</footer>';
echo '</body></html>';
?>