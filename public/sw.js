var CACHE_VERSION = 'church-v2';
var STATIC_CACHE = 'church-static-v2';
var API_CACHE = 'church-api-v2';
var DOC_CACHE = 'church-docs-v2';

var PRECACHE_ASSETS = [
  '/',
  '/manifest.json',
  '/images/icon-192.png',
  '/images/icon-512.png',
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(STATIC_CACHE).then(function (cache) {
      return cache.addAll(PRECACHE_ASSETS);
    }).then(function () { return self.skipWaiting(); })
  );
});

self.addEventListener('activate', function (event) {
  var validCaches = [STATIC_CACHE, API_CACHE, DOC_CACHE, 'church-queue'];
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys.filter(function (k) { return !validCaches.includes(k); })
            .map(function (k) { return caches.delete(k); })
      );
    }).then(function () { return self.clients.claim(); })
  );
});

self.addEventListener('fetch', function (event) {
  var req = event.request;
  var url = new URL(req.url);
  if (req.method !== 'GET') return;

  if (isStaticAsset(url)) {
    event.respondWith(cacheFirst(req, STATIC_CACHE)); return;
  }
  if (isDocument(url)) {
    event.respondWith(cacheFirst(req, DOC_CACHE)); return;
  }
  if (isPublicApi(url)) {
    event.respondWith(staleWhileRevalidate(req, API_CACHE, 300)); return;
  }
  if (url.pathname.startsWith('/api/')) return;
  event.respondWith(networkFirstWithFallback(req));
});

self.addEventListener('sync', function (event) {
  if (event.tag === 'sync-prayer-requests') event.waitUntil(syncQueue('prayer_queue', '/api/prayer-requests'));
  if (event.tag === 'sync-contact-forms') event.waitUntil(syncQueue('contact_queue', '/api/contact'));
});

self.addEventListener('push', function (event) {
  if (!event.data) return;
  var data = event.data.json();
  event.waitUntil(self.registration.showNotification(data.title || 'Church Update', {
    body: data.body || '', icon: '/images/icon-192.png', data: { url: data.url || '/' }
  }));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  event.waitUntil(clients.openWindow(event.notification.data.url || '/'));
});

function isStaticAsset(url) {
  return url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com' ||
    url.hostname === 'cdnjs.cloudflare.com' ||
    /\.(css|js|woff2?|ttf|eot|ico|png|jpg|jpeg|gif|svg|webp)(\?.*)?$/.test(url.pathname);
}
function isDocument(url) { return /\.(pdf|epub|docx?)(\?.*)?$/.test(url.pathname); }
function isPublicApi(url) {
  return ['/api/verses/today','/api/blessings/today','/api/events/upcoming',
    '/api/posts/published','/api/posts/featured','/api/settings',
    '/api/settings/widgets/public','/api/announcements/active',
    '/api/churches','/api/prayer-requests/public','/api/ministries','/api/galleries'
  ].some(function(p) { return url.pathname.startsWith(p); });
}
function cacheFirst(req, cacheName) {
  return caches.open(cacheName).then(function(cache) {
    return cache.match(req).then(function(cached) {
      if (cached) return cached;
      return fetch(req).then(function(res) {
        if (res && res.status === 200) cache.put(req, res.clone());
        return res;
      });
    });
  });
}
function staleWhileRevalidate(req, cacheName, ttl) {
  return caches.open(cacheName).then(function(cache) {
    return cache.match(req).then(function(cached) {
      var fetchP = fetch(req).then(function(res) {
        if (res && res.status === 200) cache.put(req, res.clone());
        return res;
      });
      if (cached) {
        var age = cached.headers.get('date') ? (Date.now() - new Date(cached.headers.get('date')).getTime()) / 1000 : 0;
        if (age < ttl) return cached;
      }
      return fetchP;
    });
  });
}
function networkFirstWithFallback(req) {
  return fetch(req).then(function(res) {
    if (res && res.status === 200) caches.open(STATIC_CACHE).then(function(c) { c.put(req, res.clone()); });
    return res;
  }).catch(function() {
    return caches.match(req).then(function(c) {
      return c || caches.match('/').then(function(h) {
        return h || new Response('<h1>You are offline</h1>', { headers: {'Content-Type':'text/html'} });
      });
    });
  });
}
function syncQueue(queueKey, endpoint) {
  return caches.open('church-queue').then(function(cache) {
    return cache.match('/queue/' + queueKey).then(function(res) {
      if (!res) return;
      return res.json().then(function(items) {
        return Promise.all(items.map(function(item) {
          return fetch(endpoint, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN': item.csrf || ''},
            body: JSON.stringify(item.data)
          }).then(function(r) {
            if (r.ok) {
              var remaining = items.filter(function(i) { return i.id !== item.id; });
              cache.put('/queue/' + queueKey, new Response(JSON.stringify(remaining)));
            }
          });
        }));
      });
    });
  });
}
