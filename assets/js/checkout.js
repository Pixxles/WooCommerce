jQuery(function ($) {
    if (typeof pnVars === 'undefined') {
        console.warn('Kount variables is not available');
        return;
    }

    if (typeof Kount !== 'undefined' && typeof Kount.setup === 'function') {
        const kountConfig = {
            clientID: pnVars.kount_merchant_id,
            sessionID: pnVars.session_id,
            environment: pnVars.environment,
            collectBrowserData: true,
            isSinglePageApp: false,
        };

        Kount.setup(kountConfig);
    } else {
        console.warn('Kount is not available');
    }
});