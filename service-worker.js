// Service Worker para PWA - Cardápio Digital
const CACHE_NAME = 'cardapio-v1';

self.addEventListener('install', (event) => {
    console.log('[SW] Instalando...');
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    console.log('[SW] Ativado!');
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Ignorar requisições POST (como upload de imagens ou forms)
    if (event.request.method === 'POST') {
        return;
    }

    // Estratégia simples: Network first, fallback para cache
    event.respondWith(
        fetch(event.request)
            .then(response => {
                // Se sucesso, salva no cache
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, clone);
                    });
                }
                return response;
            })
            .catch(() => {
                // Se falha, tenta do cache
                return caches.match(event.request);
            })
    );
});

console.log('[SW] Carregado!');