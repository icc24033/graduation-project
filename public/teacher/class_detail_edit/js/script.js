document.addEventListener('DOMContentLoaded', function() {
    let lessonData = {}; 
    let dbTimetable = [];
    let currentSelectedSubject = ""; 
    let currentSelectedGrade = "";
    let currentSelectedCourse = "";
    let selectedDateKey = null;

    // 削除モードの状態管理
    let isDeleteMode = false;

    const calendarGrid = document.getElementById('calendarGrid'); 
    const lessonModal = document.getElementById('lessonModal');
    const detailTextarea = document.querySelector('.lesson-details-textarea');
    const belongingsTextarea = document.getElementById('detailsTextarea'); 
    const itemInput = document.querySelector('.add-item-input'); 
    const itemTagsContainer = document.querySelector('.item-tags');
    // セレクタをHTMLの実装に合わせて調整
    const deleteIcon = document.getElementById('deleteIcon') || document.querySelector('.delete-icon'); 
    const addBtn = document.querySelector('.add-button'); // 追加ボタン

    const displayGrade = document.getElementById('displayGrade');
    const displayCourse = document.getElementById('displayCourse');
    const displayGradeSidebar = document.getElementById('displayGradeSidebar');
    const displayCourseSidebar = document.getElementById('displayCourseSidebar');

    let currentYear = 2026;
    let currentMonth = 1;

    // --- 1. 初期化 ---
    async function init() {
        loadTemplates(); // 起動時にlocalStorageから読み込み

        try {
            const response = await fetch('class_detail_edit_control.php');
            const data = await response.json();
            
            if (data.error) {
                console.error("DBエラー:", data.error);
                return;
            }
            
            dbTimetable = data;
            setupSubjectDropdown();
        } catch (e) {
            console.error("データ取得エラー:", e);
        }
    }

    // localStorageからテンプレートを読み込む
    function loadTemplates() {
        const defaultTemplates = ["ノートパソコン", "筆記用具", "教科書1", "教科書2"];
        const savedTemplates = JSON.parse(localStorage.getItem('belongingsTemplates') || JSON.stringify(defaultTemplates));
        
        itemTagsContainer.innerHTML = ''; 
        savedTemplates.forEach(name => {
            createTagElement(name);
        });
    }

    // 現在のテンプレート（タグ）の状態をlocalStorageへ保存
    function saveTemplatesToStorage() {
        const tags = Array.from(itemTagsContainer.querySelectorAll('.item-tag'))
                          .map(el => el.textContent.trim());
        localStorage.setItem('belongingsTemplates', JSON.stringify(tags));
    }

    // タグを生成する共通関数
    function createTagElement(name) {
        const div = document.createElement('div');
        div.className = 'item-tag-container';
        div.innerHTML = `<span class="item-tag">${name}</span>`;
        itemTagsContainer.appendChild(div);
    }

    // --- 2. カレンダー・ドロップダウン制御 ---
    function setupSubjectDropdown() {
        const toggle = document.getElementById('subjectDropdownToggle');
        const menu = document.getElementById('subjectDropdownMenu');
        menu.innerHTML = '';
        const seen = new Set();
        const uniqueEntries = [];

        dbTimetable.forEach(item => {
            const key = `${item.subject_name.trim()}-${item.grade}-${item.course_name}`;
            if (!seen.has(key)) {
                seen.add(key);
                uniqueEntries.push(item);
            }
        });

        uniqueEntries.forEach((item, index) => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.textContent = item.subject_name.trim(); 
            a.href = "#";
            a.onclick = (e) => {
                e.preventDefault();
                applySelection(item);
                menu.classList.remove('is-open');
            };
            li.appendChild(a);
            menu.appendChild(li);
            if(index === 0) applySelection(item);
        });

        function applySelection(item) {
            currentSelectedSubject = item.subject_name.trim();
            currentSelectedGrade = String(item.grade);
            currentSelectedCourse = item.course_name;
            if(toggle.querySelector('.current-value')) 
                toggle.querySelector('.current-value').textContent = currentSelectedSubject;
            if(displayGrade) displayGrade.textContent = ""; 
            if(displayCourse) displayCourse.textContent = currentSelectedCourse;
            if(displayGradeSidebar) displayGradeSidebar.textContent = ""; 
            if(displayCourseSidebar) displayCourseSidebar.textContent = currentSelectedCourse;
            updateCalendar();
        }
    }

    function updateCalendar() {
        lessonData = {};
        const sqlDayToJsDay = { 1: 1, 2: 2, 3: 3, 4: 4, 5: 5, 6: 6, 7: 0 };
        const storedData = JSON.parse(localStorage.getItem('lessonTextData') || '{}');
        const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();

        for (let d = 1; d <= daysInMonth; d++) {
            const dateObj = new Date(currentYear, currentMonth - 1, d);
            const jsDay = dateObj.getDay();
            dbTimetable.forEach(item => {
                if (item.subject_name.trim() === currentSelectedSubject && 
                    String(item.grade) === currentSelectedGrade && 
                    item.course_name === currentSelectedCourse &&
                    sqlDayToJsDay[item.day_of_week] === jsDay) {
                    const key = `${currentYear}-${currentMonth}-${d}`;
                    const saved = storedData[key];
                    lessonData[key] = {
                        slot: `${item.period}限`,
                        status: saved ? saved.status : "not-created",
                        statusText: saved ? saved.statusText : "未作成"
                    };
                }
            });
        }
        renderCalendarUI();
        refreshSidebar();
    }

    function renderCalendarUI() {
        const cells = calendarGrid.querySelectorAll('.date-cell, .is-out-of-month');
        cells.forEach(c => c.remove());
        const firstDay = new Date(currentYear, currentMonth - 1, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();

        for (let i = 0; i < 35; i++) {
            const cell = document.createElement('div');
            const dayNum = i - firstDay + 1;
            if (dayNum > 0 && dayNum <= daysInMonth) {
                cell.className = 'date-cell';
                cell.innerHTML = `<span class="date-num">${dayNum}</span>`;
                const key = `${currentYear}-${currentMonth}-${dayNum}`;
                if (lessonData[key]) {
                    cell.classList.add('has-data');
                    cell.innerHTML += `
                        <span class="lesson-slot">${lessonData[key].slot}</span>
                        <span class="status-button ${lessonData[key].status}">${lessonData[key].statusText}</span>
                    `;
                    cell.onclick = () => openModal(key);
                }
            } else {
                cell.className = 'date-cell is-out-of-month';
            }
            calendarGrid.appendChild(cell);
        }
        document.querySelector('.month').textContent = `${currentMonth}月`;
    }

    function refreshSidebar() {
        const sidebarItems = document.querySelectorAll('.sidebar .lesson-status-wrapper');
        sidebarItems.forEach(el => el.style.display = 'none');
        const sortedKeys = Object.keys(lessonData).sort((a,b) => {
            const dateA = a.split('-').map(Number);
            const dateB = b.split('-').map(Number);
            return new Date(dateA[0], dateA[1]-1, dateA[2]) - new Date(dateB[0], dateB[1]-1, dateB[2]);
        });
        sortedKeys.forEach((key, i) => {
            if (sidebarItems[i]) {
                const el = sidebarItems[i];
                el.style.display = 'flex';
                const [y, m, d] = key.split('-');
                el.querySelector('.lesson-date-item').textContent = `${m}月${d}日 ${lessonData[key].slot}`;
                const btn = el.querySelector('.status-button');
                btn.className = `status-button ${lessonData[key].status}`;
                btn.textContent = lessonData[key].statusText;
                el.onclick = () => openModal(key);
            }
        });
    }

    // --- 3. モーダル基本操作 ---
    function openModal(key) {
        selectedDateKey = key;
        
        isDeleteMode = false;
        if(deleteIcon) {
            deleteIcon.style.backgroundColor = "";
            deleteIcon.style.color = "";
            deleteIcon.style.filter = ""; 
        }

        loadTemplates(); 

        const stored = JSON.parse(localStorage.getItem('lessonTextData') || '{}');
        const data = stored[key] || { content: "", belongings: "" };
        detailTextarea.value = data.content;
        belongingsTextarea.value = data.belongings || "";
        
        const [y, m, d] = key.split('-');
        document.querySelector('.modal-date').textContent = `${m}月${d}日`;
        lessonModal.style.display = 'flex';
    }

    document.querySelector('.complete-button').onclick = () => saveToStorage("in-progress", "作成済み", true);
    document.querySelector('.temp-save-button').onclick = () => saveToStorage("creating", "作成中", false);

    function saveToStorage(status, statusText, isComplete) {
        if (isComplete && (!detailTextarea.value.trim() || !belongingsTextarea.value.trim())) {
            return alert("授業詳細と持ち物の両方を入力してください。");
        }
        const stored = JSON.parse(localStorage.getItem('lessonTextData') || '{}');
        stored[selectedDateKey] = { 
            content: detailTextarea.value, 
            belongings: belongingsTextarea.value,
            status: status,
            statusText: statusText
        };
        localStorage.setItem('lessonTextData', JSON.stringify(stored));
        updateCalendar();
        lessonModal.style.display = 'none';
    }

    document.querySelector('.delete-button').onclick = () => {
        if (!confirm("授業詳細を削除してもよろしいですか？")) return;
        const stored = JSON.parse(localStorage.getItem('lessonTextData') || '{}');
        delete stored[selectedDateKey];
        localStorage.setItem('lessonTextData', JSON.stringify(stored));
        updateCalendar();
        lessonModal.style.display = 'none';
    };

    lessonModal.addEventListener('mousedown', (e) => {
        if (e.target === lessonModal) lessonModal.style.display = 'none';
    });

    // --- 4. 持ち物テンプレート管理 ---

    // ゴミ箱アイコンクリック（修正箇所）
    if (deleteIcon) {
        deleteIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            isDeleteMode = !isDeleteMode;
            
            if (isDeleteMode) {
                // 削除モード：赤色背景にする（画像の場合はfilterを使用）
                this.style.backgroundColor = "#ff4d4d";
                this.style.color = "white";
                this.style.borderRadius = "4px";
                if (this.tagName === 'IMG') this.style.filter = "brightness(0) saturate(100%) invert(30%) sepia(100%) saturate(5000%) hue-rotate(350deg)";
            } else {
                this.style.backgroundColor = "";
                this.style.color = "";
                this.style.filter = "";
            }
        });
    }

    itemTagsContainer.addEventListener('click', function(e) {
        const tagContainer = e.target.closest('.item-tag-container');
        if (!tagContainer) return;
        
        const itemName = tagContainer.querySelector('.item-tag').textContent.trim();

        if (isDeleteMode) {
            tagContainer.remove();
            saveTemplatesToStorage(); 
        } else {
            let currentText = belongingsTextarea.value.trim();
            if (currentText === "持ち物は入力されていません") currentText = "";
            
            let items = currentText === "" ? [] : currentText.split('、');
            const index = items.indexOf(itemName);
            
            if (index === -1) {
                items.push(itemName);
            } else {
                items.splice(index, 1);
            }
            belongingsTextarea.value = items.join('、');
        }
    });

    addBtn.onclick = () => {
        const val = itemInput.value.trim();
        if (!val) return;
        createTagElement(val);
        saveTemplatesToStorage(); 
        itemInput.value = '';
    };

    document.querySelectorAll('.dropdown-toggle').forEach(btn => {
        btn.onclick = (e) => {
            e.stopPropagation();
            btn.nextElementSibling.classList.toggle('is-open');
        };
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('is-open'));
    });

    init();
});