define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url'
    ],
    function (
        $,
        ko,
        Component,
        placeOrderAction,
        selectPaymentMethodAction,
        customer,
        checkoutData,
        additionalValidators,
        url
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'PagoFacil_PagoFacilChile/payment/pagofacilchile'
            },
            /** @inheritdoc */
            initialize: function () {
                this._super();
                this.metodosdepago = ko.observableArray([]);
                this.selectedPaymentCode = ko.observable();
                //busca metodos de pago
                var _this = this;
                $('body').loader('show');
                $.get(url.build('pagofacilchile/payment/paymentmethod'), function(data){
                    if (data && data.error_message && data.error_message !== "") {
                        alert(data.error_message);
                    } else if (data && data.types) {
                        for (var i = 0; i < data.types.length; i++) {
                            _this.metodosdepago.push({
                                codigo: data.types[i]['codigo'],
                                nombre: data.types[i]['nombre'],
                                descripcion: data.types[i]['descripcion'],
                                url_imagen: data.types[i]['url_imagen'],
                            });
                        }
                    }
                    $('body').loader('hide');
                });
            },
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this,
                    placeOrder,
                    emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                    $.when(placeOrder).done(function () {
                        self.isPlaceOrderActionAllowed(true);
                    }).done(this.afterPlaceOrder.bind(this));
                    console.info("place order TRUE");
                    return true;
                }
                console.info("place order FALSE");
                return false;
            },

            selectPaymentMethod: function(codigo) {
                this.selectedPaymentCode(codigo);
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
            },

            afterPlaceOrder: function() {
              console.log("after flace order");
              $('body').loader('show');
              $.get(url.build('pagofacilchile/payment/data'), function(data){
                if (data.error_message && data.error_message !== "") {
                    alert(data.error_message);
                } else if (data.url) {
                    window.location = data.url;
                }
                $('body').loader('hide');
              });
            },

            getLogoUrl: function() {
                return window.checkoutConfig.logoUrl;
            },

            getData: function () {
                //console.log(this.getCode());
                let codigo = this.selectedPaymentCode();
                let data = {
                    'method': this.getCode(),
                    'additional_data': {
                        'payment_method_subopt': codigo
                    }
                };
                //console.info(data);
                return data;
            },

            isOptionActive(codigo) {
                return codigo == this.selectedPaymentCode();
            },
            clickPaymentMethod(codigo) {
                let $elem = $("input[value='"+codigo+"']");
                if ($elem.length > 0) {
                    $elem.attr('checked', true);
                    this.selectPaymentMethod(codigo);
                }
            }

        });
    }
);
