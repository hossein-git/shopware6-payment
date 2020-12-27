
# shopware6-payment
#### Beta Version
Shopware 6 Custom Payment Methods for 4 Iranian Payment Company
پلاگین درگاه پرداخت برای شاپ ور 6 


## Future:

- support shorter shopware 6 getaway token length 
- generates short secure token for getaways which does not support JWT token
- config file to add API codes 
- support native JWT shopware token as well
- save all transactions into the DB 
- track all transitions from admin panel
- Payment method for nextpay.ir
- Payment method for farapal.ir
- Payment method for pay.ir
- Payment method for payping.ir

## installation:

Copy or pull IranPay into custom/plugins then install and active it.
this plugin comes with 4 payment method.

## Docs

### Add new method
- in src/helpers/AddPaymentMethods , add your new method on $methods property,
name of the method should be same as class name without Payment at the End.
 
- then create a class on src/Models with the same name that you created on above.

- create an another class into src/Service .its your service class which you can read more about it in shopware docs and logic can be done here

### Generate Shorter Token 
- in use class Helpers/GenerateToken.php to generate  shorter token , you can see an example on src/Service/FaraPalPayment.php 
-  Route for custom token  is /payment/f-i-t , controller can be find on src/Controller/PaymentController



#### I am open to any merge request thank you
