document.addEventListener('DOMContentLoaded', function() {
    // --- 1. 変数・要素の定義 ---
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    const monthElement = document.querySelector('.month');
    const leftArrowButton = document.getElementById('prevBtn');
    const rightArrowButton = document.getElementById('nextBtn');
    const calendarGrid = document.getElementById('calendarGrid'); 
    const lessonModal = document.getElementById('lessonModal');
    const completeButton = document.querySelector('.complete-button');
    const tempSaveButton = document.querySelector('.temp-save-button');
    const deleteButton = document.querySelector('.delete-button');

    const deleteIcon = document.querySelector('.delete-icon');
    const addBtn = document.querySelector('.add-button');
    const itemInput = document.querySelector('.add-item-input');
    const itemTagsContainer = document.querySelector('.item-tags');
    
    // 入力エリア
    const lessonContentInput = document.getElementById('lessonContent');
    const belongingsTextarea = document.getElementById('belongingsTextarea');
    const charCountDisplay = document.querySelector('.char-count');

    let lessonData = {};
    let currentYear = 2026;
    let currentMonth = 1;
    let selectedDateKey = null;
    let isDeleteMode = false;
    let selectedSubjectId = null;

    // --- 2. データ読み込み関数 ---
    
    // 授業データとテンプレートを同時に読み込む
    async function init() {
        await Promise.all([loadData(), loadTemplates()]);
    }

    // 授業データの読み込み
    async function loadData() {
        try {
            const response = await fetch('class_detail_edit_control.php?action=fetch');
            const data = await response.json();
            if (data) {
                lessonData = data;
                renderCalendar(currentYear, currentMonth);
                refreshSidebar();
            }
        } catch (e) { console.error("データ読み込み失敗:", e); }
    }

    // テンプレートの読み込み
    async function loadTemplates() {
        try {
            const response = await fetch('class_detail_edit_control.php?action=fetch_templates');
            const templates = await response.json();
            itemTagsContainer.innerHTML = ''; 
            if (Array.isArray(templates)) {
                templates.forEach(name => createTagElement(name));
            }
        } catch (e) { console.error("テンプレート読み込み失敗:", e); }
    }

    function createTagElement(name) {
        const div = document.createElement('div');
        div.className = 'item-tag-container';
        div.innerHTML = `<span class="item-tag">${name}</span>`;
        itemTagsContainer.appendChild(div);
    }

    // --- 3. テンプレートのDB保存・削除 ---
    async function saveTemplateToDB(name) {
        const formData = new URLSearchParams();
        formData.append('action', 'save_template');
        formData.append('template_name', name);
        await fetch('class_detail_edit_control.php', { method: 'POST', body: formData });
    }

    async function deleteTemplateFromDB(name) {
        const formData = new URLSearchParams();
        formData.append('action', 'delete_template');
        formData.append('template_name', name);
        await fetch('class_detail_edit_control.php', { method: 'POST', body: formData });
    }

    // --- 4. 画面表示制御 ---
    
    // サイドバー更新
    function refreshSidebar() {
        const sidebarContainer = document.getElementById('sidebarLessonList');
        if (!sidebarContainer) return;
        sidebarContainer.innerHTML = ''; 
        const sortedDates = Object.keys(lessonData).sort((a, b) => new Date(a) - new Date(b));
        sortedDates.forEach(key => {
            const data = lessonData[key];
            const dateObj = new Date(key);
            const m = dateObj.getMonth() + 1;
            const d = dateObj.getDate();
            const dayOfWeek = dateObj.toLocaleDateString('ja-JP', { weekday: 'short' });
            const wrapper = document.createElement('div');
            wrapper.className = 'sidebar-lesson-item';
            wrapper.style.cursor = 'pointer';
            wrapper.innerHTML = `
                <div class="lesson-date-item" style="font-weight: bold;">${m}月${d}日(${dayOfWeek}) ${data.slot || '1限'}</div>
                <div class="status-button ${data.status}" style="font-size: 12px; display: inline-block; padding: 2px 5px;">
                    ${data.status === 'in-progress' ? '作成済み' : '作成中'}
                </div>`;
            wrapper.onclick = () => openModalWithDate(key);
            sidebarContainer.appendChild(wrapper);
        });
    }

    // モーダルを開く（データ復元処理）
    window.openModalWithDate = function(dateKey) {
        selectedDateKey = dateKey;
        
        // 保存済みデータがあれば取得、なければ空
        const data = lessonData[dateKey] || { content: "", belongings: "", status: "" };
        
        const [year, month, dayNum] = dateKey.split('-').map(Number);
        const dateObj = new Date(year, month - 1, dayNum);

        // 入力欄にセット
        lessonContentInput.value = data.content || "";
        belongingsTextarea.value = data.belongings || "";

        // 文字数カウント更新
        charCountDisplay.textContent = `${(lessonContentInput.value).length}/200文字`;

        const modalTitleDate = document.querySelector('.modal-date');
        if (modalTitleDate) {
            modalTitleDate.textContent = `${month}月${dayNum}日(${dateObj.toLocaleDateString('ja-JP', { weekday: 'short' })})`;
        }
        lessonModal.style.display = 'flex';
    };

    // カレンダー描画
    function renderCalendar(year, month) {
        const oldCells = calendarGrid.querySelectorAll('.date-cell');
        oldCells.forEach(cell => cell.remove());
        const firstDay = new Date(year, month - 1, 1).getDay();
        const daysInMonth = new Date(year, month, 0).getDate();
        
        for (let i = 0; i < 35; i++) {
            const cell = document.createElement('div');
            cell.className = 'date-cell';
            let dayNum = i - firstDay + 1;
            if (dayNum > 0 && dayNum <= daysInMonth) {
                const mm = String(month).padStart(2, '0');
                const dd = String(dayNum).padStart(2, '0');
                const dateKey = `${year}-${mm}-${dd}`;
                cell.innerHTML = `<span class="date-num">${dayNum}</span>`;
                if (lessonData[dateKey]) {
                    const d = lessonData[dateKey];
                    cell.classList.add('has-data');
                    cell.innerHTML += `
                        <span class="lesson-slot">${d.slot || '1限'}</span>
                        <span class="status-button ${d.status}">${d.status === 'in-progress' ? '作成済み' : '作成中'}</span>`;
                }
                cell.onclick = () => openModalWithDate(dateKey);
            } else { 
                cell.classList.add('is-out-of-month'); 
            }
            calendarGrid.appendChild(cell);
        }
        monthElement.textContent = `${month}月`;
    }

    // --- 5. イベントリスナー ---

    // テンプレート追加・削除切り替え
    deleteIcon.onclick = () => {
        isDeleteMode = !isDeleteMode;
        deleteIcon.classList.toggle('is-active', isDeleteMode);
        addBtn.textContent = isDeleteMode ? '削除' : '追加';
        addBtn.classList.toggle('is-delete-mode', isDeleteMode);
    };

    // テンプレート追加
    addBtn.onclick = async () => {
        const name = itemInput.value.trim();
        if (!isDeleteMode && name) {
            createTagElement(name);
            await saveTemplateToDB(name);
            itemInput.value = '';
        }
    };

    // テンプレートクリック（削除 or 持ち物エリアへの追加）
    itemTagsContainer.onclick = async (e) => {
        const tag = e.target.closest('.item-tag-container');
        if (!tag) return;
        const name = tag.querySelector('.item-tag').textContent;
        
        if (isDeleteMode) {
            tag.remove();
            await deleteTemplateFromDB(name);
        } else {
            let items = belongingsTextarea.value ? belongingsTextarea.value.split('、') : [];
            items = items.map(i => i.trim()).filter(i => i !== "");
            
            if (items.includes(name)) {
                items = items.filter(i => i !== name);
            } else {
                items.push(name);
            }
            belongingsTextarea.value = items.join('、');
        }
    };

    // 授業詳細の保存・削除通信
    async function saveTextDataToStorage(status, isDelete = false) {
        if (!selectedDateKey) return;
        const formData = new URLSearchParams();
        formData.append('date', selectedDateKey);
        
        if (isDelete) { 
            formData.append('action', 'delete'); 
        } else {
            formData.append('action', 'save');
            formData.append('content', lessonContentInput.value);
            formData.append('belongings', belongingsTextarea.value);
            formData.append('status', status);
            if (selectedSubjectId) formData.append('subject_id', selectedSubjectId);
        }

        try {
            const response = await fetch('class_detail_edit_control.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });
            const result = await response.json();
            if (result.status === "success") {
                await loadData(); // データを再取得して画面更新
                lessonModal.style.display = 'none';
            }
        } catch (e) { console.error("保存失敗:", e); }
    }

    // ボタンクリックイベント
    completeButton.onclick = () => saveTextDataToStorage("in-progress");
    tempSaveButton.onclick = () => saveTextDataToStorage("creating");
    deleteButton.onclick = () => confirm("この日のデータを削除しますか？") && saveTextDataToStorage(null, true);
    
    // カレンダー月移動
    leftArrowButton.onclick = () => { 
        currentMonth--; 
        if (currentMonth < 1) { currentMonth = 12; currentYear--; } 
        renderCalendar(currentYear, currentMonth); 
    };
    rightArrowButton.onclick = () => { 
        currentMonth++; 
        if (currentMonth > 12) { currentMonth = 1; currentYear++; } 
        renderCalendar(currentYear, currentMonth); 
    };

    // モーダルを閉じる
    lessonModal.addEventListener('click', (e) => { 
        if (e.target === lessonModal) lessonModal.style.display = 'none'; 
    });

    // リアルタイム文字数カウント
    lessonContentInput.addEventListener('input', () => {
        charCountDisplay.textContent = `${lessonContentInput.value.length}/200文字`;
    });

    // ドロップダウン制御
    dropdownToggles.forEach(toggle => {
        const menu = toggle.nextElementSibling;
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            // 他の開いているメニューを閉じる
            document.querySelectorAll('.dropdown-menu.is-open').forEach(m => {
                if (m !== menu) m.classList.remove('is-open');
            });
            menu.classList.toggle('is-open');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu.is-open').forEach(m => m.classList.remove('is-open'));
    });

    // 初期起動
    init();
});