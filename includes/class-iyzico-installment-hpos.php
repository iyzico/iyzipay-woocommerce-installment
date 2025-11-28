<?php
/**
 * Iyzico Installment HPOS Compatibility
 *
 * This file contains the HPOS compatibility class for WooCommerce.
 *
 * @category   Core
 * @package    Iyzico_Installment
 * @author     iyzico <support@iyzico.com>
 * @license    GPLv2 or later
 * @version    1.1.0
 * @link       https://iyzico.com
 * @phpversion 7.4.33
 */

if (! defined('ABSPATH') ) {
    exit;
}

/**
 * Iyzico Installment HPOS Compatibility class.
 *
 * @category Core
 * @package  Iyzico_Installment
 * @author   iyzico <support@iyzico.com>
 * @license  GPLv2 or later
 * @link     https://iyzico.com
 */
class Iyzico_Installment_Hpos
{

    /**
     * Initialize HPOS compatibility
     *
     * @return void
     */
    public static function init()
    {
        add_action(
            'before_woocommerce_init',
            array(
                self::class,
                'woocommerceHposCompatibility',
            )
        );
    }

    /**
     * Declare WooCommerce HPOS compatibility
     *
     * @return void
     */
    public static function woocommerceHposCompatibility()
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil') ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                IYZI_INSTALLMENT_FILE,
                true
            );
        }
    }
}
