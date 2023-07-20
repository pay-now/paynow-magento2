define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/url'
], function ($, ko, Component, url) {
    'use strict';
    return Component.extend({
        initialize: function (config) {
            url.setBaseUrl(ko.observable(config.baseUrl));
            this._super();
            self = this;
            this.INTERVAL = 5000;
            this.timeout = null;
            this.currentReq = null;
            this.paymentStatus = ko.observable(config.paymentStatus);
            this.paymentStatusLabel = ko.observable(config.paymentStatusLabel);
            this.paymentId =  ko.observable(config.paymentId);

            this.fetchNewData();
            setTimeout(() => {
                self.redirectToReturn(self.paymentStatus, self.paymentId)
            }, 60000);
        },
        redirectToReturn: function (paymentStatus, paymentId) {
            const successUrl = url.build('paynow/checkout/success');
            window.location.replace(successUrl + '?paymentStatus=' + paymentStatus + '&paymentId=' + paymentId);
        },
        fetchNewData : function () {
            self.currentReq = $.ajax({
                url:  url.build('paynow/payment/status'),
                dataType: 'json',
                type: 'get'});

            self.currentReq
                .done(self.processNewData)
                .always(self.scheduleNewDataFetch);
        },
        processNewData: function (data) {
            self.paymentStatus(data.payment_status);
            self.paymentId(data.paymentId);
            self.paymentStatusLabel(data.payment_status_label);

            if (!["PENDING", "NEW"].includes(data.payment_status)) {
                self.redirectToReturn(data.payment_status, data.paymentId);
            }
        },
        scheduleNewDataFetch: function () {
            if (self.currentReq) {
                self.currentReq.abort();
            }

            if (self.timeout) {
                clearTimeout(self.timeout);
            }

            self.currentReq = null;
            self.timeout = setTimeout(self.fetchNewData, self.INTERVAL);
        }
    });
});
