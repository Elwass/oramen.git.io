(function () {
    const isDashboard = document.querySelector('.order-row');
    if (!isDashboard) return;

    let lastOrderId = Number(localStorage.getItem('ramen1:lastOrderId') || 0);

    const beep = () => {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, ctx.currentTime);
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            oscillator.connect(gain);
            gain.connect(ctx.destination);
            oscillator.start();
            oscillator.stop(ctx.currentTime + 0.3);
        } catch (err) {
            console.error('Gagal memutar notifikasi suara', err);
        }
    };

    const poll = () => {
        fetch('/api/latest_order.php', { cache: 'no-store' })
            .then((res) => res.json())
            .then((data) => {
                if (data.latest_id && Number(data.latest_id) > lastOrderId) {
                    lastOrderId = Number(data.latest_id);
                    localStorage.setItem('ramen1:lastOrderId', String(lastOrderId));
                    beep();
                }
            })
            .catch((err) => console.error('Gagal memeriksa pesanan baru', err));
    };

    setInterval(poll, 15000);
})();
