    </main>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> Mystate - Gestion de Stock de Téléphones</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        // Enregistrement du Service Worker pour PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('SW enregistré'))
                    .catch(err => console.log('SW erreur:', err));
            });
        }
    </script>
</body>
</html>
