/* connection-state.js */
(function () {
  window.KbConnection = {
    mark: function (root, state) {
      if (root) {
        root.dataset.connection = state;
      }
    }
  };
})();

/* announcer.js */
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

/* focus-manager.js */
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

/* confirmation.js */
(function () {
  window.KbConfirmation = {
    cancel: function (component) {
      component.call('cancelPending');
    }
  };
})();

/* sort-adapter.js */
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

/* board-interactions.js */
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

/* virtualizer.js */
(function () {
  window.KbVirtualizer = {
    windowFor: function (count, overscan) {
      var size = Math.max(1, overscan || 4);
      return { start: 0, end: Math.min(count, size * 4), overscan: size };
    }
  };
})();

/* realtime-bridge.js */
(function () {
  var listeners = new Map();
  var EVENT = '.kanban.event';

  function bare(channel) {
    return channel.indexOf('private-') === 0 ? channel.slice(8) : channel;
  }

  function attach(id, echo, channel, handlers) {
    if (!echo || listeners.has(id)) {
      return;
    }
    var name = bare(channel);
    var subscription = echo.private(name);
    if (handlers && handlers.onEvent && subscription && subscription.listen) {
      subscription.listen(EVENT, handlers.onEvent);
    }
    listeners.set(id, { subscription: subscription, echo: echo, name: name, serial: handlers ? handlers.serial : null, unbind: handlers ? handlers.unbind : null });
  }

  function detach(id) {
    var entry = listeners.get(id);
    if (!entry) {
      return;
    }
    if (entry.subscription && entry.subscription.stopListening) {
      entry.subscription.stopListening(EVENT);
    }
    if (entry.unbind) {
      entry.unbind();
    }
    if (entry.echo && entry.echo.leave) {
      entry.echo.leave(entry.name);
    }
    listeners.delete(id);
  }

  window.KbRealtime = {
    attach: attach,
    detach: detach,
    size: function () {
      return listeners.size;
    }
  };

  if (typeof document === 'undefined' || typeof MutationObserver === 'undefined') {
    return;
  }

  function stateBinding(echo, component) {
    var pusher = echo && echo.connector && echo.connector.pusher;
    if (!pusher || !pusher.connection) {
      return null;
    }
    var wasOffline = false;
    var handler = function (states) {
      var state = states.current;
      if (state === 'connected') {
        if (wasOffline) {
          wasOffline = false;
          component.reconnect();
        }
      } else if (state === 'unavailable' || state === 'disconnected' || state === 'failed') {
        if (!wasOffline) {
          wasOffline = true;
          component.setOffline(true);
        }
      }
    };
    pusher.connection.bind('state_change', handler);
    return function () {
      pusher.connection.unbind('state_change', handler);
    };
  }

  function scan() {
    var roots = document.querySelectorAll('.kb[data-kb-realtime="true"]');
    var seen = {};

    for (var i = 0; i < roots.length; i++) {
      var el = roots[i];
      var id = el.getAttribute('wire:id');
      var channel = el.getAttribute('data-kb-channel');
      var serial = el.getAttribute('data-kb-serial');
      if (!id || !channel || !window.Livewire) {
        continue;
      }
      seen[id] = true;
      var existing = listeners.get(id);
      if (existing && existing.serial !== serial) {
        detach(id);
        existing = null;
      }
      if (existing) {
        continue;
      }
      var echo = window.Echo;
      if (!echo) {
        var warning = el.querySelector('.kb-realtime-warning');
        if (warning) {
          warning.hidden = false;
        }
        continue;
      }
      var component = window.Livewire.find(id);
      if (!component) {
        continue;
      }
      attach(id, echo, channel, {
        serial: serial,
        onEvent: function (comp) {
          return function (payload) {
            comp.applyRealtimeEvent(payload);
          };
        }(component),
        unbind: stateBinding(echo, component)
      });
    }

    listeners.forEach(function (_entry, id) {
      if (!seen[id]) {
        detach(id);
      }
    });
  }

  var observer = new MutationObserver(function () {
    scan();
  });

  function start() {
    scan();
    observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-kb-serial', 'data-kb-realtime'] });
  }

  document.addEventListener('livewire:init', scan);
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();

/* kanban.js */
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
