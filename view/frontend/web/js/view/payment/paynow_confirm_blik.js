define([
    'jquery',
    'ko',
    'uiComponent'
], function ($, ko, Component) {
    'use strict';

    return Component.extend({
        initialize: function () {
            this._super();
            self = this;
            this.fetchNewData();
            this.INTERVAL = 5000;
            this.timeout = null;
            this.currentReq = null;
        },
        paymentStatus: ko.observable(""),
        orderId: ko.observable("000164"),
        orderStatus: ko.observable(""),

        fetchNewData : function () {
            self.currentReq = $.ajax({
                url: 'status' + window.location.search,
                dataType: 'json',
                type: 'get'});

            self.currentReq
                .done(self.processNewData)
                .always(self.scheduleNewDataFetch);
        },

        processNewData: function (data) {
            self.paymentStatus(data.payment_status);
            self.orderStatus(data.order_status_label);
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
