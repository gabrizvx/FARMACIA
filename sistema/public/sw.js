'use strict';

const CACHE_PREFIX = 'farm-sjb-static-';
const CACHE_NAME = `${CACHE_PREFIX}v2`;
const STATIC_ASSETS = [
  '/offline.html',
  '/manifest.webmanifest',
  '/css/bootstrap.css',
  '/css/bootstrap-icons.min.css',
  '/css/custom.css',
  '/css/fonts/bootstrap-icons.woff2',
  '/fonts/Montserrat-Regular.woff2',
  '/js/bootstrap.bundle.min.js',
  '/js/app.js',
  '/js/chart.umd.min.js',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  '/icons/icon-512-maskable.png',
  '/icons/apple-touch-icon.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(CACHE_NAME);
    await cache.addAll(STATIC_ASSETS);
    await self.skipWaiting();
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const cacheNames = await caches.keys();
    await Promise.all(
      cacheNames
        .filter((name) => name.startsWith(CACHE_PREFIX) && name !== CACHE_NAME)
        .map((name) => caches.delete(name))
    );
    await self.clients.claim();
  })());
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(async () => {
      const cache = await caches.open(CACHE_NAME);
      const offlinePage = await cache.match('/offline.html');
      if (!offlinePage) {
        throw new Error('No se encontró la página sin conexión almacenada.');
      }

      return offlinePage;
    }));
    return;
  }

  if (!STATIC_ASSETS.includes(url.pathname)) return;

  event.respondWith((async () => {
    const cache = await caches.open(CACHE_NAME);
    const cacheKey = new URL(url.pathname, self.location.origin).href;

    try {
      const response = await fetch(request);
      if (response.ok && response.type === 'basic') {
        await cache.put(cacheKey, response.clone());
      }

      return response;
    } catch (error) {
      const cachedResponse = await cache.match(cacheKey);
      if (cachedResponse) return cachedResponse;

      throw error;
    }
  })());
});
