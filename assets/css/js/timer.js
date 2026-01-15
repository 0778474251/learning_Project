// assets/js/timer.js
function startTimer(durationMinutes, onFinish) {
  const end = Date.now() + durationMinutes * 60 * 1000;
  const el = document.getElementById('timer');
  function update() {
    const remaining = end - Date.now();
    if (remaining <= 0) { el.textContent = '00:00'; onFinish(); return; }
    const totalSeconds = Math.floor(remaining / 1000);
    const m = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
    const s = String(totalSeconds % 60).padStart(2, '0');
    el.textContent = `${m}:${s}`;
    requestAnimationFrame(update);
  }
  update();
}
