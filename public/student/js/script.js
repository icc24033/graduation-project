"use strict";

document.addEventListener('DOMContentLoaded', function() {
    // --- 1. 日付選択機能 ---
    const dateBtn = document.getElementById('dateTriggerBtn');
    const dateInput = document.getElementById('hiddenDateInput');
    const mainForm = document.getElementById('mainForm');

    if (dateBtn && dateInput && mainForm) {
        dateBtn.addEventListener('click', () => {
            // ブラウザのネイティブピッカーを呼び出す
            if (dateInput.showPicker) { 
                dateInput.showPicker(); 
            } else { 
                dateInput.click(); 
            }
        });

        // 日付が変更されたら即座にフォームを送信
        dateInput.addEventListener('change', () => { 
            mainForm.submit(); 
        });
    }

    // --- 2. コース選択（ドロップダウン内のアイテム）機能 ---
    const courseItems = document.querySelectorAll('.course-select-wrapper .dropdown-item');
    const hiddenCourseInput = document.getElementById('hiddenCourseInput');

    if (courseItems.length > 0 && hiddenCourseInput && mainForm) {
        courseItems.forEach(item => {
            item.addEventListener('click', function() {
                hiddenCourseInput.value = this.getAttribute('data-value');
                mainForm.submit();
            });
        });
    }

    // --- 3. アコーディオン（詳細・持ち物）の開閉制御 ---
    const toggleButtons = document.querySelectorAll('.dropdown-toggle');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() { 
            toggleDropdown(this); 
        });
    });
});

/**
 * ドロップダウンを閉じる処理
 */
function closeDropdown(wrapper) {
    wrapper.classList.remove('active');
    const button = wrapper.querySelector('.dropdown-toggle');
    if (button) {
        button.setAttribute('aria-expanded', 'false');
        const internalContent = wrapper.querySelector('.dropdown-content');
        if (internalContent) {
            internalContent.classList.remove('show');
        }
    }
}

/**
 * ドロップダウンの切り替え（排他制御含む）
 */
function toggleDropdown(button) {
    const wrapper = button.closest('.dropdown-wrapper');
    if (!wrapper) return;

    const isExpanded = wrapper.classList.contains('active');

    // 授業詳細と持ってくるものが同じカード内にある場合、一方が開いたらもう一方を閉じる
    if (button.classList.contains('detail-toggle') || button.classList.contains('item-toggle')) {
        const card = button.closest('.card');
        if (card) {
            const otherClass = button.classList.contains('detail-toggle') 
                               ? '.item-dropdown-wrapper' 
                               : '.detail-dropdown-wrapper';
            const otherWrapper = card.querySelector(otherClass);
            if (otherWrapper) {
                closeDropdown(otherWrapper);
            }
        }
    }

    // 自身の開閉状態を切り替え
    if (isExpanded) {
        closeDropdown(wrapper);
    } else {
        wrapper.classList.add('active');
        button.setAttribute('aria-expanded', 'true');
        const internalContent = wrapper.querySelector('.dropdown-content');
        if (internalContent) {
            internalContent.classList.add('show');
        }
    }
}