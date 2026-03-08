(function () {
  if (window.__pgTechStackDragScrollBound) return;
  window.__pgTechStackDragScrollBound = true;

  var TRACK_SELECTOR = '[data-pg-tech-stack-track="1"]';
  var activeTrack = null;
  var activePointerId = null;
  var startX = 0;
  var startScrollLeft = 0;
  var moved = false;
  var suppressClickUntil = 0;

  function now() {
    return Date.now();
  }

  function isPrimaryPointer(event) {
    if (typeof event.button === 'number' && event.button !== 0) return false;
    return true;
  }

  function releaseDrag(event) {
    if (!activeTrack) return;

    if (activePointerId != null && typeof activeTrack.releasePointerCapture === 'function') {
      try {
        activeTrack.releasePointerCapture(activePointerId);
      } catch (_) {
        // noop
      }
    }

    activeTrack.classList.remove('is-dragging');

    if (moved) {
      suppressClickUntil = now() + 180;
    }

    activeTrack = null;
    activePointerId = null;
    moved = false;
    startX = 0;
    startScrollLeft = 0;
  }

  document.addEventListener(
    'pointerdown',
    function (event) {
      var track = event.target && event.target.closest ? event.target.closest(TRACK_SELECTOR) : null;
      if (!track || !isPrimaryPointer(event)) return;

      activeTrack = track;
      activePointerId = typeof event.pointerId === 'number' ? event.pointerId : null;
      startX = event.clientX;
      startScrollLeft = track.scrollLeft;
      moved = false;
      track.classList.add('is-dragging');

      if (activePointerId != null && typeof track.setPointerCapture === 'function') {
        try {
          track.setPointerCapture(activePointerId);
        } catch (_) {
          // noop
        }
      }
    },
    true
  );

  document.addEventListener(
    'pointermove',
    function (event) {
      if (!activeTrack) return;
      if (activePointerId != null && typeof event.pointerId === 'number' && event.pointerId !== activePointerId) return;

      var deltaX = event.clientX - startX;
      if (!moved && Math.abs(deltaX) > 6) {
        moved = true;
      }

      if (!moved) return;

      activeTrack.scrollLeft = startScrollLeft - deltaX;
      event.preventDefault();
    },
    true
  );

  document.addEventListener(
    'pointerup',
    function (event) {
      if (activePointerId != null && typeof event.pointerId === 'number' && event.pointerId !== activePointerId) return;
      releaseDrag(event);
    },
    true
  );

  document.addEventListener(
    'pointercancel',
    function (event) {
      if (activePointerId != null && typeof event.pointerId === 'number' && event.pointerId !== activePointerId) return;
      releaseDrag(event);
    },
    true
  );

  document.addEventListener(
    'click',
    function (event) {
      var track = event.target && event.target.closest ? event.target.closest(TRACK_SELECTOR) : null;
      if (!track) return;
      if (now() > suppressClickUntil) return;

      event.preventDefault();
      event.stopPropagation();
    },
    true
  );
})();
