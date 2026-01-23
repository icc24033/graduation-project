document.addEventListener('DOMContentLoaded', function() {
    let lessonData = {}; 
    let dbTimetable = [];
    let savedClassDetails = []; 
    let currentSelectedSubject = localStorage.getItem('lastSelectedSubject') || ""; 
    let currentSelectedGrade = localStorage.getItem('lastSelectedGrade') || "";
    let currentSelectedCourse = localStorage.getItem('lastSelectedCourse') || "";
    let selectedDateKey = null;

    let isDeleteMode = false;

    // --- 要素の取得 ---
    const calendarGrid = document.getElementById('calendarGrid'); 
    const lessonModal = document.getElementById('lessonModal');
    const detailTextarea = document.querySelector('.lesson-details-textarea'); // 授業詳細
    const belongingsTextarea = document.getElementById('detailsTextarea');    // 課題・持ち物
    const itemInput = document.querySelector('.add-item-input'); 
    const itemTagsContainer = document.querySelector('.item-tags');
    const deleteIcon = document.querySelector('.delete-icon'); // ゴミ箱アイコン（テンプレート用）
    const addBtn = document.querySelector('.add-button'); 

    // HTMLのID: deleteLessonBtn に合わせて修正
    const deleteLessonBtn = document.getElementById('deleteLessonBtn');

    const sidebarListContainer = document.getElementById('sidebarLessonList'); 

    const displayGrade = document.getElementById('displayGrade');
    const displayCourse = document.getElementById('displayCourse');

    let currentYear = 2026;
    let currentMonth = 1;

    async function init() {
        loadTemplates(); 
        await refreshAllData(); 
        setupDeleteIcon();
    }

    async function refreshAllData() {
        try {
            const response = await fetch('class_detail_edit_control.php');
            const data = await response.json();
            if (data.error) return console.error("DBエラー:", data.error);
            
            dbTimetable = data.timetable || [];
            savedClassDetails = data.saved_details || []; 
            
            setupSubjectDropdown();
        } catch (e) {
            console.error("データ取得エラー:", e);
        }
    }

    // --- 持ち物タグテンプレート機能 ---
    function loadTemplates() {
        const defaultTemplates = ["ノートパソコン", "筆記用具", "教科書1", "プリント"];
        const savedTemplates = JSON.parse(localStorage.getItem('belongingsTemplates') || JSON.stringify(defaultTemplates));
        itemTagsContainer.innerHTML = ''; 
        savedTemplates.forEach(name => createTagElement(name));
    }

    function createTagElement(name) {
        const div = document.createElement('div');
        div.className = 'item-tag-container';
        div.innerHTML = `<span class="item-tag">${name}</span>`;
        
        div.onclick = (e) => {
            if (isDeleteMode) {
                div.remove();
                saveCurrentTemplates();
            } else {
                toggleBelongingItem(name);
            }
        };
        itemTagsContainer.appendChild(div);
    }

    function toggleBelongingItem(name) {
        let currentText = belongingsTextarea.value.trim();
        let items = currentText ? currentText.split(/、/) : []; // 日本語読点区切り
        items = items.filter(i => i !== "");

        const index = items.indexOf(name);
        if (index > -1) {
            items.splice(index, 1);
        } else {
            items.push(name);
        }
        belongingsTextarea.value = items.join('、');
    }

    function saveCurrentTemplates() {
        const tags = Array.from(itemTagsContainer.querySelectorAll('.item-tag')).map(el => el.textContent.trim());
        localStorage.setItem('belongingsTemplates', JSON.stringify(tags));
    }

    function setupDeleteIcon() {
        if (!deleteIcon) return;
        deleteIcon.onclick = () => {
            isDeleteMode = !isDeleteMode;
            // ゴミ箱アイコンの見た目を切り替え
            deleteIcon.style.filter = isDeleteMode ? "invert(27%) sepia(91%) saturate(2352%) hue-rotate(339deg) brightness(105%) contrast(106%)" : "";
        };
    }

    // --- カレンダー & 表示ロジック ---
    function setupSubjectDropdown() {
        const menu = document.getElementById('subjectDropdownMenu');
        const toggleValue = document.getElementById('currentSubjectDisplay');
        if (!menu) return;
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

        let targetEntry = null;
        uniqueEntries.forEach((item, index) => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            const sName = item.subject_name.trim();
            a.textContent = sName; 
            a.href = "#";
            a.onclick = (e) => {
                e.preventDefault();
                applySelection(item);
                menu.classList.remove('is-open');
            };
            li.appendChild(a);
            menu.appendChild(li);

            if (sName === currentSelectedSubject && item.course_name === currentSelectedCourse) {
                targetEntry = item;
            }
            if (index === 0 && !targetEntry) targetEntry = item;
        });

        if (targetEntry) applySelection(targetEntry);

        function applySelection(item) {
            currentSelectedSubject = item.subject_name.trim();
            currentSelectedGrade = String(item.grade);
            currentSelectedCourse = item.course_name;

            localStorage.setItem('lastSelectedSubject', currentSelectedSubject);
            localStorage.setItem('lastSelectedGrade', currentSelectedGrade);
            localStorage.setItem('lastSelectedCourse', currentSelectedCourse);

            if(toggleValue) toggleValue.textContent = currentSelectedSubject;
            if(displayGrade) displayGrade.textContent = `${currentSelectedGrade}年`;
            if(displayCourse) displayCourse.textContent = currentSelectedCourse;
            updateCalendar();
        }
    }

    function updateCalendar() {
        lessonData = {};
        const sqlDayToJsDay = { 1: 1, 2: 2, 3: 3, 4: 4, 5: 5, 6: 6, 7: 0 };
        const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();

        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const jsDay = new Date(currentYear, currentMonth - 1, d).getDay();

            dbTimetable.forEach(item => {
                if (item.subject_name.trim() === currentSelectedSubject && 
                    item.course_name === currentSelectedCourse &&
                    sqlDayToJsDay[item.day_of_week] === jsDay) {
                    
                    const key = `${currentYear}-${currentMonth}-${d}`;
                    const saved = savedClassDetails.find(s => s.lesson_date === dateStr && s.period == item.period);
                    
                    if (!lessonData[key]) lessonData[key] = [];
                    lessonData[key].push({
                        slot: `${item.period}限`,
                        period: item.period,
                        status: saved ? saved.status : "not-created",
                        statusText: saved ? (saved.status === 'in-progress' ? "作成済み" : "作成中") : "未作成",
                        content: saved ? saved.content : "",
                        belongings: saved ? saved.object_name : ""
                    });
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
                    lessonData[key].forEach((lesson, index) => {
                        cell.innerHTML += `
                            <div class="lesson-entry" data-index="${index}">
                                <span class="lesson-slot">${lesson.slot}</span>
                                <span class="status-button ${lesson.status}">${lesson.statusText}</span>
                            </div>
                        `;
                    });
                    cell.onclick = (e) => {
                        const entry = e.target.closest('.lesson-entry');
                        const idx = entry ? entry.dataset.index : 0;
                        openModal(key, idx);
                    };
                }
            } else {
                cell.className = 'date-cell is-out-of-month';
            }
            calendarGrid.appendChild(cell);
        }
        document.getElementById('currentMonthDisplay').textContent = `${currentMonth}月`;
    }

    function refreshSidebar() {
        if (!sidebarListContainer) return;
        sidebarListContainer.innerHTML = '';

        const sortedKeys = Object.keys(lessonData).sort((a,b) => {
            const dateA = a.split('-').map(Number);
            const dateB = b.split('-').map(Number);
            return new Date(dateA[0], dateA[1]-1, dateA[2]) - new Date(dateB[0], dateB[1]-1, dateB[2]);
        });

        sortedKeys.forEach(key => {
            lessonData[key].forEach((lesson, lessonIdx) => {
                const [y, m, d] = key.split('-');
                const wrapper = document.createElement('div');
                wrapper.className = 'lesson-status-wrapper';
                wrapper.style.display = 'flex';
                wrapper.innerHTML = `
                    <div class="lesson-date-item">${m}月${d}日 ${lesson.slot}</div>
                    <button class="status-button ${lesson.status}">${lesson.statusText}</button>
                `;
                wrapper.onclick = () => openModal(key, lessonIdx);
                sidebarListContainer.appendChild(wrapper);
            });
        });
    }

    let currentLessonIdx = 0; 
    function openModal(key, index = 0) {
        selectedDateKey = key;
        currentLessonIdx = index;
        isDeleteMode = false; // テンプレート削除モード解除

        const lesson = lessonData[key][index];
        detailTextarea.value = lesson.content || "";
        belongingsTextarea.value = lesson.belongings || "";
        const [y, m, d] = key.split('-');
        document.getElementById('modalDateDisplay').textContent = `${m}月${d}日 (${lesson.slot})`;
        lessonModal.style.display = 'flex';
    }

    const completeBtn = document.getElementById('completeBtn');
    const tempSaveBtn = document.getElementById('tempSaveBtn');
    if (completeBtn) completeBtn.onclick = () => saveToStorage("in-progress", "作成済み", true);
    if (tempSaveBtn) tempSaveBtn.onclick = () => saveToStorage("creating", "作成中", false);

    // --- 【削除処理】 ---
    if (deleteLessonBtn) {
        deleteLessonBtn.onclick = () => deleteFromStorage();
    }

    async function saveToStorage(status, statusText, isComplete) {
        const content = detailTextarea.value.trim();
        const belongings = belongingsTextarea.value.trim();
        const lesson = lessonData[selectedDateKey][currentLessonIdx];

        if (isComplete && (!content || !belongings)) return alert("詳細と持ち物を入力してください。");

        const [y, m, d] = selectedDateKey.split('-');
        const formattedDate = `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;

        try {
            const response = await fetch('class_detail_edit_control.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    date: formattedDate,
                    slot: lesson.slot,
                    content: content,
                    belongings: belongings,
                    status: status
                })
            });
            const result = await response.json();
            if (result.success) {
                alert("保存しました。");
                await refreshAllData(); 
                lessonModal.style.display = 'none';
            }
        } catch (e) { alert("通信エラーが発生しました。"); }
    }

    // ★削除実行関数
    async function deleteFromStorage() {
        if (!confirm("この授業の詳細データを完全に削除しますか？")) return;

        const lesson = lessonData[selectedDateKey][currentLessonIdx];
        const [y, m, d] = selectedDateKey.split('-');
        const formattedDate = `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;

        try {
            const response = await fetch('class_detail_edit_control.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mode: 'delete',
                    date: formattedDate,
                    slot: lesson.slot
                })
            });
            const result = await response.json();
            if (result.success) {
                alert("削除しました。");
                await refreshAllData(); 
                lessonModal.style.display = 'none';
            }
        } catch (e) { alert("通信エラーが発生しました。"); }
    }

    lessonModal.addEventListener('mousedown', (e) => { if (e.target === lessonModal) lessonModal.style.display = 'none'; });

    addBtn.onclick = () => {
        const val = itemInput.value.trim();
        if (!val) return;
        createTagElement(val);
        saveCurrentTemplates();
        itemInput.value = '';
    };

    // ドロップダウン制御
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