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
  if (typeof document === 'undefined' || !document.addEventListener) return;
  var selector = 'button:not(:disabled),input:not(:disabled),select:not(:disabled),textarea:not(:disabled),a[href],summary,[tabindex]:not([tabindex="-1"])';
  var active = null;
  var release = null;
  window.KbFocus = {
    trap: function (dialog) {
      if (!dialog) return function () {};
      var previous = document.activeElement;
      var inert = [];
      for (var node = dialog; node && node !== document.body; node = node.parentElement) {
        if (!node.parentElement) break;
        Array.from(node.parentElement.children).forEach(function (sibling) {
          if (sibling !== node && !sibling.inert && !/^(SCRIPT|STYLE|LINK)$/.test(sibling.tagName)) {
            sibling.inert = true;
            inert.push(sibling);
          }
        });
      }
      function items() {
        return Array.from(dialog.querySelectorAll(selector)).filter(function (el) {
          return el.getClientRects().length && !el.closest('[inert]');
        });
      }
      function keepFocus(event) {
        if (event.key !== 'Tab') return;
        var list = items();
        var first = list[0] || dialog;
        var last = list[list.length - 1] || dialog;
        if (!list.length || (event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last)) {
          event.preventDefault();
          (event.shiftKey ? last : first).focus();
        }
      }
      dialog.tabIndex = -1;
      dialog.addEventListener('keydown', keepFocus);
      (items()[0] || dialog).focus();
      return function () {
        dialog.removeEventListener('keydown', keepFocus);
        inert.forEach(function (el) { el.inert = false; });
        if (previous && previous.isConnected && previous.focus) previous.focus();
      };
    }
  };
  function sync() {
    var dialogs = document.querySelectorAll('.kb [aria-modal="true"]');
    var next = dialogs[dialogs.length - 1] || null;
    if (next === active) return;
    if (release) release();
    active = next;
    release = next ? window.KbFocus.trap(next) : null;
  }
  function boot() {
    sync();
    new MutationObserver(sync).observe(document.body, { childList: true, subtree: true });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
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
  var indicator = null;
  var scrollFrame = null;
  var pointer = null;
  var touch = null;

  function stopScroll() {
    if (scrollFrame) cancelAnimationFrame(scrollFrame);
    scrollFrame = null;
    pointer = null;
  }

  function autoScroll() {
    if (!dragging || !dragging.root.isConnected || !pointer) { stopScroll(); return; }
    var board = dragging.root.querySelector('.kb-board');
    var target = document.elementFromPoint(pointer.x, pointer.y);
    var stage = closest(target, '.kb-stage-scroll');
    [board, stage].forEach(function (el) {
      if (!el || !dragging.root.contains(el)) return;
      var r = el.getBoundingClientRect();
      var dx = pointer.x < r.left + 48 ? -12 : pointer.x > r.right - 48 ? 12 : 0;
      var dy = pointer.y < r.top + 48 ? -12 : pointer.y > r.bottom - 48 ? 12 : 0;
      el.scrollBy(el === board ? dx : 0, el === stage ? dy : 0);
    });
    scrollFrame = requestAnimationFrame(autoScroll);
  }

  function trackScroll(x, y) {
    pointer = { x: x, y: y };
    if (!scrollFrame) scrollFrame = requestAnimationFrame(autoScroll);
  }

  function closest(el, selector) {
    return el && el.closest ? el.closest(selector) : null;
  }

  function reducedMotion(root) {
    return root && root.getAttribute('data-reduced-motion') === 'true';
  }

  function getIndicator() {
    if (!indicator) {
      indicator = document.createElement('div');
      indicator.className = 'kb-drop-indicator';
      indicator.setAttribute('data-kb-part', 'drop-indicator');
      indicator.setAttribute('aria-hidden', 'true');
    }
    return indicator;
  }

  function removeIndicator() {
    if (indicator && indicator.parentNode) {
      indicator.parentNode.removeChild(indicator);
    }
  }

  function clearTargets() {
    removeIndicator();
    var stages = document.querySelectorAll('.kb-stage');
    for (var i = 0; i < stages.length; i++) {
      stages[i].classList.remove(
        'kb-drop-target',
        'kb-drop-allowed',
        'kb-drop-blocked',
        'kb-stage--source'
      );
      stages[i].removeAttribute('data-drop-state');
    }
  }

  function parseMatrix(root) {
    if (!root) {
      return { policy: 'allow', transitions: {} };
    }
    var raw = root.getAttribute('data-kb-transitions');
    if (!raw) {
      return { policy: 'allow', transitions: {} };
    }
    try {
      return JSON.parse(raw) || { policy: 'allow', transitions: {} };
    } catch (_e) {
      return { policy: 'allow', transitions: {} };
    }
  }

  function isStageAllowed(root, fromId, stageEl) {
    var toId = stageEl.getAttribute('data-stage');
    if (!toId) {
      return false;
    }
    if (stageEl.getAttribute('data-disabled') === 'true' || stageEl.getAttribute('data-readonly') === 'true') {
      return false;
    }
    if (fromId === toId) {
      return root.getAttribute('data-reorder') !== 'false';
    }
    var wipLimit = parseInt(stageEl.getAttribute('data-wip-limit') || '', 10);
    var wipMode = stageEl.getAttribute('data-wip-mode') || 'off';
    var totalCount = parseInt(stageEl.getAttribute('data-total-count') || '', 10);
    if (wipMode === 'strict' && !isNaN(wipLimit) && !isNaN(totalCount) && totalCount >= wipLimit) {
      return false;
    }
    var matrix = parseMatrix(root);
    var map = matrix.transitions || {};
    var keys = Object.keys(map);
    if (keys.length > 0) {
      var allowed = map[fromId] || [];
      return allowed.indexOf(toId) !== -1;
    }
    return matrix.policy === 'allow';
  }

  function markAllStages(root, fromStageEl, fromId) {
    if (!root) {
      return;
    }
    if (fromStageEl) {
      fromStageEl.classList.add('kb-stage--source');
    }
    var stages = root.querySelectorAll('.kb-stage');
    for (var i = 0; i < stages.length; i++) {
      var st = stages[i];
      var ok = isStageAllowed(root, fromId, st);
      st.setAttribute('data-drop-state', ok ? 'allowed' : 'blocked');
    }
  }

  function positionIndicator(stage, clientY, draggingEl) {
    var container = stage.querySelector('.kb-virtual') || stage.querySelector('.kb-stage-scroll');
    if (!container) {
      return { before: null, after: null };
    }
    var cards = stage.querySelectorAll('.kb-card');
    var before = null;
    var after = null;
    var last = null;
    var line = getIndicator();

    for (var i = 0; i < cards.length; i++) {
      var card = cards[i];
      if (card === draggingEl) {
        continue;
      }
      var rect = card.getBoundingClientRect();
      var mid = rect.top + rect.height / 2;
      if (clientY < mid) {
        before = card.getAttribute('data-card');
        card.parentNode.insertBefore(line, card);
        return { before: before, after: null };
      }
      last = card;
    }

    if (last) {
      after = last.getAttribute('data-card');
      if (last.nextSibling) {
        last.parentNode.insertBefore(line, last.nextSibling);
      } else {
        last.parentNode.appendChild(line);
      }
    } else {
      container.insertBefore(line, container.firstChild);
    }

    return { before: null, after: after };
  }

  function animateFlash(el, cls, ms) {
    if (!el) {
      return;
    }
    el.classList.remove(cls);
    void el.offsetWidth;
    el.classList.add(cls);
    setTimeout(function () {
      el.classList.remove(cls);
    }, ms || 650);
  }

  document.addEventListener('dragstart', function (event) {
    var card = closest(event.target, '.kb-card');
    if (!card || card.getAttribute('draggable') !== 'true') {
      return;
    }
    var stage = closest(card, '.kb-stage');
    var root = closest(card, '.kb');
    if (!stage) {
      return;
    }
    var fromId = stage.getAttribute('data-stage');
    dragging = {
      id: card.getAttribute('data-card'),
      from: fromId,
      version: card.getAttribute('data-version'),
      el: card,
      root: root
    };
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', dragging.id);
    card.classList.add('kb-dragging');
    markAllStages(root, stage, fromId);
  });

  document.addEventListener('dragover', function (event) {
    if (!dragging) {
      return;
    }
    var stage = closest(event.target, '.kb-stage');
    if (!stage || closest(stage, '.kb') !== dragging.root) {
      return;
    }
    event.preventDefault();
    trackScroll(event.clientX, event.clientY);
    var allowed = isStageAllowed(dragging.root, dragging.from, stage);
    event.dataTransfer.dropEffect = allowed ? 'move' : 'none';

    var stages = (dragging.root || document).querySelectorAll('.kb-stage');
    for (var i = 0; i < stages.length; i++) {
      stages[i].classList.remove('kb-drop-target', 'kb-drop-allowed', 'kb-drop-blocked');
    }

    stage.classList.add('kb-drop-target');
    if (allowed) {
      stage.classList.add('kb-drop-allowed');
      positionIndicator(stage, event.clientY, dragging.el);
    } else {
      stage.classList.add('kb-drop-blocked');
      removeIndicator();
    }
  });

  document.addEventListener('dragend', function () {
    if (dragging && dragging.el) {
      dragging.el.classList.remove('kb-dragging');
    }
    dragging = null;
    stopScroll();
    clearTargets();
  });

  document.addEventListener('drop', function (event) {
    if (!dragging) {
      return;
    }
    var stage = closest(event.target, '.kb-stage');
    if (!stage || closest(stage, '.kb') !== dragging.root) {
      return;
    }
    event.preventDefault();
    var current = dragging;
    dragging = null;
    stopScroll();
    var pos = positionIndicator(stage, event.clientY, current.el);
    var allowed = isStageAllowed(current.root, current.from, stage);
    clearTargets();
    current.el.classList.remove('kb-dragging');

    var to = stage.getAttribute('data-stage');
    if (!allowed && !reducedMotion(current.root)) {
      animateFlash(stage, 'kb-stage--shake', 600);
      animateFlash(current.el, 'kb-card--rejected', 750);
    }

    var comp = window.KbSort ? window.KbSort.component(stage) : null;
    if (comp) {
      window.KbSort.requestMove(comp, current.id, current.from, to, pos.before, pos.after, current.version);
      if (window.KbAnnouncer) {
        window.KbAnnouncer.say(allowed ? 'move requested' : 'move rejected');
      }
    }
  });

  window.addEventListener('kanban:move-confirmed', function (event) {
    var detail = (event && event.detail) || {};
    var cardId = detail.cardId;
    var toStageId = detail.toStageId;
    var root = document.querySelector('.kb[data-instance="' + CSS.escape(String(detail.instanceId || '')) + '"]');
    if (!root || reducedMotion(root)) return;
    setTimeout(function () {
      if (cardId) {
        var cardEl = root.querySelector('.kb-card[data-card="' + CSS.escape(String(cardId)) + '"]');
        animateFlash(cardEl, 'kb-card--settled', 750);
      }
      if (toStageId) {
        var stageEl = root.querySelector('.kb-stage[data-stage="' + CSS.escape(String(toStageId)) + '"]');
        animateFlash(stageEl, 'kb-stage--accepted', 650);
      }
    }, 30);
  });

  window.addEventListener('kanban:move-rejected', function (event) {
    var detail = (event && event.detail) || {};
    var cardId = detail.cardId;
    var toStageId = detail.toStageId;
    var root = document.querySelector('.kb[data-instance="' + CSS.escape(String(detail.instanceId || '')) + '"]');
    if (!root || reducedMotion(root)) return;
    setTimeout(function () {
      if (cardId) {
        var cardEl = root.querySelector('.kb-card[data-card="' + CSS.escape(String(cardId)) + '"]');
        animateFlash(cardEl, 'kb-card--rejected', 800);
      }
      if (toStageId) {
        var stageEl = root.querySelector('.kb-stage[data-stage="' + CSS.escape(String(toStageId)) + '"]');
        animateFlash(stageEl, 'kb-stage--shake', 650);
      }
    }, 20);
  });

  document.addEventListener('click', function (event) {
    var activeMenu = closest(event.target, 'details.kb-menu');
    var openMenus = document.querySelectorAll('details.kb-menu[open]');
    for (var i = 0; i < openMenus.length; i++) {
      if (openMenus[i] !== activeMenu) {
        openMenus[i].removeAttribute('open');
      }
    }
    if (activeMenu && closest(event.target, '.kb-card-menu button')) {
      setTimeout(function () { activeMenu.removeAttribute('open'); }, 0);
    }
  });

  // Dedicated handle leaves normal card/page scrolling available on touch devices.
  document.addEventListener('pointerdown', function (event) {
    if (event.pointerType !== 'touch' || !closest(event.target, '[data-kb-part="card-drag-handle"]')) return;
    var card = closest(event.target, '.kb-card');
    if (!card || card.getAttribute('draggable') !== 'true') return;
    event.preventDefault();
    var root = closest(card, '.kb');
    touch = { pointerId: event.pointerId, card: card };
    dragging = { id: card.dataset.card, from: card.dataset.stage, version: card.dataset.version, el: card, root: root };
    card.classList.add('kb-dragging');
    markAllStages(root, closest(card, '.kb-stage'), dragging.from);
    if (event.target.setPointerCapture) event.target.setPointerCapture(event.pointerId);
  });

  document.addEventListener('pointermove', function (event) {
    if (!touch || touch.pointerId !== event.pointerId || !dragging) return;
    event.preventDefault();
    trackScroll(event.clientX, event.clientY);
    var stage = closest(document.elementFromPoint(event.clientX, event.clientY), '.kb-stage');
    if (stage && closest(stage, '.kb') === dragging.root && isStageAllowed(dragging.root, dragging.from, stage)) {
      positionIndicator(stage, event.clientY, dragging.el);
    } else removeIndicator();
  }, { passive: false });

  document.addEventListener('pointerup', function (event) {
    if (!touch || touch.pointerId !== event.pointerId || !dragging) return;
    var current = dragging;
    var stage = closest(document.elementFromPoint(event.clientX, event.clientY), '.kb-stage');
    if (stage && closest(stage, '.kb') === current.root && isStageAllowed(current.root, current.from, stage)) {
      var pos = positionIndicator(stage, event.clientY, current.el);
      window.KbSort.requestMove(window.KbSort.component(stage), current.id, current.from, stage.dataset.stage, pos.before, pos.after, current.version);
    }
    current.el.classList.remove('kb-dragging');
    touch = null; dragging = null; stopScroll(); clearTargets();
  });

  document.addEventListener('pointercancel', function () {
    if (!touch) return;
    if (dragging) dragging.el.classList.remove('kb-dragging');
    touch = null; dragging = null; stopScroll(); clearTargets();
  });
})();

