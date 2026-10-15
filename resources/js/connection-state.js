(function () {
  window.KbConnection = {
    mark: function (root, state) {
      if (root) {
        root.dataset.connection = state;
      }
    }
  };
})();
