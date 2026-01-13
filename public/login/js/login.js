document.addEventListener('DOMContentLoaded', function() {
    // .fade-in クラスを持つすべての要素を取得
    const fadeElements = document.querySelectorAll('.fade-in');

    // 要素をひとつずつ時間差で表示する関数
    const showElements = () => {
        fadeElements.forEach((element, index) => {
            // setTimeoutで遅延を設定 (index * 200ms ずつずらす)
            setTimeout(() => {
                element.classList.add('active');
            }, index * 200 + 300); // 初回待機時間300ms + 順番ごとの遅延
        });
    };

    // 実行
    showElements();
});document.addEventListener('DOMContentLoaded', function() {
    // .fade-in クラスを持つすべての要素を取得
    const fadeElements = document.querySelectorAll('.fade-in');

    // 要素をひとつずつ時間差で表示する関数
    const showElements = () => {
        fadeElements.forEach((element, index) => {
            // setTimeoutで遅延を設定 (index * 200ms ずつずらす)
            setTimeout(() => {
                element.classList.add('active');
            }, index * 200 + 300); // 初回待機時間300ms + 順番ごとの遅延
        });
    };

    // 実行
    showElements();
});