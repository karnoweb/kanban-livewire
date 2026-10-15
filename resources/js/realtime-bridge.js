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
