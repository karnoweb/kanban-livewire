(function () {
  window.KbAnnouncer = {
    say: function (message) {
      var node = document.querySelector('.kb-live');
      if (node) {
        node.textContent = message;
      }
    }
  };
})();
