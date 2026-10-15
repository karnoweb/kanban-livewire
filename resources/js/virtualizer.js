(function () {
  window.KbVirtualizer = {
    windowFor: function (count, overscan) {
      var size = Math.max(1, overscan || 4);
      return { start: 0, end: Math.min(count, size * 4), overscan: size };
    }
  };
})();
