define([
    'jquery',
    'ko',
    'uiComponent',
    'underscore',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/customer-data',
    'mage/url'
], function ($, ko, Component, _, customer, customerData, urlBuilder) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'SoftLoft_ConsentManagementSystem/privacy_policy'
        },
        marketingDisplayText: ko.observable(''),
        privacyPolicy: ko.observable(''),
        consentPrivacyVersion: ko.observable(''),
        acceptedCurrentVersion: ko.observable(false),
        isVisible: ko.observable(false),

        initialize: function () {
           this._super();
           this.checkCustomerConsentInformation();
        },

        allowDisplayConsent: function () {
            return this.isVisible();
        },

        getMarketingDisplayText: function () {
            return this.marketingDisplayText;
        },

        getPrivacyPolicy: function () {
            return this.privacyPolicy;
        },

        getConsentPrivacyVersion: function () {
            return this.consentPrivacyVersion;
        },

        checkCustomerConsentInformation: function () {
            var self = this;
            let mobile;
            let email = customer.customerData.email;
            let ref_id = customer.customerData.id;
            let custome_attributes = customer.customerData.custom_attributes;
            mobile = (custome_attributes !== undefined && custome_attributes.telephone !== undefined) ?
                custome_attributes.telephone.value : null;

            $.ajax({
                url: urlBuilder.build('consent/ajax/checkCustomerInformation'),
                type: 'POST',
                data: {
                    'email': email,
                    'mobile': mobile,
                    'ref_id': ref_id
                },
                success: function (response) {
                    if (response.status !== undefined || response.message !== undefined) {
                        self.isVisible(false);
                        console.log("Something went wrong");
                    } else {
                        if (response.consent_privacy_status === true) {
                            self.isVisible(false);
                            self.marketingDisplayText(response.consent_marketing_status);
                            self.consentPrivacyVersion(response.consent_privacy_version);
                            self.acceptedCurrentVersion(true);
                        } else {
                            self.isVisible(true);
                            let storeCodeChosen = window.checkoutConfig.storeCode;

                            /**
                             * This function supports getting the value compatible with each store
                             * @param obj
                             * @param condition
                             * @returns {string}
                             */
                            function filterByCondition(obj, condition)
                            {
                                let result = '';
                                $.each(obj, function (key, value) {
                                    if (key == condition) {
                                        result = value;
                                        return false;
                                    }
                                });
                                return result;
                            }

                            let content = response.content;
                            if (storeCodeChosen) {
                                self.marketingDisplayText(filterByCondition(content.marketing_display_text, storeCodeChosen));
                                self.privacyPolicy(filterByCondition(content.privacy_policy, storeCodeChosen));
                            }
                            self.consentPrivacyVersion(content.consent_privacy_version);
                            self.acceptedCurrentVersion(false);
                        }
                    }
                },
                error: function (response) {
                    self.isVisible(false);
                    console.log("Something went wrong");
                }
            });
        },
    });
});