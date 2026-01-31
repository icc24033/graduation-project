// js/class_detail_edit.js

document.addEventListener('DOMContentLoaded', function() {
    // ============================================================
    // 1. 変数・要素の定義
    // ============================================================
    
    // API設定　現在地を表すパスと、相対パスによる文字列結合を利用
    const API_BASE_URL = '../../api/class_detail_edit/class_detail_api.php'; 

    // ドロップダウン用要素（IDを修正後のHTMLに合わせて取得）
    const gradeToggle = document.getElementById('gradeFilterToggle');
    const gradeMenu = document.getElementById('gradeFilterMenu');
    // const courseToggle = document.getElementById('courseFilterToggle');
    // const courseMenu = document.getElementById('courseFilterMenu');
    const subjectToggle = document.getElementById('subjectSelectorToggle');
    const subjectMenu = document.getElementById('subjectSelectorMenu');

    // カレンダー・モーダル用要素
    const monthElement = document.querySelector('.month');
    const leftArrowButton = document.querySelector('.left-arrow')?.closest('button');
    const rightArrowButton = document.querySelector('.right-arrow')?.closest('button');
    const calendarGrid = document.getElementById('calendarGrid');
    if (!calendarGrid) {
        console.error("【重要エラー】HTML内に id='calendarGrid' の要素が見つかりません。HTMLを確認してください。");
    }
    const lessonModal = document.getElementById('lessonModal');
    
    // ボタン類
    const completeButton = document.querySelector('.complete-button');
    const tempSaveButton = document.querySelector('.temp-save-button');
    const deleteButton = document.querySelector('.delete-button');

    // 持ち物管理用要素
    const deleteIcon = document.querySelector('.delete-icon');
    const addBtn = document.querySelector('.add-button');
    const itemInput = document.querySelector('.add-item-input');
    const itemTagsContainer = document.querySelector('.item-tags');

    // 状態管理変数
    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth() + 1;
    let selectedDateKey = null; // "YYYY-MM-DD"
    let lessonData = {}; // サーバーから取得した授業詳細データ
    let selectedSlotKey = null; // "YYYY-MM-DD_1限" など、現在編集中のスロット識別子
    let sidebarData = {}; // サイドバー表示用データ
    let currentEditingSlot = null; // 現在編集中のスロットオブジェクト
    
    // ドロップダウン用状態
    let currentFilters = { grade: ""}; // フィルタ状態
    let currentSubject = null; // 現在選択中の科目オブジェクト {subject_id, course_id, ...}
    
    // 持ち物削除モード
    let isDeleteMode = false;
    let selectedItemsForDelete = []; 

    // PHPからのデータチェック
    if (typeof assignedClassesData === 'undefined') {
        console.error("assignedClassesData is not defined. PHP側を確認してください。");
        window.assignedClassesData = [];
    } else if (!Array.isArray(assignedClassesData)) {
        // PHPの連想配列がJSオブジェクトとして渡ってきた場合の対応
        if (assignedClassesData && typeof assignedClassesData === 'object') {
            console.warn("assignedClassesDataが配列ではありません。オブジェクトを配列に変換します。");
            window.assignedClassesData = Object.values(assignedClassesData);
        } else {
            window.assignedClassesData = [];
        }
    }

    // ============================================================
    // 2. 初期化処理
    // ============================================================
    
    initDropdowns(); // ドロップダウンを生成・初期化

    // ============================================================
    // 3. ドロップダウン & フィルタリング ロジック
    // ============================================================

    function initDropdowns() {
        // 3-1. 学年リストの生成（重複排除）
        const grades = [...new Set(assignedClassesData.map(d => d.grade))].sort();
        renderFilterList(gradeMenu, gradeToggle, grades, 'grade', '年生');

        // コースリスト生成処理を削除
        // const courseMap = new Map();
        // assignedClassesData.forEach(d => courseMap.set(d.course_id, d.course_name));
        // renderCourseFilterList(courseMenu, courseToggle, courseMap);

        // 3-3. 科目リストの初期更新（全件表示 -> 先頭を選択）
        updateSubjectList();

        // 3-4. 開閉イベント設定
        setupDropdownToggle(gradeToggle, gradeMenu);

        // コースドロップダウンのイベント設定を削除
        // setupDropdownToggle(courseToggle, courseMenu);
        setupDropdownToggle(subjectToggle, subjectMenu);
    }

    /**
     * フィルタ条件に基づいて科目リストを再生成し、先頭を自動選択する
     */
    function updateSubjectList() {
        // 1. まずフィルタリング（学年・コースで絞り込み）
        const filteredRaw = assignedClassesData.filter(item => {
            const matchGrade = (currentFilters.grade === "") || (String(item.grade) === String(currentFilters.grade));
            
            // コースでの絞り込みを削除（常にtrue扱いや削除で対応）
            // const matchCourse = (currentFilters.course === "") || (String(item.course_id) === String(currentFilters.course));
            
            // 学年のみで判定
            return matchGrade;
        });

        // 2. 科目IDでグルーピング
        // Map構造: subject_id => { subject_name, course_ids: [], course_names: [] }
        const groupedSubjects = new Map();

        filteredRaw.forEach(item => {
            const sId = item.subject_id;
            if (!groupedSubjects.has(sId)) {
                groupedSubjects.set(sId, {
                    subject_id: sId,
                    subject_name: item.subject_name,
                    courses: [] // 対象となるコース情報を蓄積
                });
            }
            groupedSubjects.get(sId).courses.push({
                id: item.course_id,
                name: item.course_name
            });
        });

        // 3. リスト描画
        subjectMenu.innerHTML = '';
        
        if (groupedSubjects.size === 0) {
            subjectToggle.querySelector('.current-value').textContent = "該当なし";
            subjectMenu.innerHTML = '<li class="dropdown-item">該当する科目がありません</li>';
            currentSubject = null;
            lessonData = {};
            renderCalendar(currentYear, currentMonth);
            return;
        }

        // Mapの値（グルーピングされた科目オブジェクト）を配列にしてループ
        Array.from(groupedSubjects.values()).forEach(group => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = "#";
            
            // 表示名: "C# (システムデザイン, Webクリエイタ)" のようにコースを併記
            // ★ここは変更なし（コース名の表示は維持）
            const courseNames = group.courses.map(c => c.name).join(', ');
            a.textContent = `${group.subject_name}`;
            
            // ホバー時にコース内訳を表示するなどしても親切
            a.title = `対象コース: ${courseNames}`;
            
            // コース名の補足を表示する（HTMLで構造化）
            const subText = document.createElement('span');
            subText.style.fontSize = '0.85em';
            subText.style.color = '#666';
            subText.style.display = 'block';
            subText.textContent = `対象: ${courseNames}`;
            a.appendChild(subText);

            li.appendChild(a);
            li.addEventListener('click', (e) => {
                e.preventDefault();
                selectSubjectGroup(group); 
                closeAllDropdowns();
            });
            subjectMenu.appendChild(li);
        });

        // リストの先頭を自動選択
        const firstGroup = groupedSubjects.values().next().value;
        selectSubjectGroup(firstGroup);
    }

    /**
     * 科目グループを選択したときの処理
     * @param {Object} group - { subject_id, subject_name, courses: [{id, name}, ...] }
     */
    function selectSubjectGroup(group) {
        currentSubject = group;
        subjectToggle.querySelector('.current-value').textContent = group.subject_name;
        
        const displayTitle = document.getElementById('displayCourseName');
        if (displayTitle) displayTitle.textContent = group.subject_name;

        // カレンダー用（表示中の年月）
        fetchLessonData(currentYear, currentMonth);
        
        // サイドバー用（現実の今月・来月）
        fetchSidebarData();
    }

    // --- ドロップダウン生成ヘルパー ---

    function renderFilterList(menu, toggle, values, type, suffix) {
        // 「全〇〇」
        addFilterItem(menu, toggle, "全学年", "", type);
        // 各値
        values.forEach(val => {
            addFilterItem(menu, toggle, `${val}${suffix}`, val, type);
        });
    }

    function renderCourseFilterList(menu, toggle, map) {
        // 「全コース」
        addFilterItem(menu, toggle, "全コース", "", 'course');
        // 各コース
        map.forEach((name, id) => {
            addFilterItem(menu, toggle, name, id, 'course');
        });
    }

    function addFilterItem(menu, toggle, text, value, type) {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.href = "#";
        a.textContent = text;
        li.appendChild(a);
        
        li.addEventListener('click', (e) => {
            e.preventDefault();
            // 状態更新
            currentFilters[type] = value;
            toggle.querySelector('.current-value').textContent = text;
            
            // 科目リストを絞り込み直す
            updateSubjectList();
            closeAllDropdowns();
        });
        menu.appendChild(li);
    }

    function setupDropdownToggle(toggle, menu) {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = menu.classList.contains('is-open');
            closeAllDropdowns();
            if (!isOpen) {
                menu.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        });
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('is-open'));
        document.querySelectorAll('.dropdown-toggle').forEach(t => t.setAttribute('aria-expanded', 'false'));
    }
    // ★修正：不要になったコースメニュー操作を削除（汎用処理なので変更不要の場合もありますが、念のため確認）
    document.addEventListener('click', closeAllDropdowns);


    // ============================================================
    // 4. サーバー通信処理 (Fetch)
    // ============================================================

    /**
     * データの整形・検証用ヘルパー関数
     * APIから受け取ったデータ（または結合したデータ）を検証して返します。
     */
    function processAndMergeLessonData(data) {
        // データがnull/undefinedの場合
        if (!data) return {};
        
        // PHPから空の配列 [] が返ってきた場合の対応
        if (Array.isArray(data)) {
            return {};
        }
        
        // オブジェクトとして返ってきた場合はそのまま利用
        if (typeof data === 'object') {
            return data;
        }
        
        return {};
    }

    /**
     * サーバーからカレンダーデータを取得
     */
    async function fetchLessonData(year, month) {
        if (!currentSubject) return;

        const courseIds = currentSubject.courses.map(c => c.id);
        const params = new URLSearchParams();
        params.append('action', 'fetch_calendar');
        params.append('year', year);
        params.append('month', month);
        params.append('subject_id', currentSubject.subject_id);
        courseIds.forEach(id => params.append('course_ids[]', id));

        try {
            const response = await fetch(`${API_BASE_URL}?${params.toString()}`);
            if (!response.ok) throw new Error('Network response was not ok');
            
            let data = await response.json();
            
            if (Array.isArray(data) && data.length === 0) {
                data = {};
            }

            lessonData = processAndMergeLessonData(data); // マージ処理

            renderCalendar(year, month);
            // refreshSidebar(); // ★削除：カレンダー操作時にサイドバーは更新しない

        } catch (error) {
            console.error('Fetch Error:', error);
            lessonData = {};
            renderCalendar(year, month);
        }
    }

    /**
     * サイドバー用のデータを取得
     * 「今月」と「来月」の2ヶ月分を取得して結合することで月またぎに対応します
     */
    async function fetchSidebarData() {
        if (!currentSubject) return;

        const now = new Date();
        let loopY = now.getFullYear();
        let loopM = now.getMonth() + 1;

         const courseIds = currentSubject.courses.map(c => c.id);

        // API呼び出し用ヘルパー
        const fetchMonth = async (y, m) => {
            const params = new URLSearchParams();
            params.append('action', 'fetch_calendar');
            params.append('year', y);
            params.append('month', m);
            params.append('subject_id', currentSubject.subject_id);
            courseIds.forEach(id => params.append('course_ids[]', id));
    
            try {
                const res = await fetch(`${API_BASE_URL}?${params.toString()}`);
                if(!res.ok) return {};
                const d = await res.json();
                return (Array.isArray(d) && d.length === 0) ? {} : d;
            } catch (e) {
                return {};
            }
        };

        // 取得する月数の設定（ここでは向こう12ヶ月分としています）
        const monthsToFetch = 12; 
        const promises = [];

        for (let i = 0; i < monthsToFetch; i++) {
            promises.push(fetchMonth(loopY, loopM));
        
            // 次の月へ
            loopM++;
            if (loopM > 12) {
                loopM = 1;
                loopY++;
            }
        }

        try {
            // 並列取得実行
            const results = await Promise.all(promises);

            // 全ての結果を1つのオブジェクトに結合
            let rawMerged = {};
            results.forEach(res => {
                rawMerged = { ...rawMerged, ...res };
            });

            sidebarData = processAndMergeLessonData(rawMerged);
            refreshSidebar(); 

        } catch (error) {
            console.error('Sidebar Fetch Error:', error);
            sidebarData = {};
            refreshSidebar();
        }
    }

    /**
     * データの保存 (POST)
     */
    async function saveLessonDataToServer(status, statusText) {
        if (!selectedDateKey || !currentSubject) return;

        const lessonVal = document.querySelector('.lesson-details-textarea').value;
        const belongingsVal = document.getElementById('detailsTextarea').value;
        
        const courseIds = currentSubject.courses.map(c => c.id);
        const slotData = selectedSlotKey; // "1限" などの文字列

        const postData = {
            action: 'save',
            date: selectedDateKey,
            subject_id: currentSubject.subject_id,
            course_ids: courseIds,
            slot: slotData,
            content: lessonVal,
            belongings: belongingsVal,
            status: status
        };

        try {
            const response = await fetch(API_BASE_URL, {
                method: 'POST',
                body: JSON.stringify(postData),
                headers: { 'Content-Type': 'application/json' }
            });

            const result = await response.json();
            if (result.success) {
                // 修正: 引数に content(lessonVal) と belongings(belongingsVal) を追加
                updateAllViews(selectedDateKey, slotData, status, statusText, lessonVal, belongingsVal, false);
                
                lessonModal.style.display = 'none';
                alert("一括保存しました");
            } else {
                alert("保存に失敗しました: " + (result.message || "不明なエラー"));
            }
        } catch (error) {
            console.error(error);
            alert("通信エラーが発生しました");
        }
    }

    /**
     * データの削除 (POST)
     */
    async function deleteLessonDataOnServer() {
        if (!selectedDateKey || !currentSubject) return;
        
        const slotData = selectedSlotKey; // "1限" などの文字列
        const courseIds = currentSubject.courses.map(c => c.id);

        const postData = {
            action: 'delete',
            date: selectedDateKey,
            slot: slotData,
            subject_id: currentSubject.subject_id,
            course_ids: courseIds
        };

        try {
            const response = await fetch(API_BASE_URL, {
                method: 'POST',
                body: JSON.stringify(postData),
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await response.json();

            if (result.success) {
                // 修正: 引数を合わせる (content, belongings は空文字、isDelete = true)
                updateAllViews(selectedDateKey, slotData, null, null, "", "", true);
                
                lessonModal.style.display = 'none';
                
                document.querySelector('.lesson-details-textarea').value = '';
                document.getElementById('detailsTextarea').value = '';
                if (itemInput) itemInput.value = '';
                
                alert("一括削除しました");
            } else {
                alert("削除できませんでした");
            }
        } catch (e) {
            console.error(e);
            alert("通信エラー");
        }
    }

    // ============================================================
    // 5. カレンダー描画 & サイドバー更新
    // ============================================================

    function refreshSidebar() {
		const nextList = document.getElementById('nextLessonList');
		const futureList = document.getElementById('futureLessonList');
		
		if (!nextList || !futureList) return;

		nextList.innerHTML = '';
		futureList.innerHTML = '';

		// 今日の日付 (時分秒リセット)
		const today = new Date();
		today.setHours(0,0,0,0);

		// 日付キーを昇順ソート
		const sortedKeys = Object.keys(sidebarData).sort();

		let hasNextLesson = false;

		// アイテム生成ヘルパー
		const createItem = (dateKey, slot) => {
			const wrapper = document.createElement('div');
			wrapper.className = 'lesson-status-wrapper';
			// ★スタイル調整：CSSクラスで制御しても良いですが、念のためJSでも指定
			wrapper.style.display = 'flex';
			wrapper.style.alignItems = 'center';
			wrapper.style.justifyContent = 'space-between';
			wrapper.style.marginBottom = '8px';
			wrapper.style.cursor = 'pointer';
			
			const targetDate = new Date(dateKey);
			const m = targetDate.getMonth() + 1;
			const d = targetDate.getDate();
			const w = ['日','月','火','水','木','金','土'][targetDate.getDay()];

			// 左側：日付と時限
			const dateDisplay = document.createElement('div');
			dateDisplay.className = 'lesson-date-item';
			dateDisplay.textContent = `${m}月${d}日(${w}) ${slot.slot}`;
			wrapper.appendChild(dateDisplay);

			// 右側：ステータスボタン
			const statusBtn = document.createElement('button');
			statusBtn.className = `status-button ${slot.status}`;
			statusBtn.textContent = slot.statusText; // サイドバーはシンプル表示でOK
			statusBtn.style.minWidth = '80px'; 
			
			wrapper.onclick = (e) => {
				e.stopPropagation();
				openModalWithSlot(dateKey, slot);
			};
			statusBtn.onclick = (e) => {
				e.stopPropagation();
				openModalWithSlot(dateKey, slot);
			};

			wrapper.appendChild(statusBtn);
			return wrapper;
		};

		// ループ処理
		for (const key of sortedKeys) {
			const targetDate = new Date(key);
			// 過去の日付は無視
			if (targetDate < today) continue;

			const dayData = sidebarData[key];
			if (!dayData || !dayData.slots || dayData.slots.length === 0) continue;

			// 各日のスロットを処理
			dayData.slots.forEach(slot => {
				const item = createItem(key, slot);

				if (!hasNextLesson) {
					// 最初に見つかった未来の授業 ＝ 次回の授業
					nextList.appendChild(item);
					hasNextLesson = true;
				} else {
					// それ以降 ＝ 今後の授業
					futureList.appendChild(item);
				}
			});
		}

		// データがない場合の表示
		if (nextList.children.length === 0) {
			nextList.innerHTML = '<div class=\"no-data-msg\">予定なし</div>';
		}
		if (futureList.children.length === 0) {
			futureList.innerHTML = '<div class=\"no-data-msg\">予定なし</div>';
		}
	}

    /**
     * ローカルデータの更新と再描画
     * @param {string} dateKey YYYY-MM-DD
     * @param {string} targetSlotKey "1限" など
     * @param {string} status "in-progress" 等
     * @param {string} text "作成済" 等
     * @param {string} content 授業詳細
     * @param {string} belongings 持ち物
     * @param {boolean} isDelete 削除フラグ
     */
function updateAllViews(dateKey, targetSlotKey, status, text, content, belongings, isDelete = false) {
		
		// lessonData(カレンダー用) と sidebarData(サイドバー用) の両方を更新する内部関数
		const updateDataSet = (dataSet) => {
			if (!dataSet) return;

			// まだその日のデータ枠がない場合は作成
			if (!dataSet[dateKey]) {
				dataSet[dateKey] = { slots: [], circle_type: 'blue' };
			}
			const dayData = dataSet[dateKey];
			
			// 対象のスロットを探す
			let targetSlotObj = dayData.slots.find(s => s.slot === targetSlotKey);

			if (isDelete) {
				// 削除処理（物理削除せず「未作成」状態に戻す仕様に合わせています）
				if (targetSlotObj) {
					targetSlotObj.status = "not-created";
					targetSlotObj.statusText = "未作成";
					targetSlotObj.content = "";
					targetSlotObj.belongings = "";
				}
			} else {
				// 保存/更新処理
				if (targetSlotObj) {
					// 既存データの更新
					targetSlotObj.status = status;
					targetSlotObj.statusText = text;
					targetSlotObj.content = content;
					targetSlotObj.belongings = belongings;
				} else {
					// 新規スロット追加（通常ここに来る前に枠はあるはずですが、念のため）
					dayData.slots.push({
						slot: targetSlotKey,
						period: parseInt(targetSlotKey) || 0, // "1限" -> 1
						status: status,
						statusText: text,
						content: content,
						belongings: belongings
					});
					// 時限順にソート
					dayData.slots.sort((a, b) => a.period - b.period);
				}
			}
		};

		// 両方のデータセットを更新
		updateDataSet(lessonData);
		updateDataSet(sidebarData);

		// 両方のビューを再描画
		refreshSidebar();
		renderCalendar(currentYear, currentMonth);
	}

    function renderCalendar(year, month) {
		if (!calendarGrid) return;

		// グリッドをクリア（ヘッダー行以外の動的生成セルのみ削除）
		const oldCells = calendarGrid.querySelectorAll('.date-cell');
		oldCells.forEach(cell => cell.remove());

		// カレンダー計算
		const firstDay = new Date(year, month - 1, 1).getDay(); // 1日の曜日
		const daysInMonth = new Date(year, month, 0).getDate(); // その月の日数
		const prevMonthLastDay = new Date(year, month - 1, 0).getDate(); // 先月の末日

		// ★修正：35(5週)だと、土曜始まりの31日月などで溢れるため42(6週)に変更
		const totalCells = 42;

		for (let i = 0; i < totalCells; i++) {
			let dayNum, isOutOfMonth = false;
			let currentY = year;
			let currentM = month;

			if (i < firstDay) {
				// 先月
				dayNum = prevMonthLastDay - (firstDay - 1 - i);
				isOutOfMonth = true;
				currentM = month - 1;
				if(currentM < 1) { currentM = 12; currentY--; }
			} else if (i >= firstDay + daysInMonth) {
				// 来月
				dayNum = i - (firstDay + daysInMonth) + 1;
				isOutOfMonth = true;
				currentM = month + 1;
				if(currentM > 12) { currentM = 1; currentY++; }
			} else {
				// 今月
				dayNum = i - firstDay + 1;
			}
			
			const cell = createDateCell(dayNum, isOutOfMonth, currentY, currentM);
			
			// 曜日ごとの色分け用クラス
			if (i % 7 === 0) cell.classList.add('is-sunday');
			if (i % 7 === 6) cell.classList.add('is-saturday');
			
			calendarGrid.appendChild(cell);
		}
		
		// ★追加：ヘッダータイトルの更新（西暦を表示）
		if(monthElement) {
			monthElement.textContent = `${year}年 ${month}月`;
		}
	}

    function createDateCell(dayNum, isOutOfMonth, year, month) {
		const cell = document.createElement('div');
		cell.className = 'date-cell';
		if (isOutOfMonth) {
			cell.classList.add('is-out-of-month');
			cell.style.opacity = '0.5';
		}

		// 日付キー生成 (YYYY-MM-DD)
		const mStr = String(month).padStart(2, '0');
		const dStr = String(dayNum).padStart(2, '0');
		const dateKey = `${year}-${mStr}-${dStr}`;

		// 該当日のデータ取得
		const dayData = lessonData[dateKey] || null;

		// --- 日付数字と丸の表示判定 ---
		const dateNumSpan = document.createElement('span');
		dateNumSpan.className = 'date-num';
		dateNumSpan.textContent = dayNum;

		let hasLesson = false;
		// 授業データが存在する場合のみ処理
		if (!isOutOfMonth && dayData && dayData.slots && dayData.slots.length > 0) {
			hasLesson = true;
			cell.classList.add('has-data');
			
			// 丸の色判定（変更がある日等は赤、通常は青）
			if (dayData.circle_type === 'red') {
				dateNumSpan.classList.add('circle-red');
			} else {
				dateNumSpan.classList.add('circle-blue');
			}
		}
		cell.appendChild(dateNumSpan);

		// --- 授業ラベル（ボタン）の生成 ---
		if (hasLesson) {
                const slotsContainer = document.createElement('div');
                slotsContainer.className = 'slots-container';

                dayData.slots.forEach(slot => {
                    const btn = document.createElement('button');
                    
                    // ★変更点1: ボタン自体には色のクラス(not-created等)を付けない
                    // 常に白背景のラッパーとして機能させるため 'status-button' のみにする
                    btn.className = 'status-button'; 
                    
                    // ★変更点2: HTML構造を変更
                    // 左に時限、右にステータスバッジ(ここに色クラスを付与)
                    btn.innerHTML = `
                        <span class="slot-label">${slot.slot}</span>
                        <span class="status-badge ${slot.status}">${slot.statusText}</span>
                    `;
                    
                    // ボタンクリック時のみモーダルを開く
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation(); // 親セルへの伝播を止める
                        openModalWithSlot(dateKey, slot);
                    });

                    slotsContainer.appendChild(btn);
                });
                cell.appendChild(slotsContainer);
            }
		
		// ★要件：セル自体のクリックは何もしない（ボタンのみ有効）
		// ただし、もし「新規作成」などをセルクリックで行いたい場合はここを変更します
		cell.onclick = () => { return false; };

		return cell;
	}

    /**
     * 特定の授業スロットでモーダルを開く
     * @param {string} dateKey YYYY-MM-DD
     * @param {object} slotData slotオブジェクト
     */
    function openModalWithSlot(dateKey, slotData) {
        selectedDateKey = dateKey;
        selectedSlotKey = slotData.slot;
        
        // DOM要素
        const lessonDetailsText = document.querySelector('.lesson-details-textarea');
        const belongingsText = document.getElementById('detailsTextarea');
        
        // 値をセット
        lessonDetailsText.value = slotData.content || "";
        belongingsText.value = slotData.belongings || "";
        
        // タイトル更新
        const [y, m, d] = dateKey.split('-').map(Number);
        const dateObj = new Date(y, m - 1, d);
        const w = ['日','月','火','水','木','金','土'][dateObj.getDay()];
        
        const modalTitle = document.querySelector('.modal-date');
        if(modalTitle) {
            modalTitle.textContent = `${m}月${d}日(${w}) ${slotData.slot}`;
        }

        const modal = document.getElementById('lessonModal');
        if(modal) modal.style.display = 'flex';
    }

    function openModalWithDate(dateKey) {
        selectedDateKey = dateKey;
        const [year, month, dayNum] = dateKey.split('-').map(Number);
        const data = lessonData[dateKey] || {};

        // 入力欄セット
        const lessonDetailsText = document.querySelector('.lesson-details-textarea');
        const belongingsText = document.getElementById('detailsTextarea');
        
        lessonDetailsText.value = data.content || "";
        belongingsText.value = data.belongings || "";
        itemInput.value = ''; // テンプレート入力はクリア

        // 日付タイトル更新
        const dateObj = new Date(year, month - 1, dayNum);
        const dayOfWeek = dateObj.toLocaleDateString('ja-JP', { weekday: 'short' });
        const modalTitle = document.querySelector('.modal-date');
        if (modalTitle) modalTitle.textContent = `${month}月${dayNum}日(${dayOfWeek})`;

        lessonModal.style.display = 'flex';
    }


    // ============================================================
    // 6. 持ち物管理・モーダル操作（既存UIロジック維持）
    // ============================================================

    // 完了・保存ボタン
    completeButton.addEventListener('click', () => {
        if(!validateInput()) return;
        saveLessonDataToServer("in-progress", "作成済み"); // CSSクラス名と表示名
    });

    tempSaveButton.addEventListener('click', () => {
        saveLessonDataToServer("creating", "作成中");
    });

    deleteButton.addEventListener('click', () => {
        const lessonVal = document.querySelector('.lesson-details-textarea').value.trim();
        const detailsVal = document.getElementById('detailsTextarea').value.trim();
        const hasContent = lessonVal !== "" || detailsVal !== "";
        
        // 何も入力がない場合は確認なしで閉じる、ある場合は確認
        if (!hasContent || window.confirm("この日の授業詳細を削除してもよろしいですか？")) {
            deleteLessonDataOnServer();
        }
    });

    function validateInput() {
        const lessonVal = document.querySelector('.lesson-details-textarea').value.trim();
        const belongingsVal = document.getElementById('detailsTextarea').value.trim();
        
        document.querySelectorAll('.required-alert').forEach(a => a.remove());

        if (lessonVal === "" || belongingsVal === "") {
            const msg = document.createElement('span');
            msg.className = 'required-alert';
            msg.textContent = ' ※必須入力です';
            msg.style.color = '#ff0000';
            msg.style.fontSize = '12px';
            msg.style.marginLeft = '10px';

            if (lessonVal === "") document.querySelector('.form-title').appendChild(msg.cloneNode(true));
            if (belongingsVal === "") {
                const titles = document.querySelectorAll('.form-title');
                if (titles[1]) titles[1].appendChild(msg.cloneNode(true));
            }
            return false;
        }
        return true;
    }

    // 持ち物タグ UI
    deleteIcon.addEventListener('click', function() {
        isDeleteMode = !isDeleteMode;
        this.classList.toggle('is-active', isDeleteMode);
        addBtn.textContent = isDeleteMode ? '削除' : '追加';
        addBtn.classList.toggle('is-delete-mode', isDeleteMode);
        
        document.querySelectorAll('.item-tag-container').forEach(tag => tag.classList.remove('is-selected'));
        selectedItemsForDelete = [];
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
                const idx = selectedItemsForDelete.indexOf(itemName);
                if (idx > -1) selectedItemsForDelete.splice(idx, 1);
            }
            itemInput.value = selectedItemsForDelete.join('、');
        } else {
            // テキストエリアに追加/削除
            const detailsTextarea = document.getElementById('detailsTextarea');
            let txt = detailsTextarea.value.trim();
            let items = txt === "" ? [] : txt.split('、');
            const idx = items.indexOf(itemName);
            if (idx === -1) items.push(itemName);
            else items.splice(idx, 1);
            detailsTextarea.value = items.join('、');
        }
    });

    addBtn.addEventListener('click', function() {
        if (isDeleteMode) {
            document.querySelectorAll('.item-tag-container.is-selected').forEach(t => t.remove());
            itemInput.value = '';
            selectedItemsForDelete = [];
        } else {
            const val = itemInput.value.trim();
            if (val !== "") {
                const items = val.split('、');
                items.forEach(item => {
                    if (item.trim() !== "") {
                        const div = document.createElement('div');
                        div.className = 'item-tag-container';
                        div.innerHTML = `<span class="item-tag">${item.trim()}</span>`;
                        itemTagsContainer.appendChild(div);
                    }
                });
                itemInput.value = '';
            }
        }
    });

    // カレンダー月移動
    if (leftArrowButton) leftArrowButton.addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 1) { currentMonth = 12; currentYear--; }
        fetchLessonData(currentYear, currentMonth);
    });
    if (rightArrowButton) rightArrowButton.addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 12) { currentMonth = 1; currentYear++; }
        fetchLessonData(currentYear, currentMonth);
    });

    // 文字数カウント
    const lessonTextArea = document.querySelector('.lesson-details-textarea');
    const charCountDisplay = document.querySelector('.char-count');
    if (lessonTextArea && charCountDisplay) {
        lessonTextArea.addEventListener('input', () => {
            charCountDisplay.textContent = `${lessonTextArea.value.length}/200文字`;
        });
    }
});

// モーダル外クリック
const lessonModal = document.getElementById('lessonModal');
lessonModal.addEventListener('click', (e) => {
    if (e.target === lessonModal) lessonModal.style.display = 'none';
});