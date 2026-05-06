/**
 * JM SEO Agent Dashboard JavaScript
 */

(function() {
    'use strict';

    window.jmSeo = window.jmSeo || {};

    /**
     * Start manual scan
     */
    window.jmSeoStartScan = function() {
        if (!confirm('Lancer un scan complet du site? Cela peut prendre du temps.')) {
            return;
        }

        const button = document.getElementById('jm-seo-start-scan-btn');
        const modal = document.getElementById('jm-seo-scan-modal');
        
        if (button) {
            button.disabled = true;
            button.textContent = 'Scan en cours...';
        }

        fetch(jmSeoAjax.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-WP-Nonce': jmSeoAjax.nonce
            },
            body: new URLSearchParams({
                action: 'jm_seo_start_scan',
                nonce: jmSeoAjax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Scan démarré:', data.data.scan_id);
                if (modal) {
                    modal.style.display = 'block';
                    jmSeo.monitorScan(data.data.scan_id, modal);
                }
            } else {
                alert('Erreur: ' + (data.data?.message || 'Impossible de démarrer le scan'));
                if (button) {
                    button.disabled = false;
                    button.textContent = 'Lancer un scan';
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur réseau');
            if (button) {
                button.disabled = false;
                button.textContent = 'Lancer un scan';
            }
        });
    };

    /**
     * Monitor scan progress
     */
    jmSeo.monitorScan = function(scanId, modalElement) {
        const interval = setInterval(() => {
            fetch(jmSeoAjax.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-WP-Nonce': jmSeoAjax.nonce
                },
                body: new URLSearchParams({
                    action: 'jm_seo_scan_status',
                    scan_id: scanId,
                    nonce: jmSeoAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    clearInterval(interval);
                    return;
                }

                const status = data.data;
                const progressBar = modalElement.querySelector('.jm-seo-progress-bar');
                const progressText = modalElement.querySelector('.jm-seo-progress-text');
                const total = status.queue_remaining + status.pages_scanned;
                const progress = total > 0 ? Math.round((status.pages_scanned / total) * 100) : 0;

                if (progressBar) {
                    progressBar.style.width = progress + '%';
                }
                if (progressText) {
                    progressText.textContent = status.pages_scanned + ' / ' + total + ' pages analysées';
                }

                if (status.status === 'finished') {
                    clearInterval(interval);
                    alert('Scan terminé!');
                    if (modalElement) {
                        modalElement.style.display = 'none';
                    }
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                clearInterval(interval);
            });
        }, 2000); // Check every 2 seconds
    };

    /**
     * Close modal
     */
    window.jmSeoCloseModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    };

    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        // Close modal on click outside
        window.onclick = function(event) {
            const modal = document.getElementById('jm-seo-scan-modal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
    });
})();
