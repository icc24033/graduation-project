// ----------------------------------------------------------------------
// ページのHTMLが完全に読み込まれた後に実行されるメインロジック
// ----------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {

    // ----------------------------------------------------------------------
    // ユーティリティ: カスタムアラート / モーダル
    // ----------------------------------------------------------------------
    const customAlertModal = document.getElementById('customAlertModal');
    const customAlertMessage = document.getElementById('customAlertMessage');
    const customAlertClose = document.getElementById('customAlertClose');

    const showCustomAlert = (message) => {
        if (customAlertModal && customAlertMessage) {
            customAlertMessage.textContent = message;
            customAlertModal.style.display = 'flex';
        } else {
            // カスタムアラートが未定義の場合のフォールバック
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
    const tableCourseInputs = document.querySelectorAll('.course-display[data-dropdown-for]:not([data-grade-display])'); 
    
    // tableGradeInputs の再定義: data-grade-display を持つものを選択
    const tableGradeInputs = document.querySelectorAll('.course-display[data-grade-display]'); 

    const courseToggle = document.getElementById('courseDropdownToggle');
    const yearToggle = document.getElementById('yearDropdownToggle');

    
    let currentOpenToggle = null;
    let currentTableInput = null; // コース用
    let currentGradeInput = null; // 学年用
    
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
                menu.style.left = '';
                menu.style.top = '';
                menu.style.position = ''; 
            }
        }
        // 3. テーブルの学年ドロップダウンを閉じる
        if (currentGradeInput) {
            currentGradeInput.classList.remove('is-open-course-dropdown');
            
            const menuId = currentGradeInput.getAttribute('data-dropdown-for'); 
            const menu = document.getElementById(menuId);
            if (menu) {
                menu.classList.remove('is-open');
                menu.style.left = '';
                menu.style.top = '';
                menu.style.position = ''; 
            }
        }
        
        // 追跡変数をリセット
        currentOpenToggle = null;
        currentTableInput = null; 
        currentGradeInput = null; 
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
                }
            }
            event.stopPropagation(); 
        });
    });

    // --- 1-2. テーブルのコースドロップダウン開閉制御 ---
    const setupInitialCourseDropdowns = () => {
        tableCourseInputs.forEach(setupCourseDropdown);
    };

    const setupCourseDropdown = (input) => {
        input.addEventListener('click', (event) => {
            const menuId = input.getAttribute('data-dropdown-for'); 
            const menu = document.getElementById(menuId);

            if (menu) {
                const isOpened = input.classList.contains('is-open-course-dropdown');
                
                closeAllDropdowns(); 

                if (!isOpened) { 
                    input.classList.add('is-open-course-dropdown'); 
                    menu.classList.add('is-open'); 
                    menu.style.position = 'fixed'; 

                    const rect = input.getBoundingClientRect();
                    
                    menu.style.left = `${rect.right + 5}px`;
                    menu.style.top = `${rect.top + rect.height}px`;
                    
                    currentTableInput = input; // コースドロップダウンなので currentTableInput を設定
                }
            }
            event.stopPropagation(); 
        });
    };

    // --- 1-3. テーブルの学年ドロップダウン開閉制御 ---
    const setupGradeDropdown = (input) => {
        input.addEventListener('click', (event) => {
            const menuId = input.getAttribute('data-dropdown-for'); // 'gradeDropdownMenu'
            const menu = document.getElementById(menuId);

            if (menu) {
                const isOpened = input.classList.contains('is-open-course-dropdown');
                
                closeAllDropdowns(); 

                if (!isOpened) { 
                    input.classList.add('is-open-course-dropdown'); 
                    menu.classList.add('is-open'); 
                    menu.style.position = 'fixed'; 

                    const rect = input.getBoundingClientRect();
                    
                    menu.style.left = `${rect.right + 5}px`;
                    menu.style.top = `${rect.top + rect.height}px`;
                    
                    currentGradeInput = input; // 学年ドロップダウンなので currentGradeInput を設定
                }
            }
            event.stopPropagation(); 
        });
    };

    // 初期ロード時に存在する要素にイベントを設定
    const setupInitialGradeDropdowns = () => {
        tableGradeInputs.forEach(setupGradeDropdown);
    };

    setupInitialCourseDropdowns(); 
    setupInitialGradeDropdowns(); 

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // ----------------------------------------------------------------------
    // ユーティリティ: 非同期通信でコースIDをPHPに送信し、生徒リストを更新する
    // ----------------------------------------------------------------------

    const redirectToStudentAccountPage = (courseId, year, page) => {
        if (!courseId || !year || !page) {
            console.error('コースIDまたは年度が未定義です。');
            return;
        }

        const baseUrl = '../../../app/teacher/student_account_edit_backend/';
        let url = '';

        if (page === 'student_edit_course') {
            url = '../controls/student_account_course_control.php';
        }
        else if (page === 'student_delete') {
            url = '../controls/student_account_delete_control.php';
        }
        else if (page === 'student_grade_transfer') {
            url = '../controls/studnet_account_transfer_control.php'; 
        }
        else if (page === 'student_addition') {
            url = '../controls/student_account_edit_control.php';
        }

        window.location.href = `${url}?course_id=${encodeURIComponent(courseId)}&current_year=${encodeURIComponent(year)}`;
    };


    // --- 2. ドロップダウンメニュー項目選択処理 (★ 修正) ---
    dropdownMenus.forEach(menu => {
        const links = menu.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const selectedValue = e.target.textContent;
            
                // トグルボタンから現在の値を取得
                let finalCourseId = courseToggle ? courseToggle.getAttribute('data-current-course') : null;
                let finalYear = yearToggle ? yearToggle.getAttribute('data-current-year') : null;
                let finalPage = null
                let shouldRedirectSide = false; 

                // A. サイドバーのドロップダウンがアクティブな場合 (最優先)
                // クリックされたメニューがサイドバーのトグルで開いたものであり、かつトグルがアクティブであるか
                if (currentOpenToggle && menu.contains(e.target)) {
                    const currentValueSpan = currentOpenToggle.querySelector('.current-value');
                    if (currentValueSpan) {
                        currentValueSpan.textContent = selectedValue; // 選択された値を表示に反映
                    }
                
                    // 1. コースドロップダウンが選択された場合
                    if (currentOpenToggle.id === 'courseDropdownToggle') {
                        const selectedCourseId = e.target.getAttribute('data-current-course');
                        const selectedYear = e.target.getAttribute('data-current-year');
                        const selectedPage = e.target.getAttribute('data-current-page');
                        if (selectedCourseId) {
                            finalCourseId = selectedCourseId;
                            finalYear = selectedYear;
                            finalPage = selectedPage;
                            currentOpenToggle.setAttribute('data-current-course', selectedCourseId); // トグルボタンのdata属性を更新
                            shouldRedirectSide = true; 
                        }
                    } 
                    // 2. 年度ドロップダウンが選択された場合
                    else if (currentOpenToggle.id === 'yearDropdownToggle') {
                        const selectedYear = e.target.getAttribute('data-current-year');
                        const selectedCourseId = e.target.getAttribute('data-current-course');
                        const selectedPage = e.target.getAttribute('data-current-page');
                        if (selectedYear) {
                            finalYear = selectedYear;
                            finalCourseId = selectedCourseId;
                            finalPage = selectedPage;
                            currentOpenToggle.setAttribute('data-current-year', selectedYear); // トグルボタンのdata属性を更新
                            shouldRedirectSide = true; 
                        }
                    }

                    // ★ リダイレクトが必要な場合は、ここで処理を終了し closeAllDropdowns() は実行しない
                    if (shouldRedirectSide) {
                        redirectToStudentAccountPage(finalCourseId, finalYear, finalPage);
                        return; 
                    }
                }
                // B. テーブル内のドロップダウンがアクティブな場合
                // クリックされたメニューが、開いているテーブル入力要素に対応しているか
                else if ((currentTableInput || currentGradeInput) && menu.contains(e.target)) {

                    // アクティブな要素がどちらか特定
                    const activeInput = currentTableInput || currentGradeInput;
                    const menuId = activeInput.getAttribute('data-dropdown-for'); 

                    // 1. コースドロップダウンが選択された場合
                    if (menuId === 'courseDropdownMenu') {
                        const newCourseId = e.target.getAttribute('data-selected-course-center');
                        
                        // 表示用のSPANを更新
                        activeInput.textContent = selectedValue;
                        activeInput.setAttribute('data-selected-course-center', newCourseId); 
                        
                        // 隠し入力フィールド (course-hidden-input) を更新
                        const currentRow = activeInput.closest('.table-row');
                        if (currentRow) {
                            const hiddenInput = currentRow.querySelector('.course-hidden-input');
                            if (hiddenInput) {
                                hiddenInput.value = newCourseId; 
                            }
                        }
                    } 
                    // 2. 学年ドロップダウンが選択された場合
                    else if (menuId === 'gradeDropdownMenu') {
                        const newGradeValue = e.target.getAttribute('data-selected-grade-center');
                        const newGradeDisplay = e.target.textContent;

                        // 表示用のaタグを更新
                        activeInput.textContent = newGradeDisplay;
                        activeInput.setAttribute('data-current-grade-value', newGradeValue);
                        
                        // 隠し入力フィールド (grade-hidden-input) を更新
                        const currentRow = activeInput.closest('.table-row');
                        if (currentRow) {
                            const hiddenInput = currentRow.querySelector('.grade-hidden-input');
                            if (hiddenInput) {
                                hiddenInput.value = newGradeValue; 
                            }
                        }
                    }
                }
                
                // リダイレクトしない場合のみ、ドロップダウンを閉じる
                closeAllDropdowns(); 
            });
        });
    });

    // --- 3. どこかをクリックしたらメニューを閉じる (★ 修正) ---
    document.addEventListener('click', (event) => {
        // クリックされた要素が、
        // 1. サイドバーのトグル (.dropdown-toggle)
        // 2. テーブルのドロップダウン表示要素 (.course-display)
        // 3. ドロップダウンメニューそのもの (.dropdown-menu)
        // のいずれでもない場合にのみ、ドロップダウンを閉じる
        if (
            !event.target.closest('.dropdown-toggle') && 
            !event.target.closest('.course-display') &&
            !event.target.closest('.dropdown-menu')
        ) {
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
        const confirmDeleteButton = document.getElementById('confirmDeleteButton');

        // フォーム要素とhidden inputのコンテナを取得
        const deleteForm = document.getElementById('deleteForm');
        const hiddenInputsContainer = document.getElementById('hiddenInputsContainer');

        // 削除ボタン (deleteActionButton) をクリックした時の処理
        if (openButton && modal && deleteCountDisplay) {
            openButton.addEventListener('click', () => {
                const selectedStudents = [];
                
                // チェックされた行のデータを取得
                document.querySelectorAll('.row-checkbox').forEach(checkbox => { 
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

                // --- ★ ココが重要: 選択された学生IDをhidden inputとしてフォームに動的に追加 ---
                
                // まず、古いhidden inputをすべて削除
                hiddenInputsContainer.innerHTML = ''; 
                
                // 1. 選択された学生IDの配列をhidden inputとしてフォームに動的に追加
                selectedStudents.forEach((student, index) => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    // パラメータ名: 'delete_student_id[]'
                    hiddenInput.name = 'delete_student_id[]'; 
                    hiddenInput.value = student.id;
                    hiddenInputsContainer.appendChild(hiddenInput);
                });

                // 2. ★ 追加: 現在のコースID (current_course_id) をhidden inputとして追加
                const courseToggle = document.getElementById('courseDropdownToggle');
                const currentCourseId = courseToggle ? courseToggle.getAttribute('data-current-course') : '';

                if (currentCourseId) {
                    const hiddenCourseInput = document.createElement('input');
                    hiddenCourseInput.type = 'hidden';
                    hiddenCourseInput.name = 'course_id'; // パラメータ名
                    hiddenCourseInput.value = currentCourseId;
                    hiddenInputsContainer.appendChild(hiddenCourseInput);
                }

                // 3. ★ 追加: 現在の年度 (current_year) をhidden inputとして追加
                const yearToggle = document.getElementById('yearDropdownToggle');
                const currentYear = yearToggle ? yearToggle.getAttribute('data-current-year') : '';

                if (currentYear) {
                    const hiddenYearInput = document.createElement('input');
                    hiddenYearInput.type = 'hidden';
                    hiddenYearInput.name = 'current_year'; // パラメータ名
                    hiddenYearInput.value = currentYear;
                    hiddenInputsContainer.appendChild(hiddenYearInput);
                }


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
        // 5-1. 1行追加ボタンのロジック
        // ---------------------------------
        const addButton = document.querySelector('.button-group .add-button:first-child'); 

        if (addButton && tableContainer) {
            addButton.addEventListener('click', () => {
                // 現在の行数を取得してインデックスを決定
                const currentIndex = tableContainer.querySelectorAll('.table-row').length;
                const nextId = getNextStudentId();
                
                const newRow = document.createElement('div');
                newRow.className = 'table-row';
                
                // student_addition.php の構造に合わせたHTML
                newRow.innerHTML = `
                    <div class="column-check"></div> 
                    <div class="column-student-id">
                        <input type="text" 
                            name="students[${currentIndex}][student_id]" 
                            value="${nextId}" 
                            class="input-student-id">
                    </div> 
                    <div class="column-name">
                        <input type="text" 
                            name="students[${currentIndex}][name]" 
                            placeholder="氏名" 
                            class="input-student-name">
                    </div> 
                    <div class="column-course">
                        <a href="#" class="course-display" 
                        data-course-name-display 
                        data-dropdown-for="courseDropdownMenu"
                        data-selected-course-center="7">  1年1組
                        </a>
                        <input type="hidden" 
                            name="students[${currentIndex}][course_id]" 
                            value="7" 
                            class="course-hidden-input">
                    </div>
                `;
                
                tableContainer.appendChild(newRow);
                
                // 追加された行のドロップダウン設定
                const newCourseLink = newRow.querySelector('.course-display');
                if (newCourseLink) {
                    setupCourseDropdown(newCourseLink);
                }

                tableContainer.scrollTop = tableContainer.scrollHeight;
            });
        }

        // ---------------------------------
        // 5-2. 複数人数追加モーダルのロジック
        // ---------------------------------
        const addCountButton = document.querySelector('.button-group .add-button:last-child');
        const modal = document.getElementById('addCountModal'); 
        const cancelButton = document.getElementById('cancelAddCount'); 
        const confirmButton = document.getElementById('confirmAddCount'); 
        const countInput = document.getElementById('studentCountInput'); 

        if (addCountButton && modal) {
            addCountButton.addEventListener('click', () => {
                modal.style.display = 'flex'; 
                countInput.focus(); 
            });
        }

        const closeModal = () => {
            if (modal) modal.style.display = 'none';
        };

        if (cancelButton) {
            cancelButton.addEventListener('click', closeModal);
        }

        if (confirmButton && tableContainer) {
            confirmButton.addEventListener('click', () => {
                const count = parseInt(countInput.value, 10);
                
                if (isNaN(count) || count < 1 || count > 100) { 
                    showCustomAlert('有効な人数（1～100）を入力してください。');
                    return;
                }

                // 開始インデックスと開始IDを取得
                let startIndex = tableContainer.querySelectorAll('.table-row').length;
                let nextIdBase = parseInt(getNextStudentId(), 10);

                for(let i = 0; i < count; i++) {
                    const currentIndex = startIndex + i;
                    const studentId = (nextIdBase + i).toString();
                    
                    const newRow = document.createElement('div');
                    newRow.className = 'table-row';
                    
                    newRow.innerHTML = `
                        <div class="column-check"></div> 
                        <div class="column-student-id">
                            <input type="text" 
                                name="students[${currentIndex}][student_id]" 
                                value="${studentId}" 
                                class="input-student-id">
                        </div> 
                        <div class="column-name">
                            <input type="text" 
                                name="students[${currentIndex}][name]" 
                                placeholder="氏名" 
                                class="input-student-name">
                        </div> 
                        <div class="column-course">
                            <a href="#" class="course-display" 
                            data-course-name-display 
                            data-dropdown-for="courseDropdownMenu"
                            data-selected-course-center="7">  1年1組
                            </a>
                            <input type="hidden" 
                                name="students[${currentIndex}][course_id]" 
                                value="7" 
                                class="course-hidden-input">
                        </div>
                    `;
                    
                    tableContainer.appendChild(newRow);
                    
                    const newCourseLink = newRow.querySelector('.course-display');
                    if (newCourseLink) {
                        setupCourseDropdown(newCourseLink);
                    }
                }
                
                showCustomAlert(`${count} 名の空のアカウント行を追加しました。`);
                tableContainer.scrollTop = tableContainer.scrollHeight;
                closeModal();
            });
        }
    } // end student_addition.html 固有の処理
});