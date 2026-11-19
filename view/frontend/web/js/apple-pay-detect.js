/*browser:true*/
/*global define*/
define([], function () {
    'use strict';

    let applePayEnabled = false;

    try {
        applePayEnabled = Boolean(window.ApplePaySession && window.ApplePaySession.canMakePayments());
    } catch (e) {
        // canMakePayments throws in cross-origin iframes
    }

    // set on every page, so the cookie exists before the server builds checkout config
    document.cookie = 'applePayEnabled=' + (applePayEnabled ? '1' : '0') + '; path=/; SameSite=Lax';
});
