=== Nomba Payment Gateway for WooCommerce ===
Contributors: nombacheckout, tubiz
Tags: nomba, woocommerce, payment gateway, nigeria, naira,
Requires at least: 6.3
Tested up to: 6.5
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Nomba simplifies the process for Nigerian businesses to securely accept payments from various channels, both locally and internationally.

== Description ==

Nomba simplifies the process for Nigerian businesses to securely accept payments from various channels, both locally and internationally. By integrating Nomba into your WooCommerce store website, you empower your customers to pay conveniently using a range of methods:

* Credit/Debit Cards: Visa, Mastercard, Verve, American Express
* Bank transfer
* QR code
* USSD
* And more options on the horizon including Apple and Google Pay!

Nomba Payment Gateway for WooCommerce plugin allows you to receive payment on your WooCommerce store via [Nomba's](https://nomba.com/) API.

= Why Choose Nomba? =

* __Swift Setup__: Begin receiving payments in as little as 10 minutes after signing up. Simply create an [account](https://nomba.com/) and obtain your API keys
* __Transparent Pricing__: Enjoy straightforward rates of 1.4%, capped at N1,800 for local transactions, and 3.9% for international card payments.
* __Hassle-Free Dispute Management__: Access our Automated Dispute Manager at no additional cost.
* __Comprehensive Analytics__: Gain valuable insights through our intuitive dashboard.
* __Responsive Support__: Our empathetic customer service team is available 24/7 to assist you.
* __Ongoing Enhancements__: Benefit from free updates as we roll out new features and payment options.
* __Robust APIs__: Access clearly documented APIs to tailor your payment experiences to your specific needs.
* __Volume Discount__: Volume discounts available for merchants with 50m+ in monthly volumes

= Note =

This plugin is meant to be used by merchants in Nigeria.

= Plugin Features =

*   __Accept payment__ via Visa, Mastercard, Verve, American Express, Bank transfer, QR code & USSD
*   __Recurring payment__ using [WooCommerce Subscriptions](https://woo.com/products/woocommerce-subscriptions/) plugin

= WooCommerce Subscriptions Integration =

*	If a customer pays for a subscription using a Mastercard, Visa, Verve card, their subscription will renew automatically throughout the duration of the subscription. If an automatic renewal fail their subscription will be put on-hold, and they will have to log in to their account to renew the subscription.

*	For customers paying with USSD, Bank Transfer, QR code, their subscription can't be renewed automatically, once a payment is due their subscription will be on-hold. The customer will have to log in to their account to manually renew their subscription.

*	If a subscription has a free trial and no signup-fee, automatic renewal is not possible for the first payment because the initial order total will be 0, after the free trial the subscription will be put on-hold. The customer will have to log in to their account to renew their subscription. If a Mastercard, Visa, Verve is used to renew the subscription subsequent renewals will be automatic throughout the duration of the subscription.

= Suggestions / Feature Request =

Got an idea or a feature request? Feel free to reach out to us at integrations@nomba.com.

Let's make payment processing simpler and more efficient together with Nomba!

== Installation ==

*   Go to __WordPress Admin__ > __Plugins__ > __Add New__ from the left-hand menu
*   In the search box type __Nomba WooCommerce Payment Gateway__
*   Click on Install now when you see __Nomba WooCommerce Payment Gateway__ to install the plugin
*   After installation, __activate__ the plugin.


= Nomba Setup and Configuration =
*   Go to __WooCommerce > Settings__ and click on the __Payments__ tab
*   You'll see __Nomba__ listed along with your other payment methods. Click to view the plugin settings page
*   On the next screen, configure the plugin. There is a selection of options on the screen. Read what each one does below.

1. __Enable/Disable__ - Check this checkbox to Enable Nomba on your store's checkout
2. __Title__ - This will represent Nomba on your list of Payment options during checkout. It guides users to know which option to select to pay with Nomba. __Title__ is set to "Accept Secure Payment via Nomba" by default, but you can change it to suit your needs.
3. __Description__ - This controls the message that appears under the payment fields on the checkout page. Use this space to give more details to customers about what Nomba is and what payment methods they can use with it.
4. __Test Mode__ - Check this to enable test mode. When selected, the fields in step five will say "Test" instead of "Live." Test mode enables you to test payments before going live. The orders process with test payment methods, no money is involved so there is no risk. You can uncheck this when your store is ready to accept real payments.
5. __API Keys__ - The next six text boxes are for your Nomba API keys, which you can get from your Nomba merchant Dashboard.
8. Click on __Save Changes__ to update the settings.

To account for poor network connections, which can sometimes affect order status updates after a transaction, we __strongly__ recommend that you set a Webhook URL on your Nomba merchant dashboard. This way, whenever a transaction is complete on your store, we'll send a notification to the Webhook URL, which will update the order and mark it as paid. You can set this up by using the URL in red at the top of the Settings page. Just copy the URL and save it as your webhook URL on your Nomba dashboard under __Settings > Webhooks__ tab.

If you do not find Nomba on the Payment method options, please go through the settings again and ensure that:

*   You've checked the __"Enable/Disable"__ checkbox
*   You've entered your __API Keys__ in the appropriate field
*   Your store currency is set to __NGN__
*   You've clicked on __Save Changes__ during setup

== Frequently Asked Questions ==

= What Do I Need To Use The Plugin =

*   A Nomba merchant account—use an existing account or [create an account here](https://nomba.com/)
*   [WooCommerce](https://woo.com/document/installing-uninstalling-woocommerce/) plugin installed and activated on your WordPress site.
*   A valid [SSL Certificate](https://woo.com/document/ssl-and-https/)

= WooCommerce Subscriptions Integration =

*	If a customer pays for a subscription using a Mastercard, Visa, Verve card, their subscription will renew automatically throughout the duration of the subscription. If an automatic renewal fail their subscription will be put on-hold, and they will have to log in to their account to renew the subscription.

*	For customers paying with USSD, Bank Transfer, QR code, their subscription can't be renewed automatically, once a payment is due their subscription will be on-hold. The customer will have to log in to their account to manually renew their subscription.

*	If a subscription has a free trial and no signup-fee, automatic renewal is not possible for the first payment because the initial order total will be 0, after the free trial the subscription will be put on-hold. The customer will have to log in to their account to renew their subscription. If a Mastercard, Visa, Verve is used to renew the subscription subsequent renewals will be automatic throughout the duration of the subscription.

= Nomba's Terms of Service and Privacy Policy =

*   [Terms of Service](https://nomba.com/terms-of-service)
*   [Privacy Policy](https://nomba.com/privacy-policy)

== Changelog ==

= 1.0.0 - March 27, 2024 =
*   First release



== Screenshots ==

1. Nomba displayed as a payment method on the WooCommerce payment methods page

2. Nomba WooCommerce payment gateway settings page