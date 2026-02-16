/**
 * PhoneStock - JavaScript principal
 */

// =====================================================
// Theme Management (Dark Mode)
// =====================================================
function initTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.setAttribute('data-theme', 'dark');
        updateThemeIcon('dark');
    }
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
    const sunIcon = document.querySelector('.sun-icon');
    const moonIcon = document.querySelector('.moon-icon');

    if (sunIcon && moonIcon) {
        if (theme === 'dark') {
            sunIcon.style.display = 'none';
            moonIcon.style.display = 'block';
        } else {
            sunIcon.style.display = 'block';
            moonIcon.style.display = 'none';
        }
    }
}

// Initialize theme immediately
initTheme();

// =====================================================
// Main Initialization
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    // Re-check theme icons after DOM load
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    updateThemeIcon(currentTheme);

    // Fermeture automatique des alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Confirmation de suppression
    const deleteLinks = document.querySelectorAll('a[href*="delete"]');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                e.preventDefault();
            }
        });
    });

    // Validation des formulaires
    const forms = document.querySelectorAll('form:not(.ajax-search)');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            if (!valid) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs obligatoires.');
            }
        });
    });

    // Formatage des prix en temps réel
    const priceInputs = document.querySelectorAll('input[name="price"]');
    priceInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(2);
            }
        });
    });

    // Initialize AJAX search
    initAjaxSearch();

    // Initialize hide values toggle
    initHideValues();

    // Initialize compact mode
    initCompactMode();

    // Gestion des modales
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    };

    // Fermer modal en cliquant à l'extérieur
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });

    // Raccourcis clavier
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                modal.classList.remove('active');
            });
            // Close barcode scanner if open
            if (typeof closeBarcodeScanner === 'function') {
                closeBarcodeScanner();
            }
        }
    });
});

// =====================================================
// AJAX Search (Recherche instantanée)
// =====================================================
function initAjaxSearch() {
    const searchInput = document.querySelector('input[name="search"]');
    const searchForm = searchInput?.closest('form');

    if (!searchInput || !searchForm) return;

    let timeout;
    let abortController;

    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);

        const query = this.value.trim();
        const resultsContainer = document.querySelector('.table-container') ||
                                  document.querySelector('table')?.parentElement;

        if (!resultsContainer) return;

        // Show loading indicator
        searchInput.classList.add('loading');

        timeout = setTimeout(async () => {
            // Abort previous request
            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();

            try {
                const formData = new FormData(searchForm);
                const params = new URLSearchParams(formData);
                params.set('ajax', '1');

                const response = await fetch(`${searchForm.action || window.location.pathname}?${params}`, {
                    signal: abortController.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const html = await response.text();

                    // Parse and extract table content
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTable = doc.querySelector('.table-container') ||
                                     doc.querySelector('table')?.parentElement;

                    if (newTable) {
                        resultsContainer.innerHTML = newTable.innerHTML;
                    }

                    // Update URL without reload
                    const newUrl = `${window.location.pathname}?${params}`;
                    history.replaceState(null, '', newUrl.replace('&ajax=1', '').replace('ajax=1&', '').replace('ajax=1', ''));
                }
            } catch (e) {
                if (e.name !== 'AbortError') {
                    console.error('Search error:', e);
                }
            } finally {
                searchInput.classList.remove('loading');
            }
        }, 300);
    });
}

// =====================================================
// Hide/Show Values Toggle
// =====================================================
function initHideValues() {
    const hidden = localStorage.getItem('hide_values') === 'true';
    if (hidden) {
        applyHideValues(true);
    }
}

function toggleHideValues() {
    const isHidden = localStorage.getItem('hide_values') === 'true';
    const newState = !isHidden;
    localStorage.setItem('hide_values', newState);
    applyHideValues(newState);
}

function applyHideValues(hide) {
    document.querySelectorAll('.stat-value[data-monetary="true"]').forEach(el => {
        if (hide) {
            el.classList.add('value-hidden');
        } else {
            el.classList.remove('value-hidden');
        }
    });
    // Update button icon
    const btn = document.querySelector('.btn-toggle-values');
    if (btn) {
        btn.innerHTML = hide
            ? '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg> Afficher'
            : '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Masquer';
    }
}

// =====================================================
// Compact Mode Toggle
// =====================================================
function initCompactMode() {
    const compact = localStorage.getItem('table_compact') === 'true';
    if (compact) {
        applyCompactMode(true);
    }
}

function toggleCompactMode() {
    const isCompact = localStorage.getItem('table_compact') === 'true';
    const newState = !isCompact;
    localStorage.setItem('table_compact', newState);
    applyCompactMode(newState);
}

