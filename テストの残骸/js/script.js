// script.js
document.addEventListener('DOMContentLoaded', function() {
    
    // 全てのドロップダウンボタンを取得
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

    // 各ボタンに対してクリックイベントを設定
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            
            // クリックされたボタンの data-target属性の値（開くコンテンツのID）を取得
            const targetId = this.dataset.target; 
            const dropdownContent = document.getElementById(targetId);

            if (dropdownContent) {
                
                // ボタンの active クラスを切り替える（アイコンの回転制御）
                this.classList.toggle('active'); 
                
                // コンテンツの active クラスを切り替える（表示/非表示の制御）
                dropdownContent.classList.toggle('active');
            }
        });
    });
});