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
    
    const lessonContentInput = document.getElementById('lessonContent');
    const belongingsTextarea = document.getElementById('belongingsTextarea');
    const charCountDisplay = document.querySelector('.char-count');

    let lessonData = {};
    let currentYear = 2026;
    let currentMonth = 1;
    let selectedDateKey = null;
    let isDeleteMode = false;
    let selectedSubjectId = null;

    async function init() {
        const firstSubject = document.querySelector('#subjectDropdownMenu a[data-subject-id]');
        if (firstSubject) {
            selectedSubjectId = firstSubject.getAttribute('data-subject-id');
        }
        await Promise.all([loadData(), loadTemplates()]);
    }

    async function loadData() {
        try {
            let url = 'class_detail_edit_control.php?action=fetch';
            if (selectedSubjectId) {
                url += `&subject_id=${selectedSubjectId}`;
            }
            const response = await fetch(url);
            const data = await response.json();
            lessonData = data || {}; 
            renderCalendar(currentYear, currentMonth);
            refreshSidebar();
        } catch (e) { 
            console.error("データ読み込み失敗:", e); 
        }
    }

    async function loadTemplates() {
        try {
            const response = await fetch('class_detail_edit_control.php?action=fetch_templates');
            const templates = await response.json();
            itemTagsContainer.innerHTML = ''; 
            if (Array.isArray(templates)) {
                templates.forEach(name => createTagElement(name));
            }
        } catch (e) { 
            console.error("テンプレート読み込み失敗:", e); 
        }
    }

    function createTagElement(name) {
        const div = document.createElement('div');
        div.className = 'item-tag-container';
        div.innerHTML = `<span class="item-tag">${name}</span>`;
        itemTagsContainer.appendChild(div);
    }

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

    window.openModalWithDate = function(dateKey) {
        selectedDateKey = dateKey;
        const data = lessonData[dateKey] || { content: "", belongings: "", status: "" };
        const [year, month, dayNum] = dateKey.split('-').map(Number);
        const dateObj = new Date(year, month - 1, dayNum);
        lessonContentInput.value = data.content || "";
        belongingsTextarea.value = data.belongings || "";
        charCountDisplay.textContent = `${(lessonContentInput.value).length}/200文字`;
        const modalTitleDate = document.querySelector('.modal-date');
        if (modalTitleDate) {
            modalTitleDate.textContent = `${month}月${dayNum}日(${dateObj.toLocaleDateString('ja-JP', { weekday: 'short' })})`;
        }
        lessonModal.style.display = 'flex';
    };

    // カレンダー描画関数
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
                
                const dateObj = new Date(year, month - 1, dayNum);
                const currentDayOfWeek = dateObj.getDay(); // 0:日, 1:月, 2:火, 3:水, 4:木, 5:金, 6:土

                cell.innerHTML = `<span class="date-num">${dayNum}</span>`;

                // ★判定ロジックの修正：[1, 3, 5] という数値配列に合わせた比較
                if (typeof teacherSchedules !== 'undefined' && Array.isArray(teacherSchedules)) {
                    // DBの 1:月 ... 7:日 を JSの曜日に変換
                    const hasLesson = teacherSchedules.some(dbDay => {
                        let jsDay = (parseInt(dbDay) === 7) ? 0 : parseInt(dbDay); 
                        return jsDay === currentDayOfWeek;
                    });

                    if (hasLesson) {
                        cell.classList.add('is-teacher-assigned');
                    }
                }

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
    document.querySelectorAll('#subjectDropdownMenu a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const subjectId = this.getAttribute('data-subject-id');
            if (!subjectId) return;
            selectedSubjectId = subjectId;
            const toggleLabel = document.querySelector('#subjectDropdownToggle .current-value');
            if (toggleLabel) toggleLabel.textContent = this.textContent;
            const displayCourseName = document.getElementById('displayCourseName');
            if (displayCourseName) displayCourseName.textContent = this.textContent;
            loadData();
            this.closest('.dropdown-menu').classList.remove('is-open');
        });
    });

    deleteIcon.onclick = () => {
        isDeleteMode = !isDeleteMode;
        deleteIcon.classList.toggle('is-active', isDeleteMode);
        addBtn.textContent = isDeleteMode ? '削除' : '追加';
        addBtn.classList.toggle('is-delete-mode', isDeleteMode);
    };

    addBtn.onclick = async () => {
        const name = itemInput.value.trim();
        if (!isDeleteMode && name) {
            createTagElement(name);
            await saveTemplateToDB(name);
            itemInput.value = '';
        }
    };

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
            if (selectedSubjectId) {
                formData.append('subject_id', selectedSubjectId);
            }
        }
        try {
            const response = await fetch('class_detail_edit_control.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });
            const result = await response.json();
            if (result.status === "success") {
                await loadData();
                lessonModal.style.display = 'none';
            }
        } catch (e) { 
            console.error("保存失敗:", e); 
        }
    }

    completeButton.onclick = () => saveTextDataToStorage("in-progress");
    tempSaveButton.onclick = () => saveTextDataToStorage("creating");
    deleteButton.onclick = () => confirm("この日のデータを削除しますか？") && saveTextDataToStorage(null, true);
    
    leftArrowButton.onclick = () => { 
        currentMonth--; 
        if (currentMonth < 1) { currentMonth = 12; currentYear--; } 
        loadData(); 
    };
    rightArrowButton.onclick = () => { 
        currentMonth++; 
        if (currentMonth > 12) { currentMonth = 1; currentYear++; } 
        loadData(); 
    };

    lessonModal.addEventListener('click', (e) => { 
        if (e.target === lessonModal) lessonModal.style.display = 'none'; 
    });

    lessonContentInput.addEventListener('input', () => {
        charCountDisplay.textContent = `${lessonContentInput.value.length}/200文字`;
    });

    dropdownToggles.forEach(toggle => {
        const menu = toggle.nextElementSibling;
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            document.querySelectorAll('.dropdown-menu.is-open').forEach(m => {
                if (m !== menu) m.classList.remove('is-open');
            });
            menu.classList.toggle('is-open');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu.is-open').forEach(m => m.classList.remove('is-open'));
    });

    init();
});