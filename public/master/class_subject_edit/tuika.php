<?php
// tuika.php - 科目追加画面
SecurityHelper::requireLogin();
SecurityHelper::applySecureHeaders();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>授業科目一覧</title>
    <link rel="stylesheet" type="text/css" href="../css/add_style.css">
    <link rel="stylesheet" type="text/css" href="../css/reset.css">
    <link rel="stylesheet" type="text/css" href="/2025/sotsuken\graduation-project\public\master\css\common.css">
    <link rel="stylesheet" type="text/css" href="/2025/sotsuken\graduation-project\public\master\css\teacher_home\user_menu.css">
</head>
<body>
    <header class="header">
        <h1>授業科目一覧</h1>
        <div class="user-avatar" id="userAvatar" style="position: absolute; right: 20px; top: 5px;">
            <img src="<?= SecurityHelper::escapeHtml((string)$data['user_picture']) ?>" alt="ユーザーアイコン" class="avatar-image">   
        </div>
            <div class="user-menu-popup" id="userMenuPopup">
                <a href="../../../logout/logout.php" class="logout-button">
                    <span class="icon-key"></span>
                        アプリからログアウト
                </a>
                <a href="../../../help/help_control.php?back_page=4" class="help-button" target="_blank" rel="noopener noreferrer">
                    <span class="icon-lightbulb"></span> ヘルプ
                </a>
            </div>
        <a href="../../../login/redirect.php" 
            style="position: absolute; left: 20px; top: 5px;" 
            onclick="return confirm('ホーム画面に遷移しますか？ ※編集中の内容が消える恐れがあります');">
                <img src="<?= SecurityHelper::escapeHtml((string)$smartcampus_picture) ?>" alt="Webアプリアイコン" width="200" height="60">
        </a>
    </header>
    <div class="container">
        <nav class="sidebar">
            <div class="sidebar-section">
                <button class="sidebar-add-btn" onclick="openAddModal()">＋ 新規科目追加</button>
            </div>
            <hr style="border: 0; border-top: 1px solid #e0e6ed; margin: 0 20px 20px 20px;">
            <form action="addition_control.php" method="GET" id="search-form">
                <div class="sidebar-section">
                    <label class="sidebar-title">実施学年検索</label>
                    <select class="sidebar-select" name="search_grade" id="search_grade" onchange="this.form.submit()">
                        <option value="all" <?= $search_grade === 'all' ? 'selected' : '' ?>>すべて</option>
                        <option value="1年生" <?= $search_grade === '1年生' ? 'selected' : '' ?>>1年生</option>
                        <option value="2年生" <?= $search_grade === '2年生' ? 'selected' : '' ?>>2年生</option>
                    </select>
                </div>
                <div class="sidebar-section">
                    <label class="sidebar-title">実施コース検索</label>
                    <select class="sidebar-select" name="search_course" id="search_course" onchange="this.form.submit()">
                        <option value="all" <?= $search_course === 'all' ? 'selected' : '' ?>>すべて</option>
                        <?php foreach ($courseInfo as $key => $info): 
                            if ($grade_val && $info['grade'] !== $grade_val) continue;
                        ?>
                            <option value="<?= SecurityHelper::escapeHtml((string)$key) ?>" <?= $search_course === $key ? 'selected' : '' ?>>
                                <?= SecurityHelper::escapeHtml((string)$info['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            <div class="sidebar-section">
                <label class="sidebar-title">メニュー</label>
                <ul class="sidebar-nav">
                    <li><a href="addition_control.php" class="active">科目の編集</a></li>
                    <li><a href="delete_control.php">科目の削除</a></li>
                </ul>
            </div>
        </nav>

        <section class="subject-list">
            <?php foreach ($subjects as $row): ?>
                <div class="subject-card" onclick='openDetail(<?= json_encode($row) ?>)'>
                    <div class="card-header">
                        <?= $row['is_all'] ? '全体' : SecurityHelper::escapeHtml((string)$row['grade']) . '年生' ?>
                    </div>
                    <div class="card-body"><h3 class="card-title"><?= SecurityHelper::escapeHtml((string)$row['title']) ?></h3></div>
                    <div class="card-footer"><?= SecurityHelper::escapeHtml(implode(' / ', $row['courses'])) ?></div>
                </div>
            <?php endforeach; ?>
        </section>
    </div>

    <div class="modal-overlay" id="detailModal">
        <div class="modal-content">
            <div id="m-title" class="modal-title"></div>
            
            <div class="info-box">
                <span class="info-header">現在の担当講師</span>
                <div class="info-value" id="m-teacher"></div>
                <div class="circle-btn-group">
                    <button class="circle-btn" title="講師を追加" onclick="toggleArea('teacher-add')">＋</button>
                    <button class="circle-btn" title="講師を解除" onclick="toggleArea('teacher-remove')">－</button>
                </div>
                <div id="area-teacher-add" class="selector-area">
                    <select id="sel-teacher" class="sidebar-select">
                        <?php foreach($teacherList as $t): ?>
                            <option value="<?= SecurityHelper::escapeHtml((string)$t['teacher_name']) ?>">
                                <?= SecurityHelper::escapeHtml((string)$t['teacher_name']) ?> 先生
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="update-btn" onclick="saveField('teacher', 'add')">講師を追加する</button>
                </div>
                <div id="area-teacher-remove" class="selector-area">
                    <select id="sel-teacher-remove" class="sidebar-select"></select>
                    <button class="update-btn btn-danger" onclick="removeSingleTeacher()">選択した講師を解除</button>
                    <hr style="margin: 10px 0; border: 0; border-top: 1px solid #eee;">
                    <button class="update-btn" style="background-color: #d9534f; color: white;" onclick="clearField('teacher')">
                        全員解除（未設定にする）
                    </button>
                </div>
            </div>

            <div class="info-box">
                <span class="info-header">現在の実施教室</span>
                <div class="info-value" id="m-room"></div>
                <div class="circle-btn-group">
                    <button class="circle-btn" onclick="toggleArea('room-add')">＋</button>
                    <button class="circle-btn" onclick="toggleArea('room-remove')">－</button>
                </div>
                
                <div id="area-room-add" class="selector-area">
                    <select id="sel-room" class="sidebar-select">
                        <option value="" disabled selected>教室を選択</option>
                        <?php foreach($roomList as $r): ?>
                            <option value="<?= SecurityHelper::escapeHtml((string)$r['room_id']) ?>">
                                <?= SecurityHelper::escapeHtml((string)$r['room_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="update-btn" onclick="saveField('room', 'overwrite')">教室を確定する</button>
                </div>
                
                <div id="area-room-remove" class="selector-area">
                    <p style="font-size: 12px; color: #666; margin-bottom: 10px;">現在の教室設定を解除しますか？</p>
                    <button class="update-btn btn-danger" onclick="clearField('room')">教室を解除して未設定にする</button>
                </div>
            </div>

            <div class="info-box">
                <span class="info-header">現在の実施コース</span>
                <div class="info-value" id="m-courses"></div>
                <div class="circle-btn-group">
                    <button class="circle-btn" onclick="toggleArea('course-add')">＋</button>
                    <button class="circle-btn" onclick="toggleArea('course-remove')">－</button>
                </div>
                <div id="area-course-add" class="selector-area">
                    <select id="sel-course-add" class="sidebar-select"></select>
                    <button class="update-btn" onclick="updateCourse('add_course')">コースを追加</button>
                </div>
                <div id="area-course-remove" class="selector-area">
                    <select id="sel-course-remove" class="sidebar-select"></select>
                    <button class="update-btn btn-danger" onclick="updateCourse('remove_course')">このコースから削除</button>
                </div>
            </div>
            <div style="text-align:center;"><button class="btn-confirm" onclick="location.reload()">完了して閉じる</button></div>
        </div>
    </div>

    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <h2 class="modal-title">新規科目追加</h2>
            <form action="..\..\..\..\app\master\class_subject_edit_backend\backend_subject_add.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCsrfToken() ?>">    
            <input type="hidden" name="action" value="insert_new">
                <div class="info-box">
                    <label class="sidebar-title">学年</label>
                    <select name="grade" id="add_grade" class="sidebar-select" onchange="updateAddModalCourses()">
                        <option value="1">1年生</option>
                        <option value="2">2年生</option>
                        <option value="1_all">1年全体</option>
                        <option value="2_all">2年全体</option>
                        <option value="all">全体</option>
                    </select>
                </div>
                <div class="info-box" id="course_select_box">
                    <label class="sidebar-title">実施コース</label>
                    <select name="course" id="add_course" class="sidebar-select" required></select>
                </div>
                <div class="info-box">
                    <label class="sidebar-title">科目名</label>
                    <input type="text" name="title" required style="width:100%; padding:8px; box-sizing:border-box; border:1px solid #d0dbe9; border-radius:4px;">
                </div>
                <div style="display:flex; justify-content:center; gap:10px; margin-top:20px;">
                    <button type="button" class="sidebar-add-btn" style="background:#ccc; margin:0;" onclick="document.getElementById('addModal').style.display='none'">戻る</button>
                    <button type="submit" class="btn-confirm">保存する</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        const CSRF_TOKEN = "<?= SecurityHelper::generateCsrfToken() ?>";
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
    <script src="../js/subject_edit.js"></script>
</body>
</html>