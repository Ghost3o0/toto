/**
 * PhoneStock - Service Worker pour PWA
 */

const CACHE_NAME = 'phonestock-v1';
const OFFLINE_URL = '/offline.html';

// Fichiers à mettre en cache
const STATIC_ASSETS = [
    '/',
    '/assets/css/style.css',
    '/assets/js/main.js',
    '/manifest.json',
    '/offline.html',
    'https://cdn.jsdelivr.net/npm/chart.js'
];

// Installation du Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Cache ouvert');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activation et nettoyage des anciens caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Suppression ancien cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Stratégie de cache : Network First avec fallback
self.addEventListener('fetch', (event) => {
    // Ignorer les requêtes non-GET
    if (event.request.method !== 'GET') {
        return;
    }

    // Ignorer les requêtes vers d'autres domaines (sauf CDN autorisés)
    const url = new URL(event.request.url);
    const allowedHosts = [self.location.host, 'cdn.jsdelivr.net'];

    if (!allowedHosts.some(host => url.host.includes(host))) {
        return;
    }

    event.respondWith(
        // Essayer le réseau en premier
        fetch(event.request)
            .then((response) => {
                // Cloner la réponse car elle ne peut être utilisée qu'une fois
                const responseClone = response.clone();

                // Mettre en cache les réponses valides
                if (response.status === 200) {
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }

                return response;
            })
            .catch(() => {
                // Si le réseau échoue, chercher dans le cache
                return caches.match(event.request)
                    .then((cachedResponse) => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }

                        // Si c'est une page HTML, retourner la page offline
                        if (event.request.headers.get('accept').includes('text/html')) {
                            return caches.match(OFFLINE_URL);
                        }

                        // Sinon, retourner une réponse vide
                        return new Response('', {
                            status: 503,
                            statusText: 'Service Unavailable'
                        });
                    });
            })
    );
});

// Gestion des messages (pour sync en arrière-plan)
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// Synchronisation en arrière-plan (quand la connexion revient)
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-data') {
        event.waitUntil(syncOfflineData());
    }
});

// Fonction pour synchroniser les données hors-ligne
async function syncOfflineData() {
    try {
        const db = await openIndexedDB();
        const pendingActions = await getAllPendingActions(db);

        for (const action of pendingActions) {
            try {
                const response = await fetch(action.url, {
                    method: action.method,
                    headers: action.headers,
                    body: action.body
                });

                if (response.ok) {
                    await deletePendingAction(db, action.id);
                }
            } catch (error) {
                console.error('Erreur sync:', error);
            }
        }
    } catch (error) {
        console.error('Erreur sync globale:', error);
    }
}

// IndexedDB pour stocker les actions hors-ligne
function openIndexedDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('PhoneStockOffline', 1);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('pendingActions')) {
                db.createObjectStore('pendingActions', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

function getAllPendingActions(db) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction('pendingActions', 'readonly');
        const store = transaction.objectStore('pendingActions');
        const request = store.getAll();

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
    });
}

function deletePendingAction(db, id) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction('pendingActions', 'readwrite');
        const store = transaction.objectStore('pendingActions');
        const request = store.delete(id);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve();
    });
}
