<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>授業科目一覧 - 削除</title>
    <link rel="stylesheet" href="../css/delete_style.css"> 
    <link rel="stylesheet" type="text/css" href="../css/reset.css">
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\common.css">
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\teacher_home\user_menu.css">
</head>
<body>
    <header class="header">
        <h1>授業科目一覧 (削除)</h1>
        <div class="user-avatar" id="userAvatar" style="position: absolute; right: 20px; top: 5px;">
            <img src="<?= SecurityHelper::escapeHtml((string)$data['user_picture']) ?>" alt="ユーザーアイコン" class="avatar-image">   
        </div>
            <div class="user-menu-popup" id="userMenuPopup">
                <a href="../logout/logout.php" class="logout-button">
                    <span class="icon-key"></span>
                        アプリからログアウト
                </a>
                <a href="" class="help-button">
                    <span class="icon-lightbulb"></span> ヘルプ
                </a>
            </div>
        <img src="<?= SecurityHelper::escapeHtml((string)$smartcampus_picture) ?>" alt="Webアプリアイコン" width="200" height="60" style="position: absolute; left: 20px; top: 5px;">
    </header>
    <div class="container">
        <nav class="sidebar">
            <form action="delete_control.php" method="GET">
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
                            <option value="<?= SecurityHelper::escapeHtml((string)$key) ?>" <?= $search_course === $key ? 'selected' : '' ?>>
                                <?= SecurityHelper::escapeHtml((string)$info['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            <div class="sidebar-section">
                <ul class="sidebar-nav">
                    <li><a href="addition_control.php">科目の編集</a></li>
                    <li><a href="delete_control.php" class="active">科目の削除</a></li>
                </ul>
            </div>
        </nav>

        <section class="subject-list">
            <?php foreach ($subjects as $row): ?>
                <div class="subject-card" onclick='openDeleteModal(<?= json_encode($row) ?>)'>
                    <div class="card-header"><?= SecurityHelper::escapeHtml((string)$row['grade']) ?>年生</div>
                    <div class="card-body"><h3 class="card-title"><?= SecurityHelper::escapeHtml((string)$row['title']) ?></h3></div>
                    <div class="card-footer"><?= SecurityHelper::escapeHtml(implode(' / ', $row['courses'])) ?></div>
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

            <form id="deleteForm" method="POST" action="..\..\..\..\app\master\class_subject_edit_backend\backend_subject_delete.php">
                <input type="hidden" name="subject_name" id="f-title">
                <input type="hidden" name="grade" id="f-grade">
                <input type="hidden" name="action" id="f-action" value="delete_single">
                
                <input type="hidden" name="query_string" value="<?= SecurityHelper::escapeHtml((string)($_SERVER['QUERY_STRING'] ?? '')) ?>">

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
        const allCourseInfo = <?= json_encode($courseInfo) ?>;
        let currentData = {};

        document.addEventListener('DOMContentLoaded', function() {
                const userAvatar = document.getElementById('userAvatar');
                const userMenuPopup = document.getElementById('userMenuPopup');

                userAvatar.addEventListener('click', function(event) {
                    userMenuPopup.classList.toggle('is-visible');
                    event.stopPropagation();
                });

                document.addEventListener('click', function(event) {
                    if (!userMenuPopup.contains(event.target) && !userAvatar.contains(event.target)) {
                        userMenuPopup.classList.remove('is-visible');
                    }
                });
            });
    </script>
    <script src="../js/subject_delete.js"></script>
</body>
</html>