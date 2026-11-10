(function() {
    console.log('[Laravel PWA] Debug mode initialized.');

    window.laravelPwaDebug = {
        /**
         * List all caches and their contents
         */
        viewCaches: async function() {
            if (!('caches' in window)) {
                console.error('[Laravel PWA] Cache Storage is not supported.');
                return;
            }
            const keys = await caches.keys();
            console.log('[Laravel PWA] Caches:', keys);
            for (const key of keys) {
                const cache = await caches.open(key);
                const requests = await cache.keys();
                console.log(`[Laravel PWA] Cache "${key}" contains ${requests.length} items:`);
                requests.forEach(req => console.log(`  - ${req.url}`));
            }
        },

        /**
         * Clear all PWA caches
         */
        clearCaches: async function() {
            if (!('caches' in window)) return;
            const keys = await caches.keys();
            await Promise.all(keys.map(key => caches.delete(key)));
            console.log('[Laravel PWA] All caches cleared.');
        },

        /**
         * Force Service Worker update
         */
        forceUpdate: async function() {
            if (!('serviceWorker' in navigator)) return;
            const registration = await navigator.serviceWorker.getRegistration();
            if (registration) {
                console.log('[Laravel PWA] Forcing SW update check...');
                await registration.update();
                console.log('[Laravel PWA] Update check complete.');
            } else {
                console.warn('[Laravel PWA] No Service Worker registration found.');
            }
        },

        /**
         * Unregister Service Worker
         */
        unregister: async function() {
            if (!('serviceWorker' in navigator)) return;
            const registrations = await navigator.serviceWorker.getRegistrations();
            for (let registration of registrations) {
                await registration.unregister();
                console.log('[Laravel PWA] Service Worker unregistered.');
            }
        },

        /**
         * Help command
         */
        help: function() {
            console.log('[Laravel PWA] Debug Utilities:');
            console.table({
                'laravelPwaDebug.viewCaches()': 'List all caches and their contents',
                'laravelPwaDebug.clearCaches()': 'Clear all caches',
                'laravelPwaDebug.forceUpdate()': 'Force Service Worker update check',
                'laravelPwaDebug.unregister()': 'Unregister Service Worker',
                'laravelPwaDebug.help()': 'Show this help'
            });
        }
    };

    // Auto-log SW events if enabled
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            console.log('[Laravel PWA] Service Worker controller changed');
        });

        navigator.serviceWorker.getRegistration().then(reg => {
            if (reg) {
                reg.addEventListener('updatefound', () => {
                    console.log('[Laravel PWA] New Service Worker version found (installing...)');
                    const newWorker = reg.installing;
                    newWorker.addEventListener('statechange', () => {
                        console.log(`[Laravel PWA] SW state changed to: ${newWorker.state}`);
                    });
                });
            }
        });
    }

    // Call help by default
    window.laravelPwaDebug.help();
})();
