(function () {
  if (typeof document === 'undefined' || !document.addEventListener) return;
  var selector = 'button:not(:disabled),input:not(:disabled),select:not(:disabled),textarea:not(:disabled),a[href],summary,[tabindex]:not([tabindex="-1"])';
  var active = null;
  var release = null;
  window.KbFocus = {
    trap: function (dialog) {
      if (!dialog) return function () {};
      var previous = document.activeElement;
      var previousOverflow = document.body.style.overflow;
      document.body.style.overflow = 'hidden';
      var inert = [];
      for (var node = dialog; node && node !== document.body; node = node.parentElement) {
        if (!node.parentElement) break;
        Array.from(node.parentElement.children).forEach(function (sibling) {
          if (sibling !== node && !sibling.inert && !/^(SCRIPT|STYLE|LINK)$/.test(sibling.tagName)) {
            sibling.inert = true;
            inert.push(sibling);
          }
        });
      }
      function items() {
        return Array.from(dialog.querySelectorAll(selector)).filter(function (el) {
          return el.getClientRects().length && !el.closest('[inert]');
        });
      }
      function keepFocus(event) {
        if (event.key !== 'Tab') return;
        var list = items();
        var first = list[0] || dialog;
        var last = list[list.length - 1] || dialog;
        if (!list.length || (event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last)) {
          event.preventDefault();
          (event.shiftKey ? last : first).focus();
        }
      }
      dialog.tabIndex = -1;
      dialog.addEventListener('keydown', keepFocus);
      (dialog.querySelector('[autofocus]') || items()[0] || dialog).focus();
      return function () {
        dialog.removeEventListener('keydown', keepFocus);
        document.body.style.overflow = previousOverflow;
        inert.forEach(function (el) { el.inert = false; });
        if (previous && previous.isConnected && previous.focus) previous.focus();
      };
    }
  };
  function sync() {
    var dialogs = document.querySelectorAll('.kb [aria-modal="true"]');
    var next = dialogs[dialogs.length - 1] || null;
    if (next === active) return;
    if (release) release();
    active = next;
    release = next ? window.KbFocus.trap(next) : null;
  }
  function boot() {
    sync();
    new MutationObserver(sync).observe(document.body, { childList: true, subtree: true });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
})();
