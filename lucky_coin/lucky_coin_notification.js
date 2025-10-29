/**
 * Lucky Coin Notification - Global Script
 * Tự động hiển thị red dot trên icon Map khi có xu may mắn
 * Include script này vào tất cả các trang
 */

(function() {
    'use strict';

    // Configuration
    const POLL_INTERVAL = 60000; // 60 seconds
    const API_ENDPOINT = '/lucky_coin/getLuckyCoins.php';
    
    let pollInterval = null;

    /**
     * Update red dot notification
     * @param {number} unpickedCount - Number of coins user hasn't picked yet
     */
    function updateCoinNotification(unpickedCount) {
        // Find all map nav items (có thể có nhiều nếu có nhiều bottom-nav)
        const mapNavIcons = document.querySelectorAll('.nav-item .nav-icon');
        
        mapNavIcons.forEach(icon => {
            // Check if this is the map icon (contains 🗺️)
            if (icon.textContent.includes('🗺️')) {
                // Remove existing dot if any
                let dot = icon.querySelector('#coin-notification-dot');
                
                if (unpickedCount > 0) {
                    // Show red dot (has unpicked coins)
                    if (!dot) {
                        // Create dot
                        dot = document.createElement('span');
                        dot.id = 'coin-notification-dot';
                        dot.style.cssText = `
                            display: block;
                            position: absolute;
                            top: -2px;
                            right: -2px;
                            width: 8px;
                            height: 8px;
                            background: red;
                            border-radius: 50%;
                            border: 1px solid white;
                            box-shadow: 0 0 4px rgba(255, 0, 0, 0.6);
                        `;
                        
                        // Ensure icon has relative positioning
                        if (!icon.style.position || icon.style.position === 'static') {
                            icon.style.position = 'relative';
                        }
                        
                        icon.appendChild(dot);
                    } else {
                        dot.style.display = 'block';
                    }
                } else {
                    // Hide red dot (no unpicked coins or all picked)
                    if (dot) {
                        dot.style.display = 'none';
                    }
                }
            }
        });
    }

    /**
     * Fetch lucky coins and count unpicked ones
     */
    async function checkLuckyCoins() {
        try {
            const response = await fetch(API_ENDPOINT);
            
            if (!response.ok) {
                console.error('Failed to fetch lucky coins:', response.status);
                return;
            }
            
            const data = await response.json();
            
            if (data.success && data.coins) {
                // Count coins that user HASN'T picked yet
                const unpickedCount = data.coins.filter(coin => !coin.already_picked).length;
                updateCoinNotification(unpickedCount);
            } else {
                // No coins or error - hide notification
                updateCoinNotification(0);
            }
        } catch (error) {
            console.error('Lucky coin check error:', error);
            // Don't show error to user, just fail silently
        }
    }

    /**
     * Start polling
     */
    function startPolling() {
        checkLuckyCoins(); // Initial check
        pollInterval = setInterval(checkLuckyCoins, POLL_INTERVAL);
    }

    /**
     * Stop polling
     */
    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    /**
     * Handle page visibility change
     */
    function handleVisibilityChange() {
        if (document.hidden) {
            stopPolling();
        } else {
            startPolling();
        }
    }

    /**
     * Initialize
     */
    function init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', startPolling);
        } else {
            startPolling();
        }

        // Handle visibility changes
        document.addEventListener('visibilitychange', handleVisibilityChange);

        // Cleanup on page unload
        window.addEventListener('beforeunload', stopPolling);
    }

    // Auto-initialize
    init();

    // Expose functions globally if needed
    window.LuckyCoinNotification = {
        check: checkLuckyCoins,
        start: startPolling,
        stop: stopPolling
    };

})();
