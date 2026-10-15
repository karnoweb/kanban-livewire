(function () {
  if (typeof document === 'undefined' || !document.addEventListener) {
    return;
  }

  var dragging = null;

  function closest(el, selector) {
    return el && el.closest ? el.closest(selector) : null;
  }

  function clearTargets() {
    var targets = document.querySelectorAll('.kb-drop-target');
    for (var i = 0; i < targets.length; i++) {
      targets[i].classList.remove('kb-drop-target');
    }
  }

  document.addEventListener('dragstart', function (event) {
    var card = closest(event.target, '.kb-card');
    if (!card || card.getAttribute('draggable') !== 'true') {
      return;
    }
    var stage = closest(card, '.kb-stage');
    if (!stage) {
      return;
    }
    dragging = {
      id: card.getAttribute('data-card'),
      from: stage.getAttribute('data-stage'),
      version: card.getAttribute('data-version'),
      el: card
    };
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', dragging.id);
    card.classList.add('kb-dragging');
  });

  document.addEventListener('dragover', function (event) {
    if (!dragging) {
      return;
    }
    var stage = closest(event.target, '.kb-stage');
    if (!stage) {
      return;
    }
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
    clearTargets();
    stage.classList.add('kb-drop-target');
  });

  document.addEventListener('dragend', function () {
    if (dragging && dragging.el) {
      dragging.el.classList.remove('kb-dragging');
    }
    dragging = null;
    clearTargets();
  });

  document.addEventListener('drop', function (event) {
    if (!dragging) {
      return;
    }
    var stage = closest(event.target, '.kb-stage');
    if (!stage) {
      return;
    }
    event.preventDefault();
    var current = dragging;
    dragging = null;
    clearTargets();
    current.el.classList.remove('kb-dragging');

    var to = stage.getAttribute('data-stage');
    var cards = stage.querySelectorAll('.kb-card');
    var before = null;
    var after = null;
    var last = null;

    for (var i = 0; i < cards.length; i++) {
      var card = cards[i];
      if (card === current.el) {
        continue;
      }
      last = card;
      var rect = card.getBoundingClientRect();
      if (before === null && event.clientY < rect.top + rect.height / 2) {
        before = card.getAttribute('data-card');
      }
    }

    if (before === null && last) {
      after = last.getAttribute('data-card');
    }

    var comp = window.KbSort ? window.KbSort.component(stage) : null;
    if (comp) {
      window.KbSort.requestMove(comp, current.id, current.from, to, before, after, current.version);
      if (window.KbAnnouncer) {
        window.KbAnnouncer.say('move requested');
      }
    }
  });
})();
