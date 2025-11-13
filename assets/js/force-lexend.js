/**
 * Force Lexend Font for All Dropdowns
 * This script ensures all select elements and their options use Lexend font
 * Includes special handling for Firefox
 */

// Detect Firefox
const isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;

// Run immediately when DOM loads
document.addEventListener('DOMContentLoaded', function() {
    forceLexendFont();
    
    // Watch for dynamically added elements
    const observer = new MutationObserver(function(mutations) {
        forceLexendFont();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Firefox specific: force reapply on focus
    if (isFirefox) {
        document.addEventListener('focus', function(e) {
            if (e.target.tagName === 'SELECT') {
                forceLexendOnElement(e.target);
            }
        }, true);
    }
});

function forceLexendOnElement(element) {
    element.style.fontFamily = "'Lexend', sans-serif";
    element.style.setProperty('font-family', "'Lexend', sans-serif", 'important');
    
    // Firefox needs explicit style on each option
    if (element.tagName === 'SELECT') {
        const options = element.querySelectorAll('option');
        options.forEach(function(option) {
            option.style.fontFamily = "'Lexend', sans-serif";
            option.style.setProperty('font-family', "'Lexend', sans-serif", 'important');
        });
    }
}

function forceLexendFont() {
    // Force Lexend on all select elements
    const selects = document.querySelectorAll('select');
    selects.forEach(function(select) {
        forceLexendOnElement(select);
    });
    
    // Force Lexend on all option elements (including orphaned ones)
    const allOptions = document.querySelectorAll('option');
    allOptions.forEach(function(option) {
        option.style.fontFamily = "'Lexend', sans-serif";
        option.style.setProperty('font-family', "'Lexend', sans-serif", 'important');
        
        // Firefox extra: set via attribute
        if (isFirefox) {
            option.setAttribute('style', 'font-family: "Lexend", sans-serif !important;');
        }
    });
    
    // Force Lexend on all input elements
    const inputs = document.querySelectorAll('input, textarea, button');
    inputs.forEach(function(input) {
        input.style.fontFamily = "'Lexend', sans-serif";
        input.style.setProperty('font-family', "'Lexend', sans-serif", 'important');
    });
}

// Also run immediately for existing elements
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', forceLexendFont);
} else {
    forceLexendFont();
}

// Force reapply every 500ms for first 3 seconds (catches late-loaded content)
let reapplyCount = 0;
const reapplyInterval = setInterval(function() {
    forceLexendFont();
    reapplyCount++;
    if (reapplyCount >= 6) {
        clearInterval(reapplyInterval);
    }
}, 500);
