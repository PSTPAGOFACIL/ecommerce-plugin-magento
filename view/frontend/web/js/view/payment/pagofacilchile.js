define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'pagofacilchile',
                component: 'PagoFacil_PagoFacilChile/js/view/payment/method-renderer/pagofacilchile'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
