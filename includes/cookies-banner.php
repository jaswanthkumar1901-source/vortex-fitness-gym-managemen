
<div id="cookie-banner"
     class="cookie-banner"
     role="region"
     aria-labelledby="cookie-title"
     aria-describedby="cookie-description"
     hidden>

    <div class="cookie-banner__content">
        <div class="cookie-banner__icon" aria-hidden="true">🍪</div>

        <div class="cookie-banner__text">
            <h2 id="cookie-title">Your privacy matters</h2>
            <p id="cookie-description">
                Essential storage supports website functionality.
                Choose whether to allow optional analytics storage.
            </p>

            <a href="/gym-management/privacy-policy.php">Privacy Policy</a>
            <span aria-hidden="true"> · </span>
            <a href="/gym-management/cookies-policy.php">Cookie Policy</a>
        </div>
    </div>

    <div class="cookie-banner__actions">
        <button type="button" id="cookie-reject"
                class="cookie-btn cookie-btn--secondary">
            Reject optional
        </button>

        <button type="button" id="cookie-settings"
                class="cookie-btn cookie-btn--secondary">
            Settings
        </button>

        <button type="button" id="cookie-accept"
                class="cookie-btn cookie-btn--primary">
            Accept all
        </button>
    </div>
</div>

<div id="cookie-settings-panel"
     class="cookie-settings-panel"
     role="dialog"
     aria-labelledby="cookie-settings-title"
     aria-modal="true"
     hidden>

    <div class="cookie-settings-card">
        <h2 id="cookie-settings-title">Cookie preferences</h2>
        <p>Choose whether to allow optional analytics.</p>

        <label class="cookie-option">
            <span>
                <strong>Essential storage</strong>
                <small>Always enabled for core website functions.</small>
            </span>
            <input type="checkbox" checked disabled>
        </label>

        <label class="cookie-option">
            <span>
                <strong>Analytics</strong>
                <small>Optional usage measurement, if implemented.</small>
            </span>
            <input type="checkbox" id="analytics-consent">
        </label>

        <div class="cookie-settings-actions">
            <button type="button" id="cookie-save-settings"
                    class="cookie-btn cookie-btn--primary">
                Save preferences
            </button>

            <button type="button" id="cookie-close-settings"
                    class="cookie-btn cookie-btn--secondary">
                Cancel
            </button>
        </div>
    </div>
</div>
