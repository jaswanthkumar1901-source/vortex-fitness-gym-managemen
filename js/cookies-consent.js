
(() => {
    'use strict';

    const STORAGE_KEY = 'vortex_cookie_consent';
    const POLICY_VERSION = '1.0';

    const banner = document.getElementById('cookie-banner');
    const panel = document.getElementById('cookie-settings-panel');

    if (!banner || !panel) return;

    const analyticsCheckbox = document.getElementById('analytics-consent');

    const getConsent = () => {
        try {
            const value = localStorage.getItem(STORAGE_KEY);
            return value ? JSON.parse(value) : null;
        } catch (error) {
            return null;
        }
    };

    const saveConsent = (analytics) => {
        const consent = {
            essential: true,
            analytics: Boolean(analytics),
            policyVersion: POLICY_VERSION,
            savedAt: new Date().toISOString()
        };

        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(consent));
        } catch (error) {
            console.error('Unable to save cookie preferences.', error);
        }

        banner.hidden = true;
        panel.hidden = true;

        // Do not load analytics scripts here unless they have been
        // explicitly integrated and consent has been granted.
        window.dispatchEvent(new CustomEvent('vortex:cookie-consent-changed', {
            detail: consent
        }));
    };

    const openCookiePreferences = () => {
        const consent = getConsent();

        analyticsCheckbox.checked = Boolean(consent?.analytics);
        panel.hidden = false;
        banner.hidden = true;

        document.getElementById('cookie-save-settings').focus();
    };

    window.openCookiePreferences = openCookiePreferences;

    document.getElementById('cookie-accept').addEventListener('click', () => {
        saveConsent(true);
    });

    document.getElementById('cookie-reject').addEventListener('click', () => {
        saveConsent(false);
    });

    document.getElementById('cookie-settings').addEventListener('click', () => {
        openCookiePreferences();
    });

    document.getElementById('cookie-save-settings').addEventListener('click', () => {
        saveConsent(analyticsCheckbox.checked);
    });

    document.getElementById('cookie-close-settings').addEventListener('click', () => {
        panel.hidden = true;

        if (!getConsent()) {
            banner.hidden = false;
            document.getElementById('cookie-settings').focus();
        }
    });

    panel.addEventListener('click', (event) => {
        if (event.target === panel) {
            panel.hidden = true;

            if (!getConsent()) {
                banner.hidden = false;
            }
        }
    });

    const existingConsent = getConsent();

    if (!existingConsent || existingConsent.policyVersion !== POLICY_VERSION) {
        banner.hidden = false;
    } else {
        banner.hidden = true;
    }
})();
