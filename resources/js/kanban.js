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
    function positionPanel(panel) {
      var body = panel.querySelector('.kb-toolbar-options-body');
      var trigger = panel.querySelector('summary');
      if (!body || !trigger) return;
      var rect = trigger.getBoundingClientRect();
      var margin = 16;
      var desired = panel.matches('.kb-assignee-overflow') ? 260 : (panel.matches('.kb-overflow-menu, .kb-sort-menu') ? 220 : 480);
      var width = Math.min(desired, window.innerWidth - margin * 2);
      var left = getComputedStyle(panel).direction === 'rtl' ? rect.right - width : rect.left;
      left = Math.max(margin, Math.min(left, window.innerWidth - width - margin));
      var below = window.innerHeight - rect.bottom - margin - 8;
      var above = rect.top - margin - 8;
      var upwards = below < Math.min(body.scrollHeight, 220) && above > below;
      body.style.width = width + 'px';
      body.style.left = left + 'px';
      body.style.right = 'auto';
      body.style.maxHeight = Math.max(0, upwards ? above : below) + 'px';
      body.style.top = upwards ? 'auto' : Math.max(margin, rect.bottom + 8) + 'px';
      body.style.bottom = upwards ? (window.innerHeight - rect.top + 8) + 'px' : 'auto';
    }
    document.addEventListener('toggle', function (event) {
      var panel = event.target;
      if (panel.matches && panel.matches('.kb .kb-toolbar-options[open]')) positionPanel(panel);
    }, true);
    function repositionPanels() {
      document.querySelectorAll('.kb .kb-toolbar-options[open]').forEach(positionPanel);
    }
    window.addEventListener('resize', repositionPanels);
    document.addEventListener('scroll', function (event) {
      if (event.target.closest && event.target.closest('.kb-toolbar-options-body')) return;
      repositionPanels();
    }, true);
    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      var panel = event.target.closest && event.target.closest('.kb-toolbar-options[open]');
      if (panel) {
        panel.open = false;
        panel.querySelector('summary').focus();
      }
    });
    if (livewire.hook) {
      livewire.hook('morphed', repositionPanels);
    }
  }
  document.addEventListener('livewire:init', boot);
  if (window.Livewire) {
    boot();
  }
})();
