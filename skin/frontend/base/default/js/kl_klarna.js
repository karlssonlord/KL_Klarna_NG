function updateKlarnaPaymentMethods(fieldId) {
    // Populate social security fields with the new social security number
    $$('.klarna_ssn').each(function(el) {
        $(el).value = $(fieldId).value;
    });
}

// Fetch address and populare social security number
function fetchKlarnaAddress(fieldId) {
    updateKlarnaPaymentMethods(fieldId);

    // Setup query url
    var queryUrl = addressEndpoint + 'ssn/' + $(fieldId).value;

    // Query endpoint for fetching address
    new Ajax.Request(queryUrl, {
        method:'get',
        onSuccess: function(transport){

            // Fix the returned data
            var json = transport.responseText.evalJSON();

            // Setup empty variables that we will use
            var hiddenAddressFields = '';
            var selectBox = '';
            var checked = true;

            if(json.error) {
                if(typeof window.fetchKlarnaAddressErrorHandler) {
                    fetchKlarnaAddressErrorHandler(json);
                }
                return;
            }

            $(json).each(function(address){

                // Setup the address line
                var addressLine =
                    (address.fname && address.lname ? address.fname + ' ' + address.lname + klarnaAddressGlue : '') +
                    address.street + klarnaAddressGlue +
                    address.zip + ' ' + address.city;

                // Build the hidden address fields with hash
                $H(address).each(function(addressLine) {
                    if (addressLine.key !== 'hash') {
                        hiddenAddressFields = hiddenAddressFields +'<input type="hidden" id="' + address.hash + '_' + addressLine.key + '" value="' + addressLine.value + '"/>';
                    }
                });

                // Setup the checkboxes with addresses
                selectBox += '<input onclick="klarnaChangeAddress();" class="klarna-address-radio" type="radio" id="klarna_address_key_' + address.hash + '" name="klarna_address_key" value="' + address.hash + '"';
                if (checked === true) {
                    selectBox += ' checked="checked"';
                    checked = false;
                }
                selectBox += '/>';
                selectBox += ' <label for="klarna_address_key_' + address.hash + '" class="klarna-address-label">'+addressLine+'</label>';

            });

            // Set the HTML fields
            $('klarna_select_address').update(hiddenAddressFields + selectBox);

            // Set the checked address
            klarnaChangeAddress();

            // Check how many addresses that were returned
            if (json.length == 1) {
                // Only one, no need for end user to select, populate using the one we found
            }

        }
    });

}

function klarnaChangeAddress()
{
    // Fetch address hash
    var addressHash = $$('input:checked[type=radio][name=klarna_address_key]')[0].value;

    // Setup query url
    var queryUrl = updateAddressEndpoint + 'address_key/' + addressHash;

    // Compose array with address data
    var magentoAddress = {
        'shipping[firstname]': $(addressHash + '_fname').value,
        'shipping[lastname]':  $(addressHash + '_lname').value,
        'shipping[street][]':  $(addressHash + '_street').value,
        'shipping[postcode]':  $(addressHash + '_zip').value,
        'shipping[city]':      $(addressHash + '_city').value
    };

    // Check if Less Friction is used
    if (typeof Checkout !== 'undefined' &&
        typeof shippingAddress !== 'undefined' &&
        typeof shippingAddress.inject === 'function'
    ) {
        // Inject shipping address
        shippingAddress.inject($H(magentoAddress));
        billingAddress.inject($H(magentoAddress));
    } else {
        $H(magentoAddress).each(function (pair) {
            $$('[name="' + pair.key + '"]').first().setValue(pair.value);
        });
    }

    // Query endpoint for updating address
    new Ajax.Request(queryUrl, {
        method:'get',
        onSuccess: function(transport){
            // What should we do when the quote is updated?
        }
    });
}
