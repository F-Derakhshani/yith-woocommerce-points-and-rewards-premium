=== YITH WooCommerce Points and Rewards  ===

Contributors: yithemes
Tags: points, rewards, Points and Rewards, point, woocommerce, yith, point collection, reward, awards, credits, multisite, advertising, affiliate, beans, coupon, credit, Customers, discount, e-commerce, ecommerce, engage, free, incentive, incentivize, loyalty, loyalty program, marketing, promoting, referring, retention, woocommerce, woocommerce extension, WooCommerce Plugin
Requires at least: 3.5.1
Tested up to: 4.7.5
Stable tag: 1.2.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

YITH WooCommerce Points and Rewards allows you to add a rewarding program to your site and encourage your customers collecting points.

== Description ==

Have you ever started collecting shopping points? What was your reaction? Most of us are really motivated in storing as many points as possible, because so we can get more and often we do not care about spending more because, if we do, we can have a better reward. Hasn't this happened to you too? That's what you get by putting into your site a point and reward programme: loyalising your customers, encouraging them to buy always from your shop and being rewarded for their loyalty.
If you think that reward programmes were only prerogative of big shopping centres or supermarkets, you're have to change your mind, because now you can add such a programme to your own e-commerce shop too. How? Simple, with YITH WooCommerce Points and Rewards: easy to setup and easy to understand for your customers!


== Installation ==
Important: First of all, you have to download and activate WooCommerce plugin, which is mandatory for YITH WooCommerce Points and Rewards to be working.

1. Unzip the downloaded zip file.
2. Upload the plugin folder into the `wp-content/plugins/` directory of your WordPress site.
3. Activate `YITH WooCommerce Points and Rewards` from Plugins page.


== Changelog ==

= Version 1.2.6 - Released: May 26, 2017 =
Fix: Method to calculate price worth


= Version 1.2.5 - Released: May 25, 2017 =
New: Support to WooCommerce 3.0.7
Dev: moved filter ywpar_set_max_discount_for_minor_subtotal
Dev: added filter ywpar_set_percentage_cart_subtotal
Dev: added wrapper for my-account elements
Fix: Coupons to Redeem points
Fix: Fix previuos orders price
Fix: Removed earning points in YITH Multivendor Suborders when vendor's orders are synchronized
Fix: Message in single product page for variable products


= Version 1.2.4 - Released: May 05, 2017 =
New: Support to WooCommerce 3.0.5
New: Added option to reassign redeemed points for total refund
Fix: Import points from previous orders
Fix: Readded options to enable point removal for total or partial refund
Fix: Shop Manager capabilities


= Version 1.2.3 - Released: Apr 28, 2017 =
New: Support to WooCommerce 3.0.4
Fix: Filter of customer in Customer Points tab
Update: Core Framework

= Version 1.2.2 - Released: Apr 12, 2017 =
New: Support to WooCommerce 3.0.1
Fix: Error with coupons
Fix: Remove points redeemed
Update: Core Framework

= Version 1.2.1 - Released: Apr 04, 2017 =
New: Support to WooCommerce 3.0
Tweak: Changed registration date with local registration date
Dev: Added filter 'ywpar_points_registration_date'
Fix: Error with php 5.4
Update: Core Framework

= Version 1.2.0 - Released: Mar 16, 2017 =
New: Support to WooCommerce 3.0 RC 1
New: Compatibility with AutomateWoo - Referrals Add-on 1.3.5
New: Spanish translation
Tweak: Refresh of messages after cart updates
Fix: Update messages on the cart page
Update: Core Framework


= Version 1.1.4  - Released: Jan 25, 2017 =
Fix: Calculation points when the category overrides the global conversion
Fix: Calculation price discount in fixed conversion value
Dev: Changed the style class 'product_point' with 'product_point_loop'
Dev: Added method 'calculate_price_worth' in class YITH_WC_Points_Rewards_Redemption
Dev: Added method 'get_price_from_point_earned' in class YITH_WC_Points_Rewards_Earning

