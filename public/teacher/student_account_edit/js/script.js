
// ----------------------------------------------------------------------
// ページのHTMLが完全に読み込まれた後に実行されるメインロジック
// ----------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {

    // ----------------------------------------------------------------------
    // ユーティリティ: カスタムアラート / モーダル
    // alert() の代替として、エラーメッセージを表示するためのシンプルなカスタムモーダルが必要です。
    // HTML内にID "customAlertModal" と "customAlertMessage" を持つ要素が必要です。
    // ----------------------------------------------------------------------
    const customAlertModal = document.getElementById('customAlertModal');
    const customAlertMessage = document.getElementById('customAlertMessage');
    const customAlertClose = document.getElementById('customAlertClose');

    const showCustomAlert = (message) => {
        if (customAlertModal && customAlertMessage) {
            customAlertMessage.textContent = message;
            customAlertModal.style.display = 'flex';
        } else {
            // カスタムアラートが未定義の場合のフォールバック (Canvasでは避けるべき)
            console.error('カスタムアラートのHTML要素が見つかりません。');
        }
    };
    
    if (customAlertClose && customAlertModal) {
        customAlertClose.addEventListener('click', () => {
            customAlertModal.style.display = 'none';
        });
        customAlertModal.addEventListener('click', (e) => {
            if (e.target === customAlertModal) {
                customAlertModal.style.display = 'none';
            }
        });
    }

    // ----------------------------------------------------------------------
    // 1. ドロップダウンメニューの制御 (サイドバー & テーブル内)
    // ----------------------------------------------------------------------

    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    const dropdownMenus = document.querySelectorAll('.dropdown-menu');
    const tableCourseInputs = document.querySelectorAll('.course-display[data-dropdown-for]'); 

    // ★ 修正: トグルボタンの要素をここで定義
    const courseToggle = document.getElementById('courseDropdownToggle');
    const yearToggle = document.getElementById('yearDropdownToggle');

    
    let currentOpenToggle = null;
    let currentOpenMenu = null;
    let currentTableInput = null; 

    /**
     * すべてのドロップダウンを閉じる関数
     */
    const closeAllDropdowns = () => {
        // 1. サイドバーのメニューを閉じる
        document.querySelectorAll('.dropdown-toggle[aria-expanded="true"]').forEach(openToggle => {
            openToggle.setAttribute('aria-expanded', 'false');
            const openMenu = openToggle.closest('.nav-item')?.querySelector('.dropdown-menu');
            if (openMenu) openMenu.classList.remove('is-open');
        });

        // 2. テーブルのコースドロップダウンを閉じる
        if (currentTableInput) {
            currentTableInput.classList.remove('is-open-course-dropdown');
            
            const menuId = currentTableInput.getAttribute('data-dropdown-for');
            const menu = document.getElementById(menuId);
            if (menu) {
                menu.classList.remove('is-open');
                // 位置指定をリセット
                menu.style.left = '';
                menu.style.top = '';
                menu.style.position = ''; 
            }
        }
        
        // 追跡変数をリセット
        currentOpenToggle = null;
        currentOpenMenu = null;
        currentTableInput = null; 
    };

    // --- 1-1. サイドバードロップダウンの開閉制御 ---
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', (event) => {
            const navItem = toggle.closest('.nav-item');
            const menu = navItem ? navItem.querySelector('.dropdown-menu') : null;

            if (menu) {
                const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                
                closeAllDropdowns(); // まず全て閉じる

                if (!isExpanded) { 
                    toggle.setAttribute('aria-expanded', 'true');
                    menu.classList.add('is-open');

                    // 【メニューの位置設定】
                    const rect = toggle.getBoundingClientRect();
                    menu.style.left = `${rect.right + 5}px`;
                    menu.style.top = `${rect.top + rect.height}px`;
                    menu.style.position = 'fixed'; 

                    currentOpenToggle = toggle;
                    currentOpenMenu = menu;
                }
            }
            event.stopPropagation(); 
        });
    });

    // --- 1-2. テーブルのコースドロップダウン開閉制御 ---
    // 初期ロード時に存在する要素にイベントを設定する関数
    const setupInitialCourseDropdowns = () => {
        tableCourseInputs.forEach(setupCourseDropdown);
    };

    /**
     * コースドロップダウンにイベントリスナーを設定するヘルパー関数
     * @param {HTMLElement} input - コース表示要素 (.course-display)
     */
    const setupCourseDropdown = (input) => {
        input.addEventListener('click', (event) => {
            const menuId = input.getAttribute('data-dropdown-for');
            const menu = document.getElementById(menuId);

            if (menu) {
                const isOpened = input.classList.contains('is-open-course-dropdown');
                
                closeAllDropdowns(); // まず全て閉じる

                if (!isOpened) { 
                    input.classList.add('is-open-course-dropdown'); 
                    menu.classList.add('is-open'); 
                    menu.style.position = 'fixed'; 

                    const rect = input.getBoundingClientRect();
                    
                    // テーブルの入力フィールドの右側 + 5px、入力フィールドの下に配置
                    menu.style.left = `${rect.right + 5}px`;
                    menu.style.top = `${rect.top + rect.height}px`;
                    
                    currentTableInput = input; // 現在のテーブル入力を設定
                }
            }
            event.stopPropagation(); 
        });
    };

    setupInitialCourseDropdowns(); // ページロード時に既存の要素に設定


    // ----------------------------------------------------------------------
    // ユーティリティ: 非同期通信でコースIDをPHPに送信し、生徒リストを更新する
    // ----------------------------------------------------------------------

    // ユーティリティ: 非同期通信でコースIDと年度をPHPに送信し、生徒リストを更新する
    // (ここではリダイレクト処理として実装)
    const redirectToStudentAccountPage = (courseId, year) => {
        if (!courseId || !year) {
            console.error('コースIDまたは年度が未定義です。');
            return;
        }

        // サーバーサイドの処理ファイル
        const url = '../../../app/teacher/student_account_edit_backend/student_course.php'; 
    
        // コースIDと年度をURLパラメータとして付与してリダイレクト
        window.location.href = `${url}?course_id=${encodeURIComponent(courseId)}&current_year=${encodeURIComponent(year)}`;
    };


    // --- 2. メニュー項目の選択処理 ---
    dropdownMenus.forEach(menu => {
        const links = menu.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                //e.stopPropagation();

                const selectedValue = e.target.textContent;
            
                // ★ 修正: トグルボタンから現在の値を取得
                // ※ トグルボタンのdata属性をHTML/PHP側で設定していることが前提
                let finalCourseId = courseToggle ? courseToggle.getAttribute('data-current-course') : null;
                let finalYear = yearToggle ? yearToggle.getAttribute('data-current-year') : null;
                let shouldRedirect = false; // ページ遷移フラグ

                // A. サイドバーのドロップダウンだった場合 (sidebarのトグルボタンがクリックされて開いたメニュー)
                if (currentOpenToggle) {
                    const currentValueSpan = currentOpenToggle.querySelector('.current-value');
                    if (currentValueSpan) {
                        currentValueSpan.textContent = selectedValue; // 選択された値を表示に反映
                    }
            
                    // 1. コースドロップダウンが選択された場合
                    if (currentOpenToggle.id === 'courseDropdownToggle') {
                        const selectedCourseId = e.target.getAttribute('data-current-course');
                        const selectedYear = e.target.getAttribute('data-current-year');
                        if (selectedCourseId) {
                            finalCourseId = selectedCourseId;
                            finalYear = selectedYear;
                            currentOpenToggle.setAttribute('data-current-course', selectedCourseId); // 新しい値をボタンに保存
                            // ★ 修正: courseToggle ではなく currentOpenToggle を使用
                            shouldRedirect = true; 
                        }
                    } 
                    // 2. 年度ドロップダウンが選択された場合
                    else if (currentOpenToggle.id === 'yearDropdownToggle') {
                        const selectedYear = e.target.getAttribute('data-current-year');
                        const selectedCourseId = e.target.getAttribute('data-current-course');
                        if (selectedYear) {
                            finalYear = selectedYear;
                            finalCourseId = selectedCourseId;
                            currentOpenToggle.setAttribute('data-current-year', selectedYear); // 新しい値をボタンに保存
                            // ★ 修正: yearToggle ではなく currentOpenToggle を使用
                            shouldRedirect = true; 
                        }
                    }
                }
                // B. テーブルのコースドロップダウンだった場合 
                else if (currentTableInput) {
                    const selectedCourseId = e.target.getAttribute('data-current-course');
                    currentTableInput.textContent = selectedValue;
                    // data属性を更新 (テーブル行のデータ送信時に使用)
                    currentTableInput.setAttribute('data-selected-course', selectedValue);

                    const hiddenInput = currentTableInput.closest('.column-course').querySelector('.input-course-hidden');
                    if (hiddenInput && selectedCourseId) {
                        hiddenInput.value = selectedCourseId; 
                    }
                }
            
                closeAllDropdowns(); // ドロップダウンを閉じる

                // 最後にリダイレクト（ページ全体を再読み込み）を実行
                if (shouldRedirect) {
                    // コース選択、年度選択のどちらの場合も、現在選択されている両方の値でリダイレクト
                    redirectToStudentAccountPage(finalCourseId, finalYear);
                }
            });
        });
    });

    // --- 3. どこかをクリックしたらメニューを閉じる ---
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.has-dropdown') && !event.target.closest('.course-display')) {
            closeAllDropdowns();
        }
    });

    // ----------------------------------------------------------------------
    // 4. student_delete.html 固有の削除モーダル・チェックボックス処理
    // ----------------------------------------------------------------------

    if (document.body.id === 'student_delete') {
        const modal = document.getElementById('deleteModal');
        const openButton = document.getElementById('deleteActionButton'); 
        const cancelButton = document.getElementById('cancelDeleteButton');
        const studentListContainer = document.getElementById('selectedStudentList');
        const deleteCountDisplay = modal ? modal.querySelector('.modal-body p') : null;
        
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        // rowCheckboxes を動的に再取得する必要がある場合があるが、ここでは初期のものを利用
        const rowCheckboxes = document.querySelectorAll('.row-checkbox');
        const confirmDeleteButton = document.getElementById('confirmDeleteButton');

        // 削除ボタン (deleteActionButton) をクリックした時の処理
        if (openButton && modal && deleteCountDisplay) {
            openButton.addEventListener('click', () => {
                const selectedStudents = [];
                
                // チェックされた行のデータを取得
                document.querySelectorAll('.row-checkbox').forEach(checkbox => { // 常に最新のチェックボックスを取得
                    if (checkbox.checked) {
                        const row = checkbox.closest('.table-row');
                        
                        const id = checkbox.getAttribute('data-student-id'); 
                        const name = checkbox.getAttribute('data-student-name');
                        
                        if (id && name) { 
                            selectedStudents.push({ id, name });
                        } else if (row) {
                            // data属性がない場合のフォールバック処理を改善
                            const idInput = row.querySelector('.column-student-id input');
                            const nameInput = row.querySelector('.column-name input');
                            const idValue = idInput ? idInput.value : 'ID不明';
                            const nameValue = nameInput ? nameInput.value : '氏名不明';
                            selectedStudents.push({ id: idValue, name: nameValue });
                        }
                    }
                });

                // 選択された学生がいない場合はカスタムアラートを表示して中断 (alertを置き換え)
                if (selectedStudents.length === 0) {
                    showCustomAlert('削除するアカウントを選択してください。');
                    return;
                }
                
                studentListContainer.innerHTML = '';
                
                // 選択された学生の情報をモーダルに追加
                selectedStudents.forEach(student => {
                    const item = document.createElement('div');
                    item.classList.add('deleted-item', 'text-sm', 'text-gray-700', 'truncate');
                    item.textContent = `${student.id}: ${student.name}`;
                    studentListContainer.appendChild(item);
                });
                
                // 削除件数の表示を更新
                deleteCountDisplay.innerHTML = `以下の**${selectedStudents.length}件**のアカウントを削除してもよろしいですか？`;

                // モーダルを表示
                modal.style.display = 'flex';
            });
        }

        // キャンセルボタンをクリックした時の処理 (モーダルを消す)
        if (cancelButton && modal) {
            cancelButton.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }

        // モーダルのオーバーレイをクリックした時の処理 (モーダルを消す)
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // --- チェックボックスと行のハイライト処理の再定義（クロージャーを利用）---
        const updateRowHighlight = (checkbox) => {
            const row = checkbox.closest('.table-row');
            if (row) {
                if (checkbox.checked) {
                    row.classList.add('is-checked');
                } else {
                    row.classList.remove('is-checked');
                }
            }
        };

        const updateSelectAllState = () => {
            if (selectAllCheckbox) {
                const currentCheckboxes = document.querySelectorAll('.row-checkbox');
                const allChecked = Array.from(currentCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked && currentCheckboxes.length > 0;
            }
        };

        // 全選択/全解除の機能
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', () => {
                document.querySelectorAll('.row-checkbox').forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                    updateRowHighlight(checkbox);
                });
            });
        }
        
        // 個別チェックボックスのイベントリスナーを全て再設定
        // 新しい行が追加される可能性があるため、document.querySelectorAllを毎回使用する
        document.querySelectorAll('.row-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                updateRowHighlight(checkbox);
                updateSelectAllState();
            });
        });


        // 削除確認ボタン（モーダル内の「削除」）が押された時の処理
        if (confirmDeleteButton && modal) {
            confirmDeleteButton.addEventListener('click', () => {
                console.log('--- 削除を実行しました。 (※実際にはこの後にサーバー処理が必要です) ---');
                // 実際にはFetch APIでサーバーにリクエストを送る
                
                // 成功時の処理: モーダルを閉じる
                modal.style.display = 'none';
                showCustomAlert('削除リクエストをサーバーに送信しました。');
            });
        }
    } // end student_delete.html 固有の処理

    // ----------------------------------------------------------------------
    // 5. student_addition.html 固有のアカウント追加処理
    // ----------------------------------------------------------------------
    
    if (document.body.id === 'student_addition') {
        const tableContainer = document.querySelector('.account-table-container');

        // 最新の学生番号を取得し、次の番号を推測する関数 (ロジックは維持)
        const getNextStudentId = () => {
            const studentIdInputs = tableContainer.querySelectorAll('.column-student-id input');
            let maxId = 0;
            
            studentIdInputs.forEach(input => {
                const id = parseInt(input.value, 10);
                if (!isNaN(id) && id > maxId) {
                    maxId = id;
                }
            });
            // 既存のHTMLが20015で終わっていることを想定
            return maxId > 0 ? (maxId + 1).toString() : '20016';
        };

        // ---------------------------------
        // 5-1. 単一行追加ボタン (.add-button) のロジック
        // ---------------------------------
        const addButton = document.querySelector('.button-group .add-button:first-child'); 
        
        if (addButton && tableContainer) {
            const newRowTemplate = `
                <div class="table-row">
                    <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="" data-student-name=""></div> 
                    <div class="column-student-id"><input type="text" value="" name="student_id[]" class="input-student-id"></div> 
                    <div class="column-name"><input type="text" value="" name="student_name[]" placeholder="氏名を入力" class="input-student-name"></div> 
                    <div class="column-course">
                        <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コースを選択</span>
                        <input type="hidden" name="course[]" class="input-course-hidden" value="コースを選択">
                    </div>
                </div>
            `;
            
            addButton.addEventListener('click', () => {
                const newRow = document.createElement('div');
                newRow.innerHTML = newRowTemplate.trim();
                const newRowElement = newRow.firstChild;
                
                const nextId = getNextStudentId();
                const studentIdInput = newRowElement.querySelector('.column-student-id input');
                
                if (studentIdInput) {
                    studentIdInput.value = nextId; 
                }
                
                tableContainer.appendChild(newRowElement);
                
                // 追加された行のコース表示要素にイベントリスナーを再設定
                const newCourseInput = newRowElement.querySelector('.course-display[data-dropdown-for]');
                if (newCourseInput) {
                    setupCourseDropdown(newCourseInput);
                }

                tableContainer.scrollTop = tableContainer.scrollHeight;
            });
        }
        
        // ---------------------------------
        // 5-2. 複数人数追加モーダルのロジック
        // ---------------------------------
        const addCountButton = document.querySelector('.button-group .add-button:last-child'); // 追加人数入力ボタン
        const modal = document.getElementById('addCountModal'); 
        const cancelButton = document.getElementById('cancelAddCount'); 
        const confirmButton = document.getElementById('confirmAddCount'); 
        const countInput = document.getElementById('studentCountInput'); 

        // 2. モーダル表示ロジック
        if (addCountButton && modal) {
            addCountButton.addEventListener('click', () => {
                modal.style.display = 'flex'; // is-open-modal クラスの代わりに style.display を使用
                countInput.focus(); 
            });
        }

        // 3. モーダル非表示ロジック
        const closeModal = () => {
            if (modal) modal.style.display = 'none';
        };

        if (cancelButton) {
            cancelButton.addEventListener('click', closeModal);
        }

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target.id === 'addCountModal') { 
                    closeModal();
                }
            });
        }

        // 4. 追加ボタンのロジック (行の追加を実装)
        if (confirmButton && tableContainer) {
            confirmButton.addEventListener('click', () => {
                const count = parseInt(countInput.value, 10);
                
                if (isNaN(count) || count < 1 || count > 100) { // 上限を設定
                    showCustomAlert('有効な人数（1～100）を入力してください。');
                    return;
                }

                let nextId = parseInt(getNextStudentId(), 10);

                for(let i = 0; i < count; i++) {
                    const newRow = document.createElement('div');
                    const studentId = (nextId + i).toString();
                    
                    const rowHTML = `
                        <div class="table-row">
                            <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="${studentId}" data-student-name=""></div> 
                            <div class="column-student-id"><input type="text" value="${studentId}" name="student_id[]" class="input-student-id"></div> 
                            <div class="column-name"><input type="text" value="" name="student_name[]" placeholder="氏名を入力" class="input-student-name"></div> 
                            <div class="column-course">
                                <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コースを選択</span>
                                <input type="hidden" name="course[]" class="input-course-hidden" value="コースを選択">
                            </div>
                        </div>
                    `;
                    newRow.innerHTML = rowHTML.trim();
                    const newRowElement = newRow.firstChild;
                    
                    tableContainer.appendChild(newRowElement);
                    
                    const newCourseInput = newRowElement.querySelector('.course-display[data-dropdown-for]');
                    if (newCourseInput) {
                        setupCourseDropdown(newCourseInput);
                    }
                }
                
                showCustomAlert(`${count} 名の空のアカウント行を追加しました。`);
                tableContainer.scrollTop = tableContainer.scrollHeight; // スクロール
                closeModal();
            });
        }
    } // end student_addition.html 固有の処理
});
