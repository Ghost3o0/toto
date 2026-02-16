    </main>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> Mystate - Gestion de Stock de Téléphones</p>
    </footer>

    <?php if (isLoggedIn()): ?>
    <!-- Chatbot Widget -->
    <button id="chatbot-btn" class="chatbot-btn" title="Aide">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>
    <div id="chatbot-window" class="chatbot-window">
        <div class="chatbot-header">
            <h4>Assistant Mystate</h4>
            <button class="chatbot-close" title="Fermer">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="chatbot-messages">
            <div class="chatbot-msg bot">Bonjour ! Je suis l'assistant Mystate. Posez-moi une question ou choisissez un sujet ci-dessous.</div>
        </div>
        <div class="chatbot-suggestions"></div>
        <div class="chatbot-input">
            <input type="text" placeholder="Posez votre question...">
            <button>Envoyer</button>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/chatbot.js"></script>
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
