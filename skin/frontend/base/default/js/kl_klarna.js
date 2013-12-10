// Fetch address and populare social security number
function fetchKlarnaAddress(fieldId) {

    // Populate social security fields
    $$('.klarna_ssn').each(function(el) {
        $(el).value = $(fieldId).value;
    });

    // Check if Less Friction is used
    if( typeof Checkout !== 'undefined' ) {
        // @todo Trigger reload of elements
    }

}