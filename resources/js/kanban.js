(function () {
  function boot() {
    var livewire = window.Livewire;
    if (!livewire || window.__kbKanbanBooted) {
      return;
    }
    window.__kbKanbanBooted = true;
    if (livewire.hook) {
      livewire.hook('morphed', function () {});
    }
  }
  document.addEventListener('livewire:init', boot);
  if (window.Livewire) {
    boot();
  }
})();
