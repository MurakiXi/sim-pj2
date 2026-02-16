(() => {
    const root = document.getElementById('clock');
    const dateEl = document.getElementById('today-label');
    const timeEl = document.getElementById('time-label');
    if (!root || !dateEl || !timeEl) return;

    const serverNow = parseInt(root.dataset.serverNow, 10);
    const offset = serverNow - Date.now();

    const youbi = ['日', '月', '火', '水', '木', '金', '土'];

    const formatDate = (d) => {
        const y = d.getFullYear();
        const m = d.getMonth() + 1;
        const day = d.getDate();
        const w = youbi[d.getDay()];
        return `${y}年${m}月${day}日(${w})`;
    };

    const formatTime = (d) => {
        const hh = String(d.getHours()).padStart(2, '0');
        const mm = String(d.getMinutes()).padStart(2, '0');
        return `${hh}:${mm}`;
    };


    let lastDateText = '';

    const render = () => {
        const d = new Date(Date.now() + offset);

        const dateText = formatDate(d);
        if (dateText !== lastDateText) {
            dateEl.textContent = dateText;
            lastDateText = dateText;
        }

        timeEl.textContent = formatTime(d);
    };

    render();
    setInterval(render, 1000);
})();
