# iyzico Installment for WooCommerce

[![WordPress](https://img.shields.io/badge/WordPress-6.6+-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-9.3.3+-green.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4.33+-red.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

The iyzico Installment plugin displays installment options to your customers using iyzico's installment calculation on WooCommerce product pages. This plugin only displays installment information and does not process payments.

## 🚀 Features

- **Product Page Integration**: Automatic installment display on WooCommerce product pages
- **Dynamic Installment Calculation**: Real-time installment updates for price changes in variable products
- **iyzico API Integration**: Real-time installment calculation
- **Multiple Integration Options**: Use as shortcode, product tab, or widget
- **AJAX Support**: Dynamic installment calculation and updates
- **Category-Based Installment Rules**: Enable or disable installment display for specific product categories
- **Brand-Based Installment Rules**: Enable or disable installment display for specific product brands (WooCommerce native Brands)
- **Per-Product Override**: Force installment display on or off for an individual product, taking priority over any category/brand rule
- **Responsive Design**: Mobile and desktop compatible
- **Bank Logos**: Automatic logo display by credit card families
- **VAT Calculation**: Option to include VAT in product prices
- **HPOS Compatibility**: WooCommerce High-Performance Order Storage support
- **Multi-language Support**: i18n integration
- **Advanced Logging**: Detailed error tracking and debug information

## 📋 Requirements

- **WordPress**: 6.6.2 or higher
- **WooCommerce**: 9.3.3 or higher
- **PHP**: 7.4.33 or higher
- **cURL Extension**: PHP cURL support
- **iyzico WooCommerce**: Main payment plugin
- **iyzico Account**: API access for installment calculation

## 🛠️ Installation

### Installation from WordPress.org (Recommended)

1. Go to **Plugins > Add New** in your WordPress admin panel
2. Type "iyzico Installment" in the search box
3. Find the plugin and click the **Install** button
4. After installation is complete, click the **Activate** button

### Manual Installation

1. Download the plugin ZIP file
2. Go to **Plugins > Add New** in your WordPress admin panel
3. Click the **Upload Plugin** button
4. Select the downloaded ZIP file and click the **Install Now** button
5. After installation is complete, click the **Activate Plugin** button

## ⚙️ Configuration

### 1. API Credentials

You need to enter your iyzico account information to use the plugin:

1. Go to the **iyzico Installment** page
2. Fill in the **API Key** and **Secret Key** fields
3. Select **Test Mode** or **Live Mode**
4. Click the **Save** button

### 2. Integration Type

The plugin offers three different integration types:

- **Shortcode**: Display anywhere using `[iyzico_installment]` or `[dynamic_iyzico_installment]`
- **Product Tab**: Automatically adds installment tab on product pages
- **Widget**: Display installment information in sidebar or footer

### 3. Display Settings

- **Installment Tab Display**: Add installment tab on product pages
- **Responsive Design**: Mobile-compatible display

### 4. Category, Brand & Per-Product Installment Rules

Installment display is enabled by default everywhere, but you can restrict or re-enable it at three levels, evaluated in this priority order:

1. **Per-product override** (highest priority) — on the product edit screen, under the General tab, choose **Default**, **Enable**, or **Disable** for that specific product.
2. **Category / brand rule** — on the **iyzico Installment** settings page, pick categories or brands (WooCommerce's native Brands taxonomy) to explicitly enable or disable. If a product matches rules in more than one grouping and they conflict, **Disable wins**.
3. **Default** — if no rule applies at any level, installment display is **enabled**, matching the plugin's original behavior.

This lets you do things like: enable installment for an entire category, but turn it off for one specific product inside it — or disable it for a whole category while re-enabling it for a single product. The rule is applied consistently across the product tab, the `[iyzico_installment]` shortcode, and the dynamic installment AJAX endpoint.

## 🔧 Usage

### Shortcode Usage

To display installment information on any page or post:

```php
[iyzico_installment]
```

### Dynamic Installment Shortcode

For real-time installment updates in variable products:

```php
[dynamic_iyzico_installment]
```

### Usage with PHP Code

```php
// To get installment information programmatically
$installment_info = $GLOBALS['iyzico_api']->get_installment_info($product_price);

// To render the shortcode
echo do_shortcode('[iyzico_installment]'); // or [dynamic_iyzico_installment]

// To check whether installment display is enabled for a given product
// (combines per-product override + category/brand rules)
$is_enabled = $GLOBALS['iyzico_rules']->isEnabledForProduct($product);
```

### Theme Integration

Automatic integration by adding to your `functions.php` file:

```php
// Automatic installment display on product pages
add_action('woocommerce_single_product_summary', function() {
    echo do_shortcode('[iyzico_installment]'); // or [dynamic_iyzico_installment]
}, 25);
```

## 🏗️ Technical Architecture

The plugin has a modular structure:

```
iyzico-installment/
├── iyzico-installment.php          # Main plugin file
├── includes/                        # Class files
│   ├── class-iyzico-installment-settings.php        # Settings management
│   ├── class-iyzico-installment-api.php             # API integration
│   ├── class-iyzico-installment-frontend.php        # Frontend operations
│   ├── class-iyzico-installment-dynamic.php         # Dynamic installment system
│   ├── class-iyzico-installment-logger.php          # Logging system
│   ├── class-iyzico-installment-hpos.php            # HPOS compatibility
│   ├── class-iyzico-installment-product-meta.php    # Per-product installment override
│   ├── class-iyzico-installment-taxonomy-rules.php  # Category/brand installment rules
│   ├── class-iyzico-installment-rules.php           # Decision engine (combines the two above)
│   └── admin/                      # Admin panel
├── assets/                         # CSS, JS and images
│   ├── css/                        # Style files
│   ├── js/                         # JavaScript files
│   └── images/                     # Bank logos
├── i18n/                           # Language files
└── logs/                           # Log files
```

### Class Structure

- **Settings**: Manages plugin settings
- **API**: Provides iyzico API integration
- **Frontend**: User interface and shortcode operations
- **Dynamic**: Dynamic installment calculation for variable products
- **Logger**: Error tracking and debug information
- **HPOS**: WooCommerce High-Performance Order Storage compatibility
- **Product_Meta**: Stores and retrieves the per-product installment override (Default/Enable/Disable)
- **Taxonomy_Rules**: Stores and retrieves the category/brand installment rules, rendered as WooCommerce-style Select2 pickers in the settings page
- **Rules**: The decision engine — combines the per-product override and the category/brand rule into a single "should this product show installment?" answer, used by the product tab, shortcode, and dynamic AJAX endpoint
- **Admin**: Admin panel settings

## 🔌 API Integration

The plugin uses iyzico's official PHP SDK:

```php
use Iyzipay\Model\InstallmentInfo;
use Iyzipay\Options;
use Iyzipay\Request\RetrieveInstallmentInfoRequest;

// Get installment information
$request = new RetrieveInstallmentInfoRequest();
$request->setLocale('tr');
$request->setConversationId(uniqid('iyzico_installment_'));
$request->setPrice($product_price);
$request->setBinNumber($bin_number);

$response = InstallmentInfo::retrieve($request, $options);
```

## 🎨 Customization

### CSS Customization

You can customize the plugin's appearance through the admin panel. You can add your own style codes to the "Custom CSS" field in the admin panel:

```css
/* Installment container */
.iyzico-installment-container {
    background: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
}

/* Bank cards */
.iyzico-bank-card {
    background: #fafafa;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 15px;
    transition: all 0.3s ease;
}

.iyzico-bank-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
```

For more detailed examples, check the `style.css` file.

### JavaScript Customization

Secure JavaScript operations for dynamic installment updates:

```javascript
// Debug function - only works in WP_DEBUG mode
function debugLog(message, data) {
    if (window.installment_ajax && window.installment_ajax.debug && typeof console !== 'undefined') {
        if (data !== undefined) {
            console.log(message, data);
        } else {
            console.log(message);
        }
    }
}

// Listen for variation changes
jQuery(document).on('found_variation', 'form.variations_form', function(event, variation) {
    if (variation && variation.display_price) {
        var finalPrice = parseFloat(variation.display_price);
        
        // VAT calculation
        if (window.installment_ajax && window.installment_ajax.vat_enabled === 'true') {
            var vatRate = parseFloat(window.installment_ajax.vat_rate) || 0;
            finalPrice = finalPrice * (1 + (vatRate / 100));
        }
        
        debugLog('Final price with VAT:', finalPrice);
        loadInstallments(finalPrice);
    }
});

// Secure installment information loading
function loadInstallments(price) {
    // Price validation
    if (!price || price <= 0 || isNaN(price)) {
        debugLog('Invalid price:', price);
        return;
    }
    
    // AJAX object existence check
    if (!window.installment_ajax) {
        debugLog('installment_ajax object not found');
        return;
    }
    
    jQuery.ajax({
        url: window.installment_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'get_installment_options',
            price: price,
            product_id: parseInt(window.installment_ajax.product_id) || 0,
            nonce: window.installment_ajax.nonce
        },
        timeout: 10000, // 10 second timeout
        success: function(response) {
            debugLog('AJAX Response:', response);
            
            if (response && response.success) {
                // response.data is already sanitized with wp_kses_post() on server side
                jQuery('.dynamic-iyzico-installment').html(response.data);
            } else {
                // Show error messages securely - XSS protection
                var errorMsg = (response && response.data) ? String(response.data) : 'Unknown error';
                var sanitizedError = jQuery('<div>').text(errorMsg).html();
                jQuery('.dynamic-iyzico-installment').html('<p>Error: ' + sanitizedError + '</p>');
            }
        },
        error: function(xhr, status, error) {
            debugLog('AJAX Error - Status:', status);
            debugLog('AJAX Error - Error:', error);
            
            // Don't show technical details to user - security
            var userMessage = 'Connection error. Please try again.';
            
            // Special message for timeout
            if (status === 'timeout') {
                userMessage = 'Request timed out. Please try again.';
            }
            
            jQuery('.dynamic-iyzico-installment').html('<p>' + userMessage + '</p>');
        }
    });
}
```

## 🐛 Troubleshooting

### Common Issues

**Installment information not displaying:**
- Check API credentials
- Ensure WooCommerce is active
- Check if dynamic installment setting is enabled
- Review log files
- Check the category/brand rules and the product's own installment override — the product may be intentionally excluded

**Installment not updating on variations:**
- Check JavaScript errors (Browser Console)
- Ensure AJAX requests are successful
- Verify nonce value is correct

**Getting API errors:**
- Check if API Key and Secret Key are correct
- Check Test/Live mode setting
- Ensure cURL extension is active

## 📱 Responsive Design

The plugin works compatible on all devices:

- **Desktop**: Full-width table view
- **Tablet**: Medium-sized table view
- **Mobile**: Vertical list view

## 🌐 Multi-language Support

The plugin uses i18n standards:

- **Turkish**: Default language
- **English**: Translation files available
- **Custom Translations**: Can be added in `languages/` folder

## 🔒 Security

- **Nonce Control**: Security in AJAX requests
- **Rate Limiting**: Protection against DDoS attacks (15 req/min per IP)
- **ABSPATH Check**: Prevent direct access
- **Advanced CSS Sanitization**: Secure style addition with XSS protection
- **Production-Safe Debugging**: Controlled log system in debug mode
- **API Security**: iyzico's secure API protocol
- **WordPress Standards**: WordPress coding standards compliant
- **Input/Output Sanitization**: Security checks on all data inputs
- **Server-Side Rule Enforcement**: category/brand and per-product installment rules are validated on the server (including the AJAX endpoint), not just hidden in the UI

## 📊 Performance

- **Lazy Loading**: Load scripts only on necessary pages
- **AJAX Caching**: Cache installment information
- **Database Optimization**: Optimize database queries

## 🤝 Contributing

1. Fork this repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push your branch (`git push origin feature/amazing-feature`)
5. Create a Pull Request

## 📄 License

This project is licensed under the [GPL v2](https://www.gnu.org/licenses/gpl-2.0.html) license.

## 📞 Support

- **Technical Support**: [iyzico Customer Service](https://iyzico.com/iletisim)
- **Documentation**: [iyzico Developer Portal](https://docs.iyzico.com/)
- **GitHub Issues**: [Repository Issues](https://github.com/iyzico/iyzipay-woocommerce-installment/issues)

## 🔄 Updates

### v1.3.0
- **Category-Based Installment Rules**: Enable/disable installment display per product category
- **Brand-Based Installment Rules**: Enable/disable installment display per product brand (WooCommerce native Brands)
- **Per-Product Override**: Force installment display on/off for an individual product, overriding the category/brand rule
- **Consistent Enforcement**: Rules apply across the product tab, the `[iyzico_installment]` shortcode, and the dynamic installment AJAX endpoint
- **New Settings UI**: WooCommerce-style Select2 category/brand pickers on the plugin settings page

### v1.1.0
- **Dynamic Installment System**: Real-time installment updates for variable products
- **VAT Calculation**: Option to include VAT in product prices
- **AJAX Security**: Nonce control and security improvements
- **CSS Optimization**: Responsive design improvements

### v1.0.0
- Initial release
- WooCommerce product page integration
- iyzico installment calculation integration
- Display installment options
- Responsive design
- HPOS compatibility
- Advanced logging system

## 📝 Changelog

For detailed changelist, see [CHANGELOG.md](CHANGELOG.md) file.

---

**iyzico Installment** - Professional installment solution for WooCommerce

[![iyzico](https://img.shields.io/badge/iyzico-Official%20Plugin-orange.svg)](https://iyzico.com/)