function applyCompactMode(compact) {
    document.querySelectorAll('table').forEach(table => {
        if (compact) {
            table.classList.add('table-compact');
        } else {
            table.classList.remove('table-compact');
        }
    });
    const btn = document.querySelector('.btn-compact-toggle');
    if (btn) {
        btn.classList.toggle('active', compact);
        btn.textContent = compact ? 'Normal' : 'Compact';
    }
}

// =====================================================
// Counter Animation
// =====================================================
function animateCounter(element, target, duration) {
    if (!element || isNaN(target)) return;
    const start = 0;
    const startTime = performance.now();
    const isMonetary = element.getAttribute('data-monetary') === 'true';
    const suffix = element.getAttribute('data-suffix') || '';

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        // Ease out quad
        const eased = 1 - (1 - progress) * (1 - progress);
        const current = Math.round(start + (target - start) * eased);

        const formatted = new Intl.NumberFormat('fr-FR').format(current);
        element.textContent = formatted + (suffix ? ' ' + suffix : '');

        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    requestAnimationFrame(update);
}

function initCounterAnimations() {
    document.querySelectorAll('.stat-value[data-target]').forEach(el => {
        const target = parseFloat(el.getAttribute('data-target'));
        if (!isNaN(target) && target > 0) {
            animateCounter(el, target, 800);
        }
    });
}

// =====================================================
// Barcode Scanner
// =====================================================
let barcodeStream = null;

async function openBarcodeScanner(callback) {
    // Check if already open
    if (document.getElementById('barcode-modal')) {
        return;
    }

    // Create modal
    const modal = document.createElement('div');
    modal.id = 'barcode-modal';
    modal.className = 'modal-overlay active';
    modal.innerHTML = `
        <div class="modal" style="max-width: 100%; height: 100%; max-height: 100%; border-radius: 0; display: flex; flex-direction: column;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="modal-title">Scanner un code-barres</h3>
                <button onclick="closeBarcodeScanner()" class="btn btn-sm btn-outline">Fermer</button>
            </div>
            <div style="flex: 1; position: relative; background: #000; overflow: hidden;">
                <video id="barcode-video" style="width: 100%; height: 100%; object-fit: cover;"></video>
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
                            width: 80%; max-width: 300px; height: 100px; border: 2px solid #22c55e;
                            border-radius: 8px; box-shadow: 0 0 0 9999px rgba(0,0,0,0.5);"></div>
            </div>
            <div style="padding: 1rem; text-align: center;">
                <p style="margin-bottom: 0.5rem;">Ou entrez le code manuellement :</p>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" id="manual-barcode" class="form-control" placeholder="Code-barres / IMEI">
                    <button onclick="submitManualBarcode()" class="btn btn-primary">OK</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    // Store callback
    window.barcodeCallback = callback;

    // Start camera
    try {
        const video = document.getElementById('barcode-video');
        barcodeStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' }
        });
        video.srcObject = barcodeStream;
        video.play();

        // Start scanning with QuaggaJS or manual detection
        startBarcodeDetection(video);
    } catch (err) {
        console.error('Camera error:', err);
        alert('Impossible d\'accéder à la caméra. Utilisez la saisie manuelle.');
    }
}

function closeBarcodeScanner() {
    const modal = document.getElementById('barcode-modal');
    if (modal) {
        modal.remove();
    }

    if (barcodeStream) {
        barcodeStream.getTracks().forEach(track => track.stop());
        barcodeStream = null;
    }

    // Stop Quagga if running
    if (typeof Quagga !== 'undefined') {
        try { Quagga.stop(); } catch(e) {}
    }
}

function submitManualBarcode() {
    const input = document.getElementById('manual-barcode');
    const code = input?.value.trim();

    if (code && window.barcodeCallback) {
        window.barcodeCallback(code);
        closeBarcodeScanner();
    }
}

function startBarcodeDetection(video) {
    // Use BarcodeDetector API if available (Chrome)
    if ('BarcodeDetector' in window) {
        const detector = new BarcodeDetector({
            formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'qr_code']
        });

        const detectFrame = async () => {
            if (!barcodeStream) return;

            try {
                const barcodes = await detector.detect(video);
                if (barcodes.length > 0) {
                    const code = barcodes[0].rawValue;
                    if (window.barcodeCallback) {
                        window.barcodeCallback(code);
                        closeBarcodeScanner();
                        return;
                    }
                }
            } catch (e) {}

            requestAnimationFrame(detectFrame);
        };

        detectFrame();
    }
}

function onBarcodeScanned(code) {
    // Default behavior: search for the code
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.value = code;
        searchInput.dispatchEvent(new Event('input'));
    }
}

// =====================================================
// Utility Functions
// =====================================================
function formatNumber(num) {
    return new Intl.NumberFormat('fr-FR').format(num);
}

function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
