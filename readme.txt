=== iyzico Installment ===
Contributors: iyzico, tarikkamat, ta2edh
Tags: iyzico, woocommerce, installment, product-page
Tested up to: 6.8
Stable tag: 1.3.0
Requires at least: 6.6
Requires PHP: 7.4.33
License: GPL v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Display installment options on product pages with iyzico installment calculation engine.

== Description ==

The iyzico Installment plugin displays installment options to your customers on WooCommerce product pages using iyzico's installment calculation.

**Key Features:**

* Display installment options on product pages
* VAT-inclusive price calculation option
* Custom CSS addition from admin interface
* iyzico installment calculation integration
* Automatic integration with WooCommerce product pages
* Category-based installment control (enable/disable per product category)
* Brand-based installment control (enable/disable per product brand)
* Per-product override (force installment on/off for an individual product)
* Responsive design

**What It Does:**

This plugin only displays installment information on product pages. It does not process payments, only provides installment calculation and display services.

**Requirements:**

* PHP 7.4.33 and higher
* cURL extension
* WooCommerce 9.0.0 and higher
* WordPress 6.6.2 and higher
* iyzico WooCommerce (payment plugin)
* iyzico account (for installment calculation)

**Usage:**

After plugin installation, installment options are automatically displayed on all WooCommerce product pages. Customers can see the product price and available installment options.

You can further refine where installment options appear:

* From **iyzico Installment** settings, enable or disable installment display for specific product categories or brands.
* From the product edit screen, force installment display on or off for an individual product — this override always takes priority over the category/brand rule.

== Installation ==

**Manual Installation:**

1. Download the plugin ZIP file
2. Go to **Plugins > Add New** in your WordPress admin panel
3. Click the **Upload Plugin** button
4. Select the ZIP file you downloaded and click the **Install Now** button
5. After installation is complete, click the **Activate Plugin** button

**Installation from WordPress.org:**

1. Go to **Plugins > Add New** in your WordPress admin panel
2. Type "iyzico Installment" in the search box
3. Find the plugin and click the **Install** button
4. After installation is complete, click the **Activate** button

**Post-Installation:**

1. Go to **WooCommerce > Settings > General** page
2. Enable the iyzico Installment plugin
3. Enter your iyzico account information
4. Check that installment options are displayed on the product page

== Frequently Asked Questions ==

**= Does this plugin process payments? =**

No, this plugin only displays installment options on product pages. It does not process payments.

**= On which pages are installment options visible? =**

Automatically visible on all WooCommerce product pages, unless restricted via the category/brand rules or a per-product override.

**= What is the WooCommerce compatibility? =**

The plugin is compatible with WooCommerce 9.0.0 and higher versions.

**= How is installment calculation done? =**

Real-time calculation is done using iyzico's installment calculation engine.

**= Does it work with Composite (bundled) products? =**

Yes. For WooCommerce Composite Products, enable "Dynamic Installments" in the plugin settings and add the [dynamic_iyzico_installment] shortcode to the product. The installment table updates live as the customer configures the composite and its total price changes.

**= Which VAT/KDV rate is used? =**

When VAT is enabled, each product's rate is read from its WooCommerce tax class (e.g. standard 20%, reduced 10%/1%). If WooCommerce taxes are disabled or no rate is set, the global VAT rate from the plugin settings is used.

**= Can I control installment display per category or brand? =**

Yes. In the plugin's settings page, you can choose specific product categories or brands (WooCommerce's native Brands taxonomy) to explicitly enable or disable installment display for. If a category/brand isn't listed, installment display follows the default (enabled) behavior.

**= Can I override the rule for a single product? =**

Yes. On the product edit screen, under the General tab, you'll find an "Installment Management" option with three choices: Default, Enable, or Disable. This per-product setting always takes priority over any category/brand rule.

**= Can I get support? =**

Yes, you can contact iyzico customer service for technical support.

== Screenshots ==

1. Product page - Installment options
2. Admin panel - Plugin settings
3. Admin panel - Category/brand installment rules
4. Product edit screen - Per-product installment override

== Changelog ==

= 1.3.0 =
* Category-based installment control - enable/disable installment display per product category
* Brand-based installment control - enable/disable installment display per product brand (WooCommerce native Brands)
* Per-product override - force installment on/off for an individual product, overriding the category/brand rule
* Rule priority: product override > category/brand rule > default (enabled)
* Applies consistently across the product tab, the [iyzico_installment] shortcode, and the dynamic installment AJAX endpoint
* New settings UI: WooCommerce-style Select2 category/brand pickers on the plugin settings page

= 1.2.0 =
* Responsive bank logos - logos now scale correctly on mobile and no longer overflow
* Front-end stylesheet is now loaded automatically (custom CSS now applies reliably)
* Per-product VAT - VAT is calculated from each product's WooCommerce tax class (global rate used as fallback)
* WooCommerce Composite Products support - installment table updates live as the customer configures the composite

= 1.1.0 =
* Dynamic installment system - Real-time installment updates for variable products
* [dynamic_iyzico_installment] shortcode support
* VAT calculation option added
* Custom CSS addition feature from admin panel

= 1.0.0 =
* First release
* WooCommerce product page integration
* iyzico installment calculation engine integration
* Display installment options
* Responsive design

== Upgrade Notice ==

= 1.3.0 =
New: manage installment display per category, per brand, or per individual product (with per-product override taking priority). No action required for existing installs — default behavior is unchanged unless you configure new rules.

= 1.2.0 =
Responsive mobile logos, per-product VAT (tax class based) and WooCommerce Composite Products support. For composite products, place the [dynamic_iyzico_installment] shortcode on the product and enable Dynamic Installments.

= 1.1.0 =
Major feature update! Dynamic installment system and customization options added. Update is highly recommended.

= 1.0.0 =
This is the first release. It is recommended to keep it updated for security and performance improvements.