document.addEventListener('DOMContentLoaded', function() {
    // --- 1. 変数・要素の定義 ---
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    // 授業データを保持する変数（初期状態は空。loadDataでDBから取得）
    let lessonData = {};

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

    let currentYear = 2026;
    let currentMonth = 1;
    let selectedDateKey = null;
    let isDeleteMode = false;
    let selectedItemsForDelete = []; 

    // --- 【追加】DBからデータを読み込む関数 ---
    async function loadData() {
        try {
            // PHPのcontrolファイルにデータ取得のリクエストを送る
            const response = await fetch('class_detail_edit_control.php?action=fetch');
            const data = await response.json();
            if (data) {
                lessonData = data;
                // データを取得した後にカレンダーとサイドバーを再描画
                renderCalendar(currentYear, currentMonth);
                refreshSidebar();
            }
        } catch (e) {
            console.error("データ読み込み失敗:", e);
        }
    }

    // --- 2. 反映処理（サイドバー・表示更新） ---
    function refreshSidebar() {
        const sortedDates = Object.keys(lessonData).sort((a, b) => new Date(a) - new Date(b));
        const sidebarItems = document.querySelectorAll('.sidebar .lesson-status-wrapper');

        sidebarItems.forEach((targetWrapper, index) => {
            const dateDisplay = targetWrapper.querySelector('.lesson-date-item');
            const statusBtn = targetWrapper.querySelector('.status-button');

            if (index < sortedDates.length) {
                const key = sortedDates[index];
                const data = lessonData[key];
                
                targetWrapper.style.cursor = 'pointer';
                targetWrapper.onclick = function() {
                    openModalWithDate(key);
                };

                const dateObj = new Date(key);
                const m = dateObj.getMonth() + 1;
                const d = dateObj.getDate();
                const dayOfWeek = dateObj.toLocaleDateString('ja-JP', { weekday: 'short' });

                if (dateDisplay) {
                    dateDisplay.textContent = data.slot ? `${m}月${d}日(${dayOfWeek}) ${data.slot}` : `${m}月${d}日(${dayOfWeek})`;
                }
                if (statusBtn) {
                    // DBのステータスに応じてテキストを決定
                    let text = data.statusText;
                    if (!text) {
                        text = (data.status === "in-progress") ? "作成済み" : (data.status === "creating" ? "作成中" : "未作成");
                    }
                    statusBtn.textContent = text;
                    statusBtn.className = `status-button ${data.status || 'not-created'}`;
                }
            }
        });
    }

    function updateAllViews(dateKey, slot, status, text, isDelete = false) {
        if (isDelete) {
            delete lessonData[dateKey];
        } else {
            const currentSlot = lessonData[dateKey] ? lessonData[dateKey].slot : "1限";
            lessonData[dateKey] = { slot: currentSlot, status: status, statusText: text };
        }
        // 画面を再読み込みせずに最新状態を反映
        refreshSidebar();
        renderCalendar(currentYear, currentMonth);
    }

    // --- 3. ドロップダウン制御 ---
    dropdownToggles.forEach(toggle => {
        const dropdownMenu = toggle.nextElementSibling;
        toggle.addEventListener('click', function(event) {
            event.stopPropagation();
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
            dropdownMenu.classList.toggle('is-open');

            dropdownToggles.forEach(otherToggle => {
                if (otherToggle !== this) {
                    otherToggle.setAttribute('aria-expanded', 'false');
                    otherToggle.nextElementSibling.classList.remove('is-open');
                }
            });
            updateDropdownPosition(this, dropdownMenu);
        });

        dropdownMenu.querySelectorAll('a').forEach(item => {
            item.addEventListener('click', function(event) {
                event.preventDefault();
                const selectedValue = this.textContent;
                toggle.querySelector('.current-value').textContent = selectedValue;
                toggle.setAttribute('aria-expanded', 'false');
                dropdownMenu.classList.remove('is-open');
            });
        });
    });

    // --- 4. カレンダー描画とモーダル制御 ---
    function openModalWithDate(dateKey) {
        selectedDateKey = dateKey;
        const [year, month, dayNum] = dateKey.split('-').map(Number);

        // --- 【修正：情報を表示する機能】 ---
        // lessonData[dateKey] に保存されている内容を取得
        const existingData = lessonData[dateKey] || {};
        
        // 入力欄を取得
        const lessonTextArea = document.querySelector('.lesson-details-textarea');
        const belongingsTextArea = document.getElementById('detailsTextarea');
        const charCountDisplay = document.querySelector('.char-count');

        // データがあれば表示、なければ空にする（作成中・作成済みどちらでも動作）
        lessonTextArea.value = existingData.content || "";
        belongingsTextArea.value = existingData.belongings || "";
        
        // 文字数カウントを更新
        if (charCountDisplay) {
            charCountDisplay.textContent = `${lessonTextArea.value.length}/200文字`;
        }
        // -----------------------------------

        const dateObj = new Date(year, month - 1, dayNum);
        const dayOfWeek = dateObj.toLocaleDateString('ja-JP', { weekday: 'short' });
        const modalTitleDate = document.querySelector('.modal-date');
        if (modalTitleDate) modalTitleDate.textContent = `${month}月${dayNum}日(${dayOfWeek})`;

        lessonModal.style.display = 'flex';
    }

    function renderCalendar(year, month) {
        const oldCells = calendarGrid.querySelectorAll('.date-cell');
        oldCells.forEach(cell => cell.remove());
        const firstDay = new Date(year, month - 1, 1).getDay();
        const daysInMonth = new Date(year, month, 0).getDate();
        const prevMonthLastDay = new Date(year, month - 1, 0).getDate();

        for (let i = 0; i < 35; i++) {
            let dayNum, isOutOfMonth = false;
            if (i < firstDay) {
                dayNum = prevMonthLastDay - (firstDay - 1 - i);
                isOutOfMonth = true;
            } else if (i >= firstDay + daysInMonth) {
                dayNum = i - (firstDay + daysInMonth) + 1;
                isOutOfMonth = true;
            } else {
                dayNum = i - firstDay + 1;
            }
            const cell = createDateCell(dayNum, isOutOfMonth, year, month);
            if (i % 7 === 0) cell.classList.add('is-sunday');
            if (i % 7 === 6) cell.classList.add('is-saturday');
            calendarGrid.appendChild(cell);
        }
        monthElement.textContent = `${month}月`;
    }

    function createDateCell(dayNum, isOutOfMonth, year, month) {
        const cell = document.createElement('div');
        cell.className = 'date-cell';
        if (isOutOfMonth) cell.classList.add('is-out-of-month');
        cell.innerHTML = `<span class="date-num">${dayNum}</span>`;

        const mm = String(month).padStart(2, '0');
        const dd = String(dayNum).padStart(2, '0');
        const dateKey = `${year}-${mm}-${dd}`;

        if (!isOutOfMonth && lessonData[dateKey]) {
            const data = lessonData[dateKey];
            cell.classList.add('has-data');
            let text = data.statusText;
            if (!text) {
                text = (data.status === "in-progress") ? "作成済み" : (data.status === "creating" ? "作成中" : "未作成");
            }
            cell.innerHTML += `
                <span class="lesson-slot">${data.slot || '1限'}</span>
                <span class="status-button ${data.status}">${text}</span>
            `;
        }

        cell.addEventListener('click', function() {
            if (isOutOfMonth) return;
            openModalWithDate(dateKey);
        });
        return cell;
    }

    function updateDropdownPosition(toggle, menu) {
        const toggleRect = toggle.getBoundingClientRect();
        const sidebarRect = document.querySelector('.sidebar').getBoundingClientRect();
        menu.style.top = `${toggleRect.top - sidebarRect.top}px`;
        menu.style.left = `${toggleRect.right - sidebarRect.left}px`;
    }

    // --- 5. 持ち物管理 ---
    deleteIcon.addEventListener('click', function() {
        isDeleteMode = !isDeleteMode;
        this.classList.toggle('is-active', isDeleteMode);
        addBtn.textContent = isDeleteMode ? '削除' : '追加';
        addBtn.classList.toggle('is-delete-mode', isDeleteMode);
        itemInput.value = '';
    });

    itemTagsContainer.addEventListener('click', function(e) {
        const tagContainer = e.target.closest('.item-tag-container');
        if (!tagContainer) return;
        const itemName = tagContainer.querySelector('.item-tag').textContent;

        if (isDeleteMode) {
            tagContainer.classList.toggle('is-selected');
            if (tagContainer.classList.contains('is-selected')) {
                selectedItemsForDelete.push(itemName);
            } else {
                selectedItemsForDelete = selectedItemsForDelete.filter(i => i !== itemName);
            }
            itemInput.value = selectedItemsForDelete.join('、');
        } else {
            const detailsTextarea = document.getElementById('detailsTextarea');
            let items = detailsTextarea.value ? detailsTextarea.value.split('、') : [];
            if (!items.includes(itemName)) {
                items.push(itemName);
            } else {
                items = items.filter(i => i !== itemName);
            }
            detailsTextarea.value = items.filter(i => i !== "").join('、');
        }
    });

    addBtn.addEventListener('click', function() {
        if (isDeleteMode) {
            document.querySelectorAll('.item-tag-container.is-selected').forEach(tag => tag.remove());
            itemInput.value = '';
            selectedItemsForDelete = [];
        } else {
            const val = itemInput.value.trim();
            if (val !== "") {
                const newTag = document.createElement('div');
                newTag.className = 'item-tag-container';
                newTag.innerHTML = `<span class="item-tag">${val}</span>`;
                itemTagsContainer.appendChild(newTag);
                itemInput.value = '';
            }
        }
    });

    // --- 6. 通信関数 ---
    async function saveTextDataToStorage(status, statusText, isDelete = false) {
        if (!selectedDateKey) return;

        const formData = new URLSearchParams();
        formData.append('date', selectedDateKey);

        if (isDelete) {
            formData.append('action', 'delete');
        } else {
            const lessonVal = document.querySelector('.lesson-details-textarea').value;
            const belongingsVal = document.getElementById('detailsTextarea').value;
            formData.append('action', 'save');
            formData.append('content', lessonVal);
            formData.append('status', status); 
            formData.append('belongings', belongingsVal);
        }

        try {
            const response = await fetch('class_detail_edit_control.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });

            const responseText = await response.text();
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                console.error("JSON解析エラー。サーバーからの応答:", responseText);
                throw new Error("サーバーから不正な応答がありました。");
            }

            if (result.status === "success") {
                await loadData();
                lessonModal.style.display = 'none';
                alert(result.message);
            } else {
                alert("保存エラー: " + result.message);
            }
        } catch (error) {
            console.error("通信失敗:", error);
            alert("サーバーとの通信に失敗しました。");
        }
    }

    // --- イベント紐付け ---
    completeButton.addEventListener('click', () => saveTextDataToStorage("in-progress", "作成済み"));
    tempSaveButton.addEventListener('click', () => saveTextDataToStorage("creating", "作成中"));
    deleteButton.addEventListener('click', () => {
        if (confirm("データを削除しますか？")) saveTextDataToStorage(null, null, true);
    });

    leftArrowButton.addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 1) { currentMonth = 12; currentYear--; }
        renderCalendar(currentYear, currentMonth);
    });

    rightArrowButton.addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 12) { currentMonth = 1; currentYear++; }
        renderCalendar(currentYear, currentMonth);
    });

    const lessonTextAreaInput = document.querySelector('.lesson-details-textarea');
    lessonTextAreaInput.addEventListener('input', function() {
        document.querySelector('.char-count').textContent = `${this.value.length}/200文字`;
    });

    lessonModal.addEventListener('click', (e) => {
        if (e.target === lessonModal) lessonModal.style.display = 'none';
    });

    // 初期実行
    renderCalendar(currentYear, currentMonth);
    loadData();
});