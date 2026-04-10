/**
 * NepalPay Shared Utilities
 * Debug logging, biller mappings, common functions
 */

window.NepalPayUtils = {
    // Biller mappings: ID → Provider name for API calls
    billerMap: {
        '1': 'NEA',
        '2': 'NCELL', 
        '3': 'WORLDLINK',
        '4': 'NTC',
        '5': 'SMARTCELL'
    },

    // Provider icons
    providerIcons: {
        'NEA': '⚡',
        'NCELL': '📱',
        'WORLDLINK': '🌐',
        'NTC': '📞',
        'SMARTCELL': '📶',
        'KUKL': '💧'
    },

    // Enhanced Debug Logger (improved from pay.php)
    DebugLog: {
        logs: [],
        maxLogs: 200,
        
        log: function(event, level, message, data = {}) {
            const timestamp = new Date().toISOString();
            const logEntry = {
                timestamp, event, level, message, data,
                url: window.location.pathname,
                session: NepalPayUtils.getSessionId()
            };
            
            this.logs.push(logEntry);
            if (this.logs.length > this.maxLogs) this.logs.shift();
            
            const styles = {
                'DEBUG': 'color: #6b7280; font-weight: bold;',
                'INFO': 'color: #0891b2; font-weight: bold;',
                'WARNING': 'color: #f59e0b; font-weight: bold;',
                'ERROR': 'color: #dc2626; font-weight: bold;',
                'SUCCESS': 'color: #10b981; font-weight: bold;'
            };
            
            console.groupCollapsed(`%c[${timestamp.slice(11,23)}] [${level}] ${event}`, styles[level] || '');
            console.log(message);
            console.table(data);
            console.groupEnd();
        },
        
        info: function(event, message, data) { this.log(event, 'INFO', message, data); },
        debug: function(event, message, data) { this.log(event, 'DEBUG', message, data); },
        warn: function(event, message, data) { this.log(event, 'WARNING', message, data); },
        error: function(event, message, data) { this.log(event, 'ERROR', message, data); },
        success: function(event, message, data) { this.log(event, 'SUCCESS', message, data); },
        
        buttonClick: function(id, text, data = {}) {
            this.info('BUTTON_CLICK', `${id}: ${text}`, { 
                button_id: id, button_text: text, ...data 
            });
        },
        
        getLogs: function() { return this.logs; },
        exportLogs: function() { 
            return JSON.stringify(this.logs, null, 2); 
        }
    },

    // Session helper
    getSessionId: function() {
        return document.cookie.match(/PHPSESSID=([^;]+)/)?.[1] || 'anonymous';
    },

    // Animate element (fade in/out)
    animate: function(el, type = 'fadeIn', duration = 300) {
        el.style.transition = `opacity ${duration}ms ease`;
        el.style.opacity = type === 'fadeIn' ? '1' : '0';
        if (type === 'slideUp') el.style.transform = 'translateY(0)';
    }
};

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('%c🇳🇵 NepalPay Utils loaded', 'color: #ef4444; font-size: 16px; font-weight: bold;');
    window.DebugLog = NepalPayUtils.DebugLog; // Backward compat
});
