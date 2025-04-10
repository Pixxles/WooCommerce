jQuery(function ($) {
    if (typeof pnVars === 'undefined') {
        console.warn('Initial variables of kount is undefined');
        return;
    }

    if (typeof Kount !== 'undefined' && typeof Kount.setup === 'function') {
        const kountConfig = {
            clientID: pnVars.merchant_id,
            sessionID: pnVars.session_id,
            environment: pnVars.environment,
            collectBrowserData: true,
            isSinglePageApp: false,
            isDebugEnabled: true,
        };

        Kount.setup(kountConfig);
        console.log('Kount initialized with session:', pnVars.session_id);
    } else {
        console.warn('Kount.setup is not available');
    }
});