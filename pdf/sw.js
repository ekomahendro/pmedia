const CACHE_NAME = 'ebook-dash-v1';
const assets = [
  'login.php',
  'manifest.json',
  'logo.png'
];

// Event saat Service Worker diinstal
self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(assets);
    })
  );
});

// Event saat aplikasi mengambil data (Fetch)
self.addEventListener('fetch', e => {
  e.respondWith(
    fetch(e.request).catch(() => caches.match(e.request))
  );
});