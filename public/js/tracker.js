(function () {
  'use strict';

  var STORAGE_KEY_VID = 'ac_vid';
  var STORAGE_KEY_REF = 'ac_ref';
  var BEACON_PING     = '/api/pixel/ping';
  var BEACON_CONVERT  = '/api/pixel/convert';

  // ─── Resolve SaaS backend origin & tenant slug from <script data-tenant> ───
  var scriptEl    = document.currentScript || (function () {
    var scripts = document.getElementsByTagName('script');
    for (var i = scripts.length - 1; i >= 0; i--) {
      if (scripts[i].getAttribute('data-tenant')) return scripts[i];
    }
    return null;
  })();

  var tenantSlug  = scriptEl ? scriptEl.getAttribute('data-tenant') : null;
  var backendBase = scriptEl ? (scriptEl.getAttribute('data-endpoint') || '') : '';

  if (!tenantSlug) {
    if (typeof console !== 'undefined') {
      console.warn('[AffiliateCore] Missing data-tenant attribute on script tag.');
    }
    return;
  }

  // ─── UUID v4 (crypto-secure) ──────────────────────────────────────────────
  function uuidv4() {
    if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
      var buf = new Uint8Array(16);
      crypto.getRandomValues(buf);
      buf[6] = (buf[6] & 0x0f) | 0x40;
      buf[8] = (buf[8] & 0x3f) | 0x80;
      var hex = [];
      for (var i = 0; i < 16; i++) {
        hex.push(('0' + buf[i].toString(16)).slice(-2));
      }
      return (
        hex.slice(0, 4).join('') + '-' +
        hex.slice(4, 6).join('') + '-' +
        hex.slice(6, 8).join('') + '-' +
        hex.slice(8, 10).join('') + '-' +
        hex.slice(10, 16).join('')
      );
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
      var r = (Math.random() * 16) | 0;
      return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
    });
  }

  // ─── Transport layer: sendBeacon → fetch keepalive → XHR fallback ─────────
  function send(path, payload) {
    var url  = backendBase + path;
    var json = JSON.stringify(payload);

    if (typeof navigator !== 'undefined' && navigator.sendBeacon) {
      try {
        var blob = new Blob([json], { type: 'application/json' });
        if (navigator.sendBeacon(url, blob)) return;
      } catch (_) { /* fall through */ }
    }

    if (typeof fetch !== 'undefined') {
      try {
        fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: json,
          keepalive: true,
          credentials: 'omit'
        }).catch(function () {});
        return;
      } catch (_) { /* fall through */ }
    }

    try {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', url, true);
      xhr.setRequestHeader('Content-Type', 'application/json');
      xhr.send(json);
    } catch (_) {}
  }

  // ─── LocalStorage helpers (First-Party Storage Relay) ─────────────────────
  function lsSet(key, val) {
    try { window.localStorage.setItem(key, val); } catch (_) {}
  }

  function lsGet(key) {
    try { return window.localStorage.getItem(key); } catch (_) { return null; }
  }

  // ─── Extract ?ref= from current URL ──────────────────────────────────────
  function getRefParam() {
    try {
      var params = new URLSearchParams(window.location.search);
      return params.get('ref') || null;
    } catch (_) {
      var match = window.location.search.match(/[?&]ref=([^&#]+)/);
      return match ? decodeURIComponent(match[1]) : null;
    }
  }

  // ─── Boot: capture click on page load ─────────────────────────────────────
  var refHash = getRefParam();

  if (refHash) {
    var visitorId = lsGet(STORAGE_KEY_VID) || uuidv4();

    lsSet(STORAGE_KEY_VID, visitorId);
    lsSet(STORAGE_KEY_REF, refHash);

    send(BEACON_PING, {
      tenant_slug: tenantSlug,
      hash:        refHash,
      visitor_id:  visitorId,
      url:         window.location.href,
      referrer:    document.referrer || null
    });
  }

  // ─── Public API ───────────────────────────────────────────────────────────
  window.AffiliateCore = window.AffiliateCore || {};

  /**
   * Track a conversion on the tenant store.
   * Call after a successful order: AffiliateCore.trackConversion('ORD-123', 299.99)
   *
   * @param {string}          orderId  - External order / transaction reference
   * @param {number|string}   amount   - Sale amount (gross)
   */
  window.AffiliateCore.trackConversion = function (orderId, amount) {
    var vid = lsGet(STORAGE_KEY_VID);
    var ref = lsGet(STORAGE_KEY_REF);

    if (!vid || !ref) return;

    send(BEACON_CONVERT, {
      tenant_slug: tenantSlug,
      hash:        ref,
      visitor_id:  vid,
      order_id:    String(orderId),
      amount:      parseFloat(amount) || 0
    });
  };

  /**
   * Check if the current visitor was referred by an affiliate.
   * @returns {{ visitor_id: string, hash: string } | null}
   */
  window.AffiliateCore.getAttribution = function () {
    var vid = lsGet(STORAGE_KEY_VID);
    var ref = lsGet(STORAGE_KEY_REF);
    return (vid && ref) ? { visitor_id: vid, hash: ref } : null;
  };
})();
