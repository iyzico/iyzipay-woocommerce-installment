<?php
/**
 * Plugin Name: iyzico Installment
 * Description: iyzico Installment for WooCommerce.
 * Version: 1.1.0
 * Requires at least: 6.6
 * WC requires at least: 9.3.3
 * Requires PHP: 7.4.33
 * Author: iyzico
 * Author URI: https://iyzico.com
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: iyzico-installment
 * Domain Path: /i18n/languages/
 * Requires Plugins: woocommerce, iyzico-woocommerce
 *
 * @category   Core
 * @package    Iyzico_Installment
 * @author     iyzico <support@iyzico.com>
 * @license    GPLv2 or later
 * @version    1.1.0
 * @link       https://iyzico.com
 * @phpversion 7.4.33
 *
 * Tested up to: 6.8
 * WC tested up to: 9.7.1
 * WC_HPOS_Compatibility: true
 */

// Prevent direct access
if (! defined('ABSPATH') ) {
    exit;
}

// Plugin Constants
define('IYZI_INSTALLMENT_VERSION', '1.1.0');
define('IYZI_INSTALLMENT_FILE', __FILE__);
define('IYZI_INSTALLMENT_PATH', plugin_dir_path(__FILE__));
define('IYZI_INSTALLMENT_URL', plugin_dir_url(__FILE__));
define(
    'IYZI_INSTALLMENT_ASSETS_URL',
    plugin_dir_url(__FILE__) . 'assets'
);

// Step 1: Load logger
require_once IYZI_INSTALLMENT_PATH . 'includes/class-iyzico-installment-logger.php';

// Step 2: Load settings class
require_once IYZI_INSTALLMENT_PATH .
    'includes/class-iyzico-installment-settings.php';
$GLOBALS['iyzico_settings'] = new Iyzico_Installment_Settings();

// Step 3: Load API class
require_once IYZI_INSTALLMENT_PATH . 'includes/class-iyzico-installment-api.php';
$GLOBALS['iyzico_api'] = new Iyzico_Installment_API($GLOBALS['iyzico_settings']);

// Step 4: Load Frontend class
require_once IYZI_INSTALLMENT_PATH .
    'includes/class-iyzico-installment-frontend.php';
$GLOBALS['iyzico_frontend'] = new Iyzico_Installment_Frontend(
    $GLOBALS['iyzico_settings'],
    $GLOBALS['iyzico_api']
);

// Step 5: Load Hpos class
require_once IYZI_INSTALLMENT_PATH . 'includes/class-iyzico-installment-hpos.php';
Iyzico_Installment_Hpos::init();

// Step 6: Load Admin class
require_once IYZI_INSTALLMENT_PATH .
    'includes/admin/class-iyzico-installment-admin.php';
$GLOBALS['iyzico_admin'] = new Iyzico_Installment_Admin(
    $GLOBALS['iyzico_settings']
);

// Step 7: Load Dynamic Installment class
require_once IYZI_INSTALLMENT_PATH .
    'includes/class-iyzico-installment-dynamic.php';
$GLOBALS['iyzico_dynamic'] = new Iyzico_Installment_Dynamic(
    $GLOBALS['iyzico_settings'],
    $GLOBALS['iyzico_api']
);