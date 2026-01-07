document.addEventListener('DOMContentLoaded', () => {
    // ==========================================
    // 変数定義
    // ==========================================
    let savedTimetables = [];
    let isCreatingMode = false;
    let isViewOnly = false;
    let currentRecord = null;
    let tempCreatingData = null;
    let originalRecordData = null; // 編集前のオリジナルデータ
    let previousState = null; // 新規作成前の状態を保存
    let activeCell = null; // クリックされたセル

    // ==========================================
    // DOM要素の取得
    // ==========================================
    const mainCreateNewBtn = document.getElementById('mainCreateNewBtn');
    const defaultNewBtnArea = document.getElementById('defaultNewBtnArea');
    const creatingItemArea = document.getElementById('creatingItemArea');
    const creatingItemCard = document.getElementById('creatingItemCard');
    const creatingCourseName = document.getElementById('creatingCourseName');
    const mainStartDate = document.getElementById('mainStartDate');
    const mainEndDate = document.getElementById('mainEndDate');
    const resetViewBtn = document.getElementById('resetViewBtn');
    const footerArea = document.getElementById('footerArea');
    const completeButton = document.getElementById('completeButton');
    const cancelCreationBtn = document.getElementById('cancelCreationBtn');
    
    // コース選択ドロップダウン関連
    const courseDropdownToggle = document.getElementById('courseDropdownToggle');
    const courseDropdownMenu = document.getElementById('courseDropdownMenu');
    const courseDropdownCurrent = courseDropdownToggle.querySelector('.current-value');
    const displayModeRadios = document.querySelectorAll('input[name="displayMode"]');
    
    // 作成モーダル関連
    const createModal = document.getElementById('createModal');
    const createGradeSelect = document.getElementById('createGradeSelect');
    const createCourseSelect = document.getElementById('createCourseSelect');
    const checkCsv = document.getElementById('checkCsv');
    const csvInputArea = document.getElementById('csvInputArea');
    const createSubmitBtn = document.getElementById('createSubmitBtn');
    const createCancelBtn = document.getElementById('createCancelBtn');
    
    // 授業詳細モーダル関連
    const classModal = document.getElementById('classModal');
    const inputClassName = document.getElementById('inputClassName');
    const inputTeacherName = document.getElementById('inputTeacherName');
    const inputRoomName = document.getElementById('inputRoomName');
    const btnCancel = document.getElementById('btnCancel');
    const btnSave = document.getElementById('btnSave');
    const modalTitle = document.getElementById('modalTitle');

    // コース定義データ
    const courseData = {
        "1": ["１年１組", "１年２組", "基本情報コース", "応用情報コース"],
        "2": ["システムデザインコース", "Webクリエイタコース", "マルチメディアOAコース"],
        "all": ["１年１組", "１年２組", "基本情報コース", "応用情報コース", "システムデザインコース", "Webクリエイタコース", "マルチメディアOAコース"]
    };

    // ==========================================
    // 初期化処理
    // ==========================================
    initializeDemoData();
    selectInitialTimetable();

    // ==========================================
    // イベントリスナー設定
    // ==========================================

    // ドロップダウンの開閉制御
    if (courseDropdownToggle) {
        courseDropdownToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            // 無効化されている場合は開かない
            if (courseDropdownToggle.classList.contains('disabled')) return;
            
            courseDropdownMenu.classList.toggle('show');
            const expanded = courseDropdownMenu.classList.contains('show');
            courseDropdownToggle.setAttribute('aria-expanded', expanded);
        });
    }

    // ドロップダウン外クリックで閉じる
    window.addEventListener('click', (e) => {
        if (courseDropdownMenu && courseDropdownMenu.classList.contains('show')) {
            if (!courseDropdownToggle.contains(e.target) && !courseDropdownMenu.contains(e.target)) {
                courseDropdownMenu.classList.remove('show');
                courseDropdownToggle.setAttribute('aria-expanded', 'false');
            }
        }
    });

    // ドロップダウンメニューの項目クリック
    if (courseDropdownMenu) {
        courseDropdownMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const selectedCourse = e.target.getAttribute('data-course');
                if (courseDropdownCurrent) {
                    courseDropdownCurrent.textContent = selectedCourse;
                }
                courseDropdownMenu.classList.remove('show');
                courseDropdownToggle.setAttribute('aria-expanded', 'false');
                
                // 選択されたコースに基づいてリストを更新
                renderSavedList('select');
            });
        });
    }

    // 表示モードラジオボタンの変更監視
    displayModeRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            changeDisplayMode(e.target.value);
        });
    });

    // 「作成中」カードクリック
    if (creatingItemCard) {
        creatingItemCard.addEventListener('click', () => {
            if (!isCreatingMode) return;
            
            // 作成中に戻る = 閲覧モード解除
            isViewOnly = false;
            currentRecord = null;
            originalRecordData = null;
            
            restoreTempCreatingData();
            
            resetViewBtn.classList.add('hidden');
            footerArea.classList.add('show');
            completeButton.textContent = '完了';
            cancelCreationBtn.textContent = 'キャンセル';
            mainStartDate.disabled = true;
            mainEndDate.disabled = true;
            
            document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
        });
    }

    // 適用期間の変更監視
    [mainStartDate, mainEndDate].forEach(input => {
        if (!input) return;
        input.addEventListener('input', () => {
            if (!isCreatingMode && currentRecord && originalRecordData) {
                if (mainStartDate.value !== originalRecordData.startDate || mainEndDate.value !== originalRecordData.endDate) {
                    cancelCreationBtn.textContent = '変更を破棄';
                    cancelCreationBtn.disabled = false;
                    cancelCreationBtn.style.opacity = '1';
                    cancelCreationBtn.style.cursor = 'pointer';
                } else {
                    // 元の値に戻った場合
                    if (isRecordActive(currentRecord)) {
                        cancelCreationBtn.textContent = '削除不可（適用中）';
                        cancelCreationBtn.disabled = true;
                        cancelCreationBtn.style.opacity = '0.5';
                        cancelCreationBtn.style.cursor = 'not-allowed';
                    } else {
                        cancelCreationBtn.textContent = '削除';
                        cancelCreationBtn.disabled = false;
                        cancelCreationBtn.style.opacity = '1';
                        cancelCreationBtn.style.cursor = 'pointer';
                    }
                }
            }
        });
    });

    // 表示解除ボタン
    if (resetViewBtn) {
        resetViewBtn.addEventListener('click', () => {
            // 作成中モードでのみ使用（作成中に戻る）
            if (isCreatingMode) {
                creatingItemCard.click();
                return;
            }
        });
    }

    // 新規作成ボタン
    if (mainCreateNewBtn) {
        mainCreateNewBtn.addEventListener('click', () => {
            // 編集中の時間割がある場合は確認
            if (currentRecord && originalRecordData && !isCreatingMode && !isViewOnly) {
                const hasDateChanges = mainStartDate.value !== originalRecordData.startDate || mainEndDate.value !== originalRecordData.endDate;
                const hasGridChanges = getGridDataForComparison(getCurrentGridData()) !== getGridDataForComparison(originalRecordData.data);
                
                if (hasDateChanges || hasGridChanges) {
                    if (!confirm('編集中の内容は保存されていません。\n新規作成を開始しますか？')) {
                        return;
                    }
                }
            }
            
            savePreviousState();
            openCreateModal();
        });
    }

    // 新規作成モーダルのキャンセル
    if (createCancelBtn) {
        createCancelBtn.addEventListener('click', () => {
            createModal.classList.add('hidden');
            document.body.classList.remove('modal-open');
            restorePreviousState();
        });
    }

    // 新規作成モーダルの学年選択
    if (createGradeSelect) {
        createGradeSelect.addEventListener('change', function() {
            const grade = this.value;
            createCourseSelect.innerHTML = '<option value="">コースを選択してください</option>';
            if (grade && courseData[grade]) {
                courseData[grade].forEach(c => {
                    const op = document.createElement('option');
                    op.value = c; op.textContent = c; createCourseSelect.appendChild(op);
                });
                createCourseSelect.disabled = false;
            } else {
                createCourseSelect.innerHTML = '<option value="">先に学年を選択してください</option>';
                createCourseSelect.disabled = true;
            }
            checkCreateValidation();
        });
    }

    // 新規作成モーダルの各入力監視（バリデーション用）
    if (createCourseSelect) createCourseSelect.addEventListener('change', checkCreateValidation);
    if (checkCsv) {
        checkCsv.addEventListener('change', () => {
            if (checkCsv.checked) csvInputArea.classList.add('active');
            else csvInputArea.classList.remove('active');
            checkCreateValidation();
        });
    }
    const csvFile = document.getElementById('csvFile');
    if (csvFile) csvFile.addEventListener('change', checkCreateValidation);

    // 新規作成実行ボタン
    if (createSubmitBtn) {
        createSubmitBtn.addEventListener('click', executeCreateNew);
    }

    // フッターボタン（完了/保存）
    if (completeButton) {
        completeButton.addEventListener('click', handleCompleteButton);
    }

    // フッターボタン（キャンセル/削除）
    if (cancelCreationBtn) {
        cancelCreationBtn.addEventListener('click', handleCancelButton);
    }

    // 時間割セルクリック（授業詳細モーダル表示）
    document.addEventListener('click', (e) => {
        const cell = e.target.closest('.timetable-cell');
        if (cell) {
            if (!isCreatingMode && !isViewOnly) return;
            openClassModal(cell);
        }
    });

    // 授業詳細モーダルのボタン
    if (btnCancel) {
        btnCancel.addEventListener('click', () => {
            classModal.classList.add('hidden');
            document.body.classList.remove('modal-open');
            activeCell = null;
        });
    }
    if (btnSave) {
        btnSave.addEventListener('click', saveClassData);
    }

    // ==========================================
    // 関数定義
    // ==========================================

    function initializeDemoData() {
        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];
        const lastMonth = new Date(today); lastMonth.setMonth(lastMonth.getMonth() - 1);
        const lastMonthStr = lastMonth.toISOString().split('T')[0];
        const nextMonth = new Date(today); nextMonth.setMonth(nextMonth.getMonth() + 1);
        const nextMonthStr = nextMonth.toISOString().split('T')[0];
        const twoMonthsLater = new Date(today); twoMonthsLater.setMonth(twoMonthsLater.getMonth() + 2);
        const twoMonthsLaterStr = twoMonthsLater.toISOString().split('T')[0];
        const threeMonthsLater = new Date(today); threeMonthsLater.setMonth(threeMonthsLater.getMonth() + 3);
        const threeMonthsLaterStr = threeMonthsLater.toISOString().split('T')[0];

        savedTimetables = [
            {
                id: 1001, course: "システムデザインコース", startDate: lastMonthStr, endDate: twoMonthsLaterStr,
                data: [
                    {day: "月", period: "1", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "301演習室"},
                    {day: "月", period: "2", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "302演習室"},
                    {day: "火", period: "1", className: "データベース基礎", teacherName: "高橋 誠", roomName: "201教室"},
                    {day: "水", period: "1", className: "ネットワーク構築", teacherName: "田中 優子", roomName: "301演習室"},
                    {day: "木", period: "1", className: "セキュリティ概論", teacherName: "渡辺 剛", roomName: "4F大講義室"},
                    {day: "金", period: "1", className: "HR", teacherName: "伊藤 直人", roomName: "202教室"}
                ]
            },
            {
                id: 1002, course: "Webクリエイタコース", startDate: lastMonthStr, endDate: nextMonthStr,
                data: [
                    {day: "月", period: "1", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "302演習室"},
                    {day: "月", period: "2", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "301演習室"},
                    {day: "火", period: "1", className: "プロジェクト管理", teacherName: "山本 さくら", roomName: "202教室"}
                ]
            },
            {
                id: 1003, course: "マルチメディアOAコース", startDate: nextMonthStr, endDate: threeMonthsLaterStr,
                data: [
                    {day: "月", period: "1", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "302演習室"},
                    {day: "火", period: "1", className: "データベース基礎", teacherName: "高橋 誠", roomName: "201教室"}
                ]
            },
            {
                id: 1004, course: "１年１組", startDate: nextMonthStr, endDate: twoMonthsLaterStr,
                data: [
                    {day: "月", period: "1", className: "データベース基礎", teacherName: "高橋 誠", roomName: "201教室"},
                    {day: "火", period: "1", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "301演習室"}
                ]
            }
        ];
    }

    function selectInitialTimetable() {
        const priorityCourses = ["システムデザインコース", "Webクリエイタコース", "マルチメディアOAコース", "応用情報コース", "基本情報コース", "ITパスポートコース", "１年１組", "１年２組"];
        for (const courseName of priorityCourses) {
            const record = savedTimetables.find(r => r.course === courseName);
            if (record) {
                if(courseDropdownCurrent) courseDropdownCurrent.textContent = courseName;
                renderSavedList('select');
                setTimeout(() => {
                    const targetItem = document.querySelector(`.saved-item[data-id="${record.id}"]`);
                    if (targetItem) targetItem.click();
                }, 100);
                return;
            }
        }
    }

    function changeDisplayMode(mode) {
        if (mode === 'select') {
            courseDropdownToggle.classList.remove('disabled');
        } else {
            courseDropdownToggle.classList.add('disabled');
        }
        renderSavedList(mode);
    }

    function renderSavedList(mode) {
        const container = document.getElementById('savedListContainer');
        const divider = document.getElementById('savedListDivider');
        if(!container) return;

        const items = container.querySelectorAll('li:not(.is-group-label)');
        items.forEach(item => item.remove());
        
        let filteredRecords = savedTimetables;
        
        if (mode === 'select') {
            const currentCourse = courseDropdownCurrent ? courseDropdownCurrent.textContent : '';
            filteredRecords = filteredRecords.filter(item => item.course === currentCourse);
        }
        
        const todayStr = new Date().toISOString().split('T')[0];
        if (mode === 'current') {
            filteredRecords = filteredRecords.filter(item => {
                if (!item.startDate) return false;
                const isStarted = item.startDate <= todayStr;
                const isNotEnded = !item.endDate || item.endDate >= todayStr;
                return isStarted && isNotEnded;
            });
        } else if (mode === 'next') {
            filteredRecords = filteredRecords.filter(item => {
                if (!item.startDate) return false;
                return item.startDate > todayStr;
            });
        }
        
        filteredRecords.sort((a, b) => {
            const aActive = isRecordActive(a) ? 0 : 1;
            const bActive = isRecordActive(b) ? 0 : 1;
            return aActive - bActive;
        });
        
        if (filteredRecords.length > 0) {
            container.classList.remove('hidden');
            if(divider) divider.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
            if(divider) divider.classList.add('hidden');
        }
        
        filteredRecords.forEach(record => {
            const dateLabel = getFormattedDate(record.startDate);
            const li = document.createElement('li');
            li.className = 'saved-item';
            li.setAttribute('data-id', record.id);
            
            let statusBadge = '';
            if (isRecordActive(record)) {
                statusBadge = '<span class="status-badge current">適用中</span>';
            } else if (record.startDate > todayStr) {
                statusBadge = '<span class="status-badge next">次回</span>';
            }
            
            li.innerHTML = `
                <div class="saved-item-content">
                    <span class="saved-item-date">${dateLabel}</span>
                    <span class="saved-item-course">${record.course}</span>
                    ${statusBadge}
                </div>
                <i class="fa-solid fa-chevron-right text-slate-300 text-xs"></i>
            `;
            
            li.addEventListener('click', () => {
                handleSavedItemClick(li, record);
            });
            
            container.appendChild(li);
        });
    }

    function handleSavedItemClick(li, record) {
        if (isCreatingMode) {
            if(!confirm('作成中のデータを破棄して、選択した時間割を表示しますか？')) return;
            toggleCreatingMode(false);
        }
        
        document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
        li.classList.add('active');
        
        currentRecord = record;
        originalRecordData = JSON.parse(JSON.stringify(record));
        
        updateHeaderDisplay(record);
        mainStartDate.value = record.startDate;
        mainEndDate.value = record.endDate;
        
        isViewOnly = true;
        resetViewBtn.classList.remove('hidden');
        footerArea.classList.add('show');
        completeButton.textContent = '保存';
        
        // グリッド描画
        clearGrid();
        record.data.forEach(item => {
            const targetCell = document.querySelector(`.timetable-cell[data-day="${item.day}"][data-period="${item.period}"]`);
            fillCell(targetCell, item);
        });
        
        // 削除ボタン状態更新
        if (mainStartDate) mainStartDate.dispatchEvent(new Event('input'));
    }

    function toggleCreatingMode(isCreating, courseName = '', sDate = '', eDate = '') {
        isCreatingMode = isCreating;
        if (isCreating) {
            isViewOnly = false;
            resetViewBtn.classList.add('hidden');
            defaultNewBtnArea.classList.add('hidden');
            creatingItemArea.classList.remove('hidden');
            creatingCourseName.textContent = courseName;
            mainStartDate.disabled = false;
            mainEndDate.disabled = false;
            footerArea.classList.add('show');
            completeButton.textContent = '完了';
            cancelCreationBtn.textContent = 'キャンセル';
        } else {
            defaultNewBtnArea.classList.remove('hidden');
            creatingItemArea.classList.add('hidden');
            mainStartDate.disabled = false;
            mainEndDate.disabled = false;
            mainStartDate.value = '';
            mainEndDate.value = '';
            clearGrid();
            document.getElementById('mainCourseDisplay').innerHTML = "（未選択）";
            tempCreatingData = null;
            currentRecord = null;
            originalRecordData = null;
            footerArea.classList.remove('show');
        }
    }

    function checkCreateValidation() {
        const grade = createGradeSelect.value;
        const course = createCourseSelect.value;
        const isCsv = checkCsv.checked;
        const csvFile = document.getElementById('csvFile');
        const hasFile = csvFile && csvFile.files.length > 0;
        let isValid = grade !== "" && course !== "";
        if (isCsv && !hasFile) isValid = false;
        createSubmitBtn.disabled = !isValid;
    }

    function openCreateModal() {
        // 現在の状態をクリア
        currentRecord = null;
        originalRecordData = null;
        isViewOnly = false;
        document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
        footerArea.classList.remove('show');
        mainStartDate.disabled = false;
        mainEndDate.disabled = false;
        clearGrid();
        document.getElementById('mainCourseDisplay').innerHTML = "（未選択）";
        
        createModal.classList.remove('hidden');
        document.body.classList.add('modal-open');
        createGradeSelect.value = "";
        createCourseSelect.innerHTML = '<option value="">先に学年を選択してください</option>';
        createCourseSelect.disabled = true;
        checkCsv.checked = false;
        csvInputArea.classList.remove('active');
        checkCreateValidation();
    }

    function executeCreateNew() {
        const selectedCourse = createCourseSelect.value;
        const sDate = mainStartDate.value;
        const eDate = mainEndDate.value;
        
        if (sDate && eDate) {
            const hasOverlap = savedTimetables.some(record => {
                if (record.course !== selectedCourse) return false;
                const recEnd = record.endDate || record.startDate;
                return (sDate <= recEnd) && (eDate >= record.startDate);
            });
            if (hasOverlap) {
                alert('指定された適用期間は、同じコースの既存の時間割と重複しています。');
                return;
            }
        }
        
        toggleCreatingMode(true, selectedCourse, sDate, eDate);
        document.getElementById('mainCourseDisplay').innerHTML = selectedCourse;
        
        const selectRadio = document.querySelector('input[value="select"]');
        if (selectRadio) {
            selectRadio.checked = true;
            // ラジオボタン変更時の処理を手動発火
            changeDisplayMode('select');
        }
        if(courseDropdownCurrent) courseDropdownCurrent.textContent = selectedCourse;
        renderSavedList('select');
        
        clearGrid();
        
        createModal.classList.add('hidden');
        document.body.classList.remove('modal-open');
        setTimeout(() => alert(`「${selectedCourse}」の作成を開始します。`), 10);
    }

    function openClassModal(cell) {
        activeCell = cell;
        const currentClass = cell.querySelector('.class-name')?.textContent || '';
        const currentTeacher = cell.querySelector('.teacher-name span')?.textContent || '';
        const currentRoom = cell.querySelector('.room-name span')?.textContent || '';
        
        inputClassName.value = currentClass;
        inputTeacherName.value = currentTeacher;
        inputRoomName.value = currentRoom;
        
        const day = cell.getAttribute('data-day');
        const period = cell.getAttribute('data-period');
        modalTitle.textContent = `${day}曜日 ${period}限`;
        
        classModal.classList.remove('hidden');
        document.body.classList.add('modal-open');
    }

    function saveClassData() {
        if (activeCell) {
            const className = inputClassName.value;
            const teacherName = inputTeacherName.value;
            const roomName = inputRoomName.value;
            const day = activeCell.getAttribute('data-day');
            const period = activeCell.getAttribute('data-period');
            
            if (className) {
                fillCell(activeCell, { className, teacherName, roomName });
                activeCell.classList.add('is-edited');
                
                // 作成中の一時データを更新
                if(isCreatingMode) {
                    if(!tempCreatingData) tempCreatingData = { gridData: [] };
                    // 既存データを削除して追加
                    tempCreatingData.gridData = tempCreatingData.gridData.filter(d => d.day !== day || d.period !== period);
                    tempCreatingData.gridData.push({day, period, className, teacherName, roomName});
                    // その他メタデータも保持
                    tempCreatingData.courseName = creatingCourseName.textContent;
                    tempCreatingData.startDate = mainStartDate.value;
                    tempCreatingData.endDate = mainEndDate.value;
                }
            } else {
                activeCell.innerHTML = '';
                activeCell.classList.remove('is-filled');
                activeCell.classList.remove('is-edited');
                // 作成中データから削除
                if(isCreatingMode && tempCreatingData) {
                    tempCreatingData.gridData = tempCreatingData.gridData.filter(d => d.day !== day || d.period !== period);
                }
            }
        }
        classModal.classList.add('hidden');
        document.body.classList.remove('modal-open');
        activeCell = null;
    }

    function handleCompleteButton() {
        const currentGrid = getCurrentGridData();
        
        if (isCreatingMode) {
            console.log("New Data:", currentGrid);
            alert('時間割を新規保存しました（デモ）。');
            toggleCreatingMode(false);
            
        } else if (isViewOnly && currentRecord) {
            console.log("Updated Data ID:" + currentRecord.id, currentGrid);
            alert('時間割の変更を保存しました（デモ）。');
            
            currentRecord.data = currentGrid;
            currentRecord.startDate = mainStartDate.value;
            currentRecord.endDate = mainEndDate.value;
            originalRecordData = JSON.parse(JSON.stringify(currentRecord));
            
            updateHeaderDisplay(currentRecord);
            renderSavedList('select');
            document.querySelectorAll('.timetable-cell').forEach(c => c.classList.remove('is-edited'));
        }
    }

    function handleCancelButton() {
        if (isCreatingMode) {
            if (confirm('作成中の内容を破棄して終了しますか？')) {
                toggleCreatingMode(false);
            }
        } else if (isViewOnly && currentRecord) {
            if (cancelCreationBtn.textContent.includes('変更を破棄')) {
                if (confirm('変更した内容を元に戻しますか？')) {
                    mainStartDate.value = originalRecordData.startDate;
                    mainEndDate.value = originalRecordData.endDate;
                    clearGrid();
                    originalRecordData.data.forEach(item => {
                        const targetCell = document.querySelector(`.timetable-cell[data-day="${item.day}"][data-period="${item.period}"]`);
                        fillCell(targetCell, item);
                    });
                    mainStartDate.dispatchEvent(new Event('input'));
                }
            } else {
                if (confirm('この時間割データを削除しますか？')) {
                    alert('削除しました（デモ）。');
                    resetViewBtn.click();
                }
            }
        }
    }

    // --- Helper Functions ---

    function clearGrid() {
        document.querySelectorAll('.timetable-cell').forEach(cell => {
            cell.innerHTML = '';
            cell.classList.remove('is-filled');
            cell.classList.remove('is-edited');
        });
    }

    function fillCell(cell, item) {
        if (!cell) return;
        cell.innerHTML = `
        <div class="class-content">
            <div class="class-name">${item.className}</div>
            <div class="class-detail">
                ${item.teacherName ? `<div class="teacher-name"><i class="fa-solid fa-user icon"></i><span>${item.teacherName}</span></div>` : ''}
                ${item.roomName ? `<div class="room-name"><i class="fa-solid fa-location-dot icon"></i><span>${item.roomName}</span></div>` : ''}
            </div>
        </div>`;
        cell.classList.add('is-filled');
    }

    function restoreTempCreatingData() {
        if (tempCreatingData) {
            clearGrid();
            tempCreatingData.gridData.forEach(item => {
                const targetCell = document.querySelector(`.timetable-cell[data-day="${item.day}"][data-period="${item.period}"]`);
                fillCell(targetCell, item);
            });
            document.getElementById('mainCourseDisplay').innerHTML = tempCreatingData.courseName;
            mainStartDate.value = tempCreatingData.startDate;
            mainEndDate.value = tempCreatingData.endDate;
        }
    }

    function savePreviousState() {
        previousState = {
            currentRecord: currentRecord,
            originalRecordData: originalRecordData ? JSON.parse(JSON.stringify(originalRecordData)) : null,
            startDate: mainStartDate.value,
            endDate: mainEndDate.value,
            gridData: getCurrentGridData(),
            courseDisplayText: document.getElementById('mainCourseDisplay').innerHTML,
            selectedItemId: document.querySelector('.saved-item.active')?.getAttribute('data-id')
        };
    }

    function restorePreviousState() {
        if (previousState) {
            currentRecord = previousState.currentRecord;
            originalRecordData = previousState.originalRecordData;
            mainStartDate.value = previousState.startDate;
            mainEndDate.value = previousState.endDate;
            document.getElementById('mainCourseDisplay').innerHTML = previousState.courseDisplayText;
            
            clearGrid();
            previousState.gridData.forEach(item => {
                const targetCell = document.querySelector(`.timetable-cell[data-day="${item.day}"][data-period="${item.period}"]`);
                fillCell(targetCell, item);
            });
            
            if (previousState.selectedItemId) {
                document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
                const targetItem = document.querySelector(`.saved-item[data-id="${previousState.selectedItemId}"]`);
                if (targetItem) targetItem.classList.add('active');
            }
            previousState = null;
        } else {
            if (!isCreatingMode && !currentRecord) {
                mainStartDate.disabled = false;
                mainEndDate.disabled = false;
            }
        }
    }

    function isRecordActive(record) {
        const todayStr = new Date().toISOString().split('T')[0];
        const isStarted = record.startDate <= todayStr;
        const isNotEnded = !record.endDate || record.endDate >= todayStr;
        return isStarted && isNotEnded;
    }

    function updateHeaderDisplay(record) {
        const displayEl = document.getElementById('mainCourseDisplay');
        if(!displayEl) return;
        let badgeHtml = '';
        const todayStr = new Date().toISOString().split('T')[0];
        if(isRecordActive(record)) {
            badgeHtml = '<span class="active-badge current">適用中</span>';
        } else if (record.startDate > todayStr) {
            badgeHtml = '<span class="active-badge next">次回反映</span>';
        }
        displayEl.innerHTML = record.course + badgeHtml;
    }

    function getFormattedDate(inputVal) {
        if (!inputVal) return '';
        const parts = inputVal.split('-');
        if (parts.length !== 3) return '';
        return `${parts[1]}/${parts[2]}~`;
    }

    function getCurrentGridData() {
        const data = [];
        document.querySelectorAll('.timetable-cell.is-filled').forEach(cell => {
            const day = cell.getAttribute('data-day');
            const period = cell.getAttribute('data-period');
            const className = cell.querySelector('.class-name')?.textContent || '';
            const teacherName = cell.querySelector('.teacher-name span')?.textContent || '';
            const roomName = cell.querySelector('.room-name span')?.textContent || '';
            
            if (className) {
                data.push({day, period, className, teacherName, roomName});
            }
        });
        return data;
    }

    function getGridDataForComparison(data) {
        const sorted = [...data].sort((a, b) => {
            if (a.day !== b.day) return a.day.localeCompare(b.day);
            return a.period - b.period;
        });
        return JSON.stringify(sorted);
    }
});