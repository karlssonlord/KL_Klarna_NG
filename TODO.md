# TODO

## Checkout

* Expose a field for national ID number in the billing information.
* Implement billing address search from national ID number (this is only
  supported for Swedish ID numbers!).
* Where address search is supported (Sweden), the user should not be allowed
  to enter/edit their billing address. Klarna will not authorize the payment
  if the address does not match the Swedish ID number!

## PClass storage

I'm not exactly sure what a PClass is, but they need to be stored. Right now
it defaults to JSON storage on disk, but it should be moved to the database.
This module should set up a database table for PClass storage and provide
the Klarna client with the details.

See `KL_Klarna_Model_Api_Request`, methods `getPclassStorage()` and
`getPclassStorageUri()`.

-- 
The pclass file, that is used to calculate the monthly cost for campaigns,
can be downloaded from Klarna Online (www.klarna.com). Go to "view store" 
in the menu on the left. In the store view, click "Click here to view 
campaigns" and download the pclass file as a php, asp, asp.net or java file.
--

## Partial captures and refunds

There is currently no support for partial captures and refunds. For this
payment method to work well within Magento.

For partial captures, it seems like a reservation must be "split" before
a partial capture - it's not as simple as just capturing a subset of the
items. See the Klarna API documentation for details.

## Shipping and handling fees

There is no support for shipping and handling (invoice) fees at the moment.
In the Klarna API, shipping and handling fees are added as products with
special flags on them.

The best way to implement this is probably to add two new methods
`addShippingFee` and `addHandlingFee` to `KL_Klarna_Model_Api_Request` and
call those from the authorize and capture method objects.

## KL_Klarna_Model_Invoice

### canUseForCountry & canUseForCurrency

These two test wether the payment method can be used for the selected
(billing) country and currency.

Testing them individually is not enough though, since country and currency
must match. `canUseForCountry` and `canUseForCurrency` is a good first line
of defense, but a check must also be implemented in `validate`.

### validate

Implement validation logic to make sure all information needed for the
payment is present and **valid**.

Important validations:

* Does the billing country and currency match up? SEK for Sweden, etc.
* Is there a national ID number present?

Validation of national ID numbers in Validate.php For Sweden asume the 
that the number has been used to fetch the customer address in the 
registration fase. 
- Create differenct regexp for differernt countries
- Handle Klarna errror codes for

### authorize & capture

* Use national ID number from checkout (it is hardcoded for now).