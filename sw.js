// sw.js
const CACHE_NAME = 'techfix-v1';
const ASSETS_TO_CACHE = [
  '/TechFixPHP/',
  '/TechFixPHP/index.php',
  '/TechFixPHP/assets/css/home.css',
  // Thêm các file ảnh hoặc JS khác nếu cần
];

// 1. Cài đặt Service Worker và Cache file
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[Service Worker] Đang cache tài nguyên...');
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
});

// 2. Kích hoạt và xóa cache cũ nếu có update
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(
        keyList.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
});

// 3. Chặn request để phục vụ Offline
self.addEventListener('fetch', (event) => {
  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request);
    })
  );
});