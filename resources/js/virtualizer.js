(function () {
  window.KbVirtualizer = {
    windowFor: function (count, overscan, start, windowSize) {
      var padding = Math.max(1, overscan || 4);
      var size = Math.max(1, windowSize || padding * 4);
      var offset = Math.max(0, Math.min(Math.max(0, count - size), start || 0));
      return { start: offset, end: Math.min(count, offset + size), overscan: padding };
    }
  };

  if (typeof document === 'undefined' || typeof MutationObserver === 'undefined') return;
  var selector = '.kb-stage-scroll, .kb-list-scroll, .kb-board';
  var restoring = false;
  function key(el) {
    var stage = el.closest('.kb-stage');
    return el.classList.contains('kb-stage-scroll') ? 'stage:' + stage.dataset.stage : el.className;
  }
  document.addEventListener('scroll', function (event) {
    var el = event.target;
    if (restoring || !el.matches || !el.matches(selector) || !el.getClientRects().length) return;
    var root = el.closest('.kb');
    if (!root) return;
    root.__kbScrollPositions = root.__kbScrollPositions || {};
    root.__kbScrollPositions[key(el)] = { top: el.scrollTop, left: el.scrollLeft };
  }, true);
  function observe() {
    if (!document.body) return;
    new MutationObserver(function () {
      restoring = true;
      document.querySelectorAll('.kb').forEach(function (root) {
        var positions = root.__kbScrollPositions || {};
        root.querySelectorAll(selector).forEach(function (el) {
          var saved = positions[key(el)];
          if (saved && el.getClientRects().length) { el.scrollTop = saved.top; el.scrollLeft = saved.left; }
        });
      });
      restoring = false;
    }).observe(document.body, { childList: true, subtree: true });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', observe, { once: true });
  else observe();
})();
