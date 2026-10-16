(function () {
  function boot() {
    var livewire = window.Livewire;
    if (!livewire || window.__kbKanbanBooted) {
      return;
    }
    window.__kbKanbanBooted = true;
    document.addEventListener('click', function (event) {
      document.querySelectorAll('.kb-toolbar-options[open]').forEach(function (panel) {
        if (!panel.contains(event.target)) panel.open = false;
      });
    });
    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      var panel = event.target.closest && event.target.closest('.kb-toolbar-options[open]');
      if (panel) {
        panel.open = false;
        panel.querySelector('summary').focus();
      }
    });
    if (livewire.hook) {
      livewire.hook('morphed', function () {});
    }
  }
  document.addEventListener('livewire:init', boot);
  if (window.Livewire) {
    boot();
  }
})();
