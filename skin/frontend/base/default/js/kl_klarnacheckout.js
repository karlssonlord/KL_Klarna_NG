function klarnaCheckoutSwitchPayment(code)
{
    window._klarnaCheckout(function (api) {
        // Pause the checkout
        api.suspend();

        // Update the shipping method
        new Ajax.Request('/klarna/shipping', {
            method: 'POST',
            parameters: {
                shippingCode: code
            },
            onSuccess: function(transport){
                // Resume the checkout
                api.resume();
            }
        });

    });
}