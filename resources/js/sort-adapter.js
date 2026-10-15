(function () {
  function component(el) {
    if (!el || !el.closest || !window.Livewire) {
      return null;
    }
    var root = el.closest('[wire\\:id]');
    return root ? window.Livewire.find(root.getAttribute('wire:id')) : null;
  }

  window.KbSort = {
    engine: 'anchor-adapter',
    component: component,
    requestMove: function (comp, cardId, fromStageId, toStageId, beforeId, afterId, expectedVersion) {
      if (!comp) {
        return;
      }
      // Livewire.find() returns the component's $wire proxy.
      comp.requestMove(
        cardId,
        fromStageId,
        toStageId,
        beforeId || null,
        afterId || null,
        null,
        expectedVersion || null
      );
    }
  };
})();
