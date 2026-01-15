<?php
// 1. データベース接続設定
$host = 'localhost'; $dbname = 'itira'; $user = 'root'; $password = 'root'; 

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) { die("DB接続エラー: " . $e->getMessage()); }

// 有効なコース情報を取得
$courseInfo = [
    'kihon'         => ['table' => 'kihon_itiran',    'name' => '基本情報'],
    'multimedia'    => ['table' => 'mariti_itiran',   'name' => 'マルチメディア'],
    'applied-info'  => ['table' => 'ouyou_itiran',    'name' => '応用情報'],
    'system-design' => ['table' => 'sisutemu_itiran', 'name' => 'システムデザイン'],
    'web-creator'   => ['table' => 'web_itiran',      'name' => 'Webクリエイター'],
    'itikumi'       => ['table' => 'itikumi',         'name' => '1年1組'],
    'nikumi'        => ['table' => 'nikumi',          'name' => '1年2組']
];

// ==========================================
// 【削除処理】
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $target_title = $_POST['subject_name'];
    $target_grade = (int)$_POST['grade'];
    $action = $_POST['action'];

    try {
        if ($action === 'delete_single') {
            $courseKey = $_POST['course_key'];
            if (isset($courseInfo[$courseKey])) {
                $table = $courseInfo[$courseKey]['table'];
                $sql = "DELETE FROM `$table` WHERE subject_name = ? AND grade = ?";
                $pdo->prepare($sql)->execute([$target_title, $target_grade]);
            }
        } elseif ($action === 'delete_all') {
            foreach ($courseInfo as $info) {
                $sql = "DELETE FROM `{$info['table']}` WHERE subject_name = ? AND grade = ?";
                $pdo->prepare($sql)->execute([$target_title, $target_grade]);
            }
        }
        header("Location: sakuzyo.php?" . http_build_query($_GET));
        exit;
    } catch (PDOException $e) {
        $db_error = "削除エラー: " . $e->getMessage();
    }
}

// 2. 検索条件・データ取得
$search_grade = $_GET['search_grade'] ?? 'all';
$search_course = $_GET['search_course'] ?? 'all';

