(function () {
  window.KbConfirmation = {
    cancel: function (component) {
      component.call('cancelPending');
    }
  };
})();