= Version 1.1.3  - Released: Dec 21, 2016 =
Added: Option to enable shop manager to edit points
Added: A placeholder {price_discount_fixed_conversion} for message in single product page
Added: An option to change the label of button "Apply Discount"
Added: An option to select the rules that earning the points
Added: An option to select the rules that redeem the points
Added: An option to show points in loop
Added: Message to show points earned in order pay
Added: A filter 'ywpar_enabled_user' to enable or disable user
Added: An option to choose if free shipping allowed to redeem
Tweak: Compatibility with YITH WooCommerce Email Template
Tweak: Calculation points on older orders if product doesn't exists
Fixed: Overriding of points earned in variations
Fixed: Removed earning points in YITH Multivendor Suborders
Fixed: Update points to redeem when the cart is updated
Fixed: Email expiring content
Fixed: Earning point message on cart if a totally discount coupon is applied

= Version 1.1.2  - Released: Mar 24, 2016 =
Added: The return of points redeemed to the cancellation of the order
Added: Options on products and categories to override the rewards conversion discounts
Fixed: Javascript error in frontend.js
Tweak: Improvement Product Points calculation changed floor by round

= Version 1.1.1  - Released: Mar 14, 2016 =
Added: Button to reset points
Added: Change points values when variation select change
Tweak: Improvement Product Points calculation
Udated: Label of options in administrator panel

= Version 1.1.0 - Released: Mar 08, 2016 =
Fixed: Calculation earned points is a Dynamic Pricing and Discount rule is applied
Fixed: Moved ob_start() function in update send_email_update_points() method
Fixed: Update merge of default options with options from free version
Updated: Plugin Framework

= Version 1.0.9 - Released: Feb 29, 2016 =
Added: Option to redeem points with percentual discount
Added: Option to remove the possibility to redeem points
Added: Option to add a minimum amount discount to redeem points

= Version 1.0.8 - Released: Feb 11, 2016 =
Added: filter ywpar_get_product_point_earned that let third party plugin to set the point earned by specific product

= 1.0.7 - Released: Feb 05, 2016 =
Added: Shortcode yith_ywpar_points_list to show the list of points of a user
Added: Option to hide points in my account page
Fixed: Pagination on Customer's Points list

= 1.0.6 - Released: Feb 01, 2016 =
Fixed: Calculation points when coupons are used

= 1.0.5 - Released: Jan 26, 2016 =
Added: Option to remove points when coupons are used
Added: Earning Points in a manual order
Added: In Customer's Points tab all customers are showed also without points
Added: Compatibility with YITH WooCommerce Multi Vendor Premium hidden the points settings on products for vendors
Fixed: Removed Fatal in View Points if the order do not exists
Fixed: Conflict js with YITH Dynamic Pricing and Discounts
Fixed: Refund points calculation for partial refund
Fixed: Extra points double calculation

= 1.0.4 - Released: Jan 07, 2016 =
Added: Compatibility with WooCommerce 2.5 RC1
Fixed: Redeem points also if the button "Apply discount" is not clicked
Fixed: Calculation points on a refund order
Fixed: Update Points content

= 1.0.3 - Released: Dec 14, 2015 =
Added: Compatibility with Wordpress 4.4
Fixed: Extra points options
Fixed: Reviews assigment points for customers
Fixed: String translations
Updated: Changed Text Domain from 'ywpar' to 'yith-woocommerce-points-and-rewards'
Updated: Plugin Framework

= 1.0.2 - Released: Nov 30, 2015 =
Fixed: Enable/Disable Option
Fixed: Double points assigment
Update: Plugin Framework


= 1.0.1 - Released: Sept 23, 2015 =
Added: Minimun amount to reedem
Added: Italian Translation

= 1.0.0 - Released: Sept 17, 2015 =
Initial release