$subjects = [];
foreach ($courseInfo as $key => $info) {
    if ($search_course !== 'all' && $search_course !== $key) continue;
    
    $sql = "SELECT grade, subject_name FROM `{$info['table']}`";
    if ($search_grade !== 'all') {
        $stmt = $pdo->prepare($sql . " WHERE grade = ?");
        $stmt->execute([$search_grade]);
    } else {
        $stmt = $pdo->query($sql);
    }

    while ($row = $stmt->fetch()) {
        $id = $row['grade'] . "_" . $row['subject_name'];
        if (!isset($subjects[$id])) {
            $subjects[$id] = [
                'grade' => $row['grade'], 'title' => $row['subject_name'],
                'courses' => [], 'course_keys' => []
            ];
        }
        $subjects[$id]['courses'][] = $info['name'];
        $subjects[$id]['course_keys'][] = $key;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>授業科目一覧 - 削除</title>
    <style>
        /* レイアウトの基盤設定 */
        html, body { height: 100%; margin: 0; overflow: hidden; }
        body { background-color: #f7f9fb; font-family: sans-serif; display: flex; flex-direction: column; }
        
        .header { 
            background-color: #ffc6c6; 
            height: 60px; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            border-bottom: 1px solid #e9d0d0; 
            flex-shrink: 0; /* 高さを固定 */
        }
        .header h1 { font-size: 20px; color: #333; margin: 0; }

        .container { 
            display: flex; 
            flex: 1; 
            overflow: hidden; /* 親要素でのスクロールを禁止 */
        }

        /* サイドバー固定 */
        .sidebar { 
            width: 220px; 
            background-color: #fff0f0; 
            border-right: 1px solid #ede0e0; 
            padding: 20px 0;
            overflow-y: auto; /* 項目が増えたらサイドバー内でスクロール */
        }
        .sidebar-section { padding: 0 20px; margin-bottom: 25px; }
        .sidebar-title { font-size: 13px; font-weight: bold; color: #666; margin-bottom: 10px; display: block; }
        .sidebar-select { width: 100%; padding: 8px; border: 1px solid #e9d0d0; border-radius: 4px; }
        .sidebar-nav { list-style: none; padding: 0; }
        .sidebar-nav a { text-decoration: none; color: #333; font-size: 14px; padding: 10px 20px; display: block; }
        .sidebar-nav a.active { background-color: #ffcfcf; font-weight: bold; }

        /* 右側の科目リストだけをスクロール */
        .subject-list { 
            flex: 1; 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); 
            gap: 20px; 
            padding: 5px; 
            align-content: flex-start;
            overflow-y: auto; /* ここでスクロールを発生させる */
            height: 100%;
        }

        .subject-card { background: white; border-radius: 12px; border: 1px solid #f0e2e2; display: flex; flex-direction: column; aspect-ratio: 1 / 1.1; cursor: pointer; transition: 0.2s; }
        .subject-card:hover { border-color: #ff9999; transform: translateY(-2px); }
        .card-header { background-color: #fff5f5; padding: 10px 15px; font-weight: bold; font-size: 13px; border-radius: 12px 12px 0 0; }
        .card-body { flex: 1; display: flex; justify-content: center; align-items: center; padding: 15px; text-align: center; }
        .card-title { font-size: 18px; font-weight: 800; color: #333; }
        .card-footer { padding: 10px; text-align: center; font-size: 11px; color: #b89494; border-top: 1px solid #f9f0f0; }

        /* モーダル */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 420px; text-align: center; }
        .info-box { background: #fdf2f2; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #fee2e2; }
        .btn-group { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; }
        .btn-delete-all { background-color: #e74c3c; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-delete-single { background-color: #f39c12; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-cancel { background-color: #eee; color: #666; border: none; padding: 10px; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
    <header class="header"><h1>授業科目一覧 (削除)</h1></header>

    <div class="container">
        <nav class="sidebar">
            <form action="sakuzyo.php" method="GET">
                <div class="sidebar-section">
                    <label class="sidebar-title">実施学年検索</label>
                    <select class="sidebar-select" name="search_grade" onchange="this.form.submit()">
                        <option value="all" <?= $search_grade === 'all' ? 'selected' : '' ?>>すべて</option>
                        <option value="1" <?= $search_grade === '1' ? 'selected' : '' ?>>1年生</option>
                        <option value="2" <?= $search_grade === '2' ? 'selected' : '' ?>>2年生</option>
                    </select>
                </div>
                <div class="sidebar-section">
                    <label class="sidebar-title">実施コース検索</label>
                    <select class="sidebar-select" name="search_course" onchange="this.form.submit()">
                        <option value="all" <?= $search_course === 'all' ? 'selected' : '' ?>>すべて</option>
                        <?php foreach ($courseInfo as $key => $info): ?>
                            <option value="<?= $key ?>" <?= $search_course === $key ? 'selected' : '' ?>><?= $info['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            <div class="sidebar-section">
                <ul class="sidebar-nav">
                    <li><a href="tuika.php">科目の編集</a></li>
                    <li><a href="sakuzyo.php" class="active">科目の削除</a></li>
                </ul>
            </div>
        </nav>

        <section class="subject-list">
            <?php foreach ($subjects as $row): ?>
                <div class="subject-card" onclick='openDeleteModal(<?= json_encode($row) ?>)'>
                    <div class="card-header"><?= $row['grade'] ?>年生</div>
                    <div class="card-body"><h3 class="card-title"><?= htmlspecialchars($row['title']) ?></h3></div>
                    <div class="card-footer"><?= implode(' / ', $row['courses']) ?></div>
                </div>
            <?php endforeach; ?>
        </section>
    </div>

    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <h2 style="font-size: 18px; margin-bottom: 5px;">科目の削除確認</h2>
            <div class="info-box">
                <p id="m-title" style="font-weight: bold; font-size: 1.2em; margin: 5px 0;"></p>
                <p id="m-grade" style="font-size: 0.9em; color: #666;"></p>
            </div>

            <form id="deleteForm" method="POST" action="sakuzyo.php?<?= http_build_query($_GET) ?>">
                <input type="hidden" name="subject_name" id="f-title">
                <input type="hidden" name="grade" id="f-grade">
                <input type="hidden" name="action" id="f-action" value="delete_single">

                <div id="single-delete-area">
                    <p style="font-size: 12px; color: #666;">削除するコースを選択してください：</p>
                    <select name="course_key" id="f-course" class="sidebar-select" style="margin-bottom: 10px;"></select>
                    <button type="submit" class="btn-delete-single" onclick="setAction('delete_single')">選択したコースから削除</button>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn-delete-all" onclick="setAction('delete_all')">すべてのコースから完全に削除</button>
                    <button type="button" class="btn-cancel" onclick="closeModal()">キャンセル</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDeleteModal(data) {
            document.getElementById('m-title').innerText = data.title;
            document.getElementById('m-grade').innerText = data.grade + "年生";
            document.getElementById('f-title').value = data.title;
            document.getElementById('f-grade').value = data.grade;

            const sel = document.getElementById('f-course');
            sel.innerHTML = "";
            data.course_keys.forEach((key, i) => {
                let opt = document.createElement('option');
                opt.value = key;
                opt.text = data.courses[i];
                sel.appendChild(opt);
            });

            document.getElementById('deleteModal').style.display = 'flex';
        }

        function setAction(action) {
            document.getElementById('f-action').value = action;
            const confirmMsg = action === 'delete_all' ? '本当にすべてのテーブルからこの科目を消去しますか？' : '選択したコースから削除しますか？';
            if (!confirm(confirmMsg)) {
                event.preventDefault();
            }
        }

        function closeModal() { document.getElementById('deleteModal').style.display = 'none'; }
        window.onclick = (e) => { if (e.target.id === 'deleteModal') closeModal(); }
    </script>
</body>
</html>