/**
 * FOUC (Flash of Unstyled Content) Prevention
 * This script applies the saved theme BEFORE the DOM is fully loaded
 * Execute this synchronously in the <head> to prevent theme flash
 */
(function() {
    'use strict';
    
    // Try to get theme from localStorage first (fastest)
    let theme = localStorage.getItem('campushub-theme');
    
    // If no saved theme, check system preference
    if (!theme) {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            theme = 'dark';
        } else {
            theme = 'light';
        }
    }
    
    // Apply theme immediately to prevent flash
    if (theme) {
        document.documentElement.setAttribute('data-theme', theme);
    }
})();
