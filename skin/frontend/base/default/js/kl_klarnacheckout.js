// Abstract(s)
function _klarnaCheckoutWrapper(callback) {
    if (typeof _klarnaCheckout != 'undefined') {
        _klarnaCheckout(function(api) {
            api.suspend();
            typeof callback === 'function' && callback(api);
        });
    }
}

// Helpers
function klarnaCheckoutSuspend() {
    _klarnaCheckoutWrapper();
}

function klarnaCheckoutResume() {
    _klarnaCheckoutWrapper(function(api) {
        api.resume();
    });
}

function klarnaCheckoutSetShippingMethod(code) {
    _klarnaCheckoutWrapper(function(api) {
        new Ajax.Request('/klarna/shipping', {
            method: 'POST',
            parameters: {
                shippingCode: code
            },
            onComplete: function(transport){
                api.resume();
            }
        });
    });
}

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