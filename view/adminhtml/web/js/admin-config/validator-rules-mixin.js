define([
    'jquery'
], function ($) {
    'use strict';
    return function (target) {
        $.validator.addMethod(
            'validate-api-credentials',
            function (value) {
                let thisRegex = new RegExp('/^[[:xdigit:]]{8}(?:\-[[:xdigit:]]{4}){3}\-[[:xdigit:]]{12}$/i');
                return !(thisRegex.test(value));
            },
            $.mage.__('Incorrect API/Signature key format.')
        );
        return target;
    };
});