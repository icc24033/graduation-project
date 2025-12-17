<?php

// Content-Type ヘッダーをプレーンテキストに設定すると、ブラウザで var_dump の出力が見やすくなります。
header('Content-Type: text/plain; charset=UTF-8');

echo "--- POSTデータ受信テスト結果 ---\n\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. POSTデータ全体を表示
    echo "## 1. \$_POST の全体構造\n";
    // students[i][...] 形式のデータがどのように配列として構造化されているかを確認します。
    var_dump($_POST);

    echo "\n----------------------------------------\n\n";

    // 2. students 配列の中身を詳細に表示
    if (isset($_POST['students']) && is_array($_POST['students'])) {
        echo "## 2. 'students' 配列の中身の詳細\n";
        
        // 配列の要素数を確認
        echo "受信した学生アカウント数: " . count($_POST['students']) . "件\n\n";
        
        // 各学生データの構造を詳細に表示
        foreach ($_POST['students'] as $index => $student_data) {
            echo "--- インデックス [{$index}] の学生データ ---\n";
            // 期待されるキー: student_id, name, course_id
            var_dump($student_data);
            echo "\n";
        }
    } else {
        echo "## 2. 'students' 配列が見つかりません。\n";
        echo "データが正しく students[...] 形式で送信されていません。\n";
    }
    
} else {
    echo "POSTリクエストではありません。";
}

?>