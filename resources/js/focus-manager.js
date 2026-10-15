(function () {
  window.KbFocus = {
    trap: function (dialog) {
      if (!dialog) {
        return function () {};
      }
      var previous = document.activeElement;
      var focusable = dialog.querySelector('button, input, [href]');
      if (focusable) {
        focusable.focus();
      }
      return function () {
        if (previous && previous.focus) {
          previous.focus();
        }
      };
    }
  };
})();