/* virtualizer.js */
(function () {
  window.KbVirtualizer = {
    windowFor: function (count, overscan, start, windowSize) {
      var padding = Math.max(1, overscan || 4);
      var size = Math.max(1, windowSize || padding * 4);
      var offset = Math.max(0, Math.min(Math.max(0, count - size), start || 0));
      return { start: offset, end: Math.min(count, offset + size), overscan: padding };
    }
  };

  if (typeof document === 'undefined' || typeof MutationObserver === 'undefined') return;
  var selector = '.kb-stage-scroll, .kb-list-scroll, .kb-board';
  var restoring = false;
  function key(el) {
    var stage = el.closest('.kb-stage');
    return el.classList.contains('kb-stage-scroll') ? 'stage:' + stage.dataset.stage : el.className;
  }
  document.addEventListener('scroll', function (event) {
    var el = event.target;
    if (restoring || !el.matches || !el.matches(selector) || !el.getClientRects().length) return;
    var root = el.closest('.kb');
    if (!root) return;
    root.__kbScrollPositions = root.__kbScrollPositions || {};
    root.__kbScrollPositions[key(el)] = { top: el.scrollTop, left: el.scrollLeft };
  }, true);
  function observe() {
    if (!document.body) return;
    new MutationObserver(function () {
      restoring = true;
      document.querySelectorAll('.kb').forEach(function (root) {
        var positions = root.__kbScrollPositions || {};
        root.querySelectorAll(selector).forEach(function (el) {
          var saved = positions[key(el)];
          if (saved && el.getClientRects().length) { el.scrollTop = saved.top; el.scrollLeft = saved.left; }
        });
      });
      restoring = false;
    }).observe(document.body, { childList: true, subtree: true });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', observe, { once: true });
  else observe();
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
    var wasConnected = false;
    var wasOffline = false;
    var handler = function (states) {
      var state = states.current;
      if (state === 'connected') {
        wasConnected = true;
        if (wasOffline) {
          wasOffline = false;
          component.setOffline(false);
          component.reconnect();
        }
      } else if (
        wasConnected
        && (state === 'unavailable' || state === 'disconnected' || state === 'failed')
      ) {
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
