define([
    'jquery'
], function ($) {
    'use strict';
    return function (target) {
        $.validator.addMethod(
            'validate-api-credentials',
            function (value) {
                let thisRegex = new RegExp(/^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$/);
                return thisRegex.test(value);
            },
            $.mage.__('Incorrect API/Signature key format.')
        );
        return target;
    };
});