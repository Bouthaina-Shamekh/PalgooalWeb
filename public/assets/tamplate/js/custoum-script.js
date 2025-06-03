  document.addEventListener('DOMContentLoaded', () => {
    const promoBar = document.getElementById('promo-bar');
    const timeSpan = document.getElementById('promo-time');
    const closeBtn = document.getElementById('close-promo-bar');

    let duration = 600; // 10 دقائق

    function updateTimer() {
      const mins = Math.floor(duration / 60);
      const secs = duration % 60;

      const minLabel = mins === 0 ? '' :
        mins === 1 ? 'دقيقة' :
        mins === 2 ? 'دقيقتان' :
        mins <= 10 ? `${mins} دقائق` :
        `${mins} دقيقة`;

      const secLabel = secs === 0 ? '' :
        secs === 1 ? '1 ثانية' :
        secs === 2 ? '2 ثانية' :
        secs <= 10 ? `${secs} ثوانٍ` :
        `${secs} ثانية`;

      timeSpan.textContent = (minLabel ? minLabel : '') +
                             (minLabel && secLabel ? ' و' : '') +
                             (secLabel ? secLabel : '');
    }

    updateTimer();

    const interval = setInterval(() => {
      duration--;
      updateTimer();
      if (duration <= 0) {
        clearInterval(interval);
        promoBar.classList.add('opacity-0');
        setTimeout(() => promoBar.style.display = 'none', 300);
      }
    }, 1000);

    closeBtn.addEventListener('click', () => {
      promoBar.classList.add('opacity-0');
      setTimeout(() => promoBar.style.display = 'none', 300);
    });
  });





  
