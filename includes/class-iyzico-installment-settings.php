<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Iyzico Installment Settings class.
 *
 * @package Iyzico_Installment
 * @category Core
 * @author Iyzico
 * @license GPLv2 or later
 * @link https://iyzico.com
 */
class Iyzico_Installment_Settings {

	/**
	 * Option key in the database
	 */
	const OPTION_KEY = 'iyzico_installment_settings';

	/**
	 * Default settings
	 *
	 * @var array
	 */
	private $_defaults = array(
		'api_key'                     => '',
		'secret_key'                  => '',
		'integration_type'            => 'shortcode',
		'mode'                        => 'sandbox',
		'enable_vat'                  => false,
		'vat_rate'                    => 20,
		'enable_dynamic_installments' => false,
		'custom_css'                  => '',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize settings
		$this->_initializeSettings();
	}

	/**
	 * Initialize settings
	 *
	 * @return void
	 */
	private function _initializeSettings() {
		$settings = get_option( self::OPTION_KEY, array() );

		if ( empty( $settings ) ) {
			update_option( self::OPTION_KEY, $this->_defaults );
		}
	}

	/**
	 * Get all settings
	 *
	 * @return array
	 */
	public function getAll() {
		$settings = get_option( self::OPTION_KEY, $this->_defaults );
		return wp_parse_args( $settings, $this->_defaults );
	}

	/**
	 * Get single setting
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 *
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		$settings = $this->getAll();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Update single setting
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 *
	 * @return bool
	 */
	public function update( $key, $value ) {
		$settings         = $this->getAll();
		$settings[ $key ] = $value;
		return update_option( self::OPTION_KEY, $settings );
	}

	/**
	 * Update multiple settings
	 *
	 * @param array $new_settings New settings.
	 *
	 * @return bool
	 */
	public function updateMultiple( $new_settings ) {
		$settings = $this->getAll();
		$settings = wp_parse_args( $new_settings, $settings );
		return update_option( self::OPTION_KEY, $settings );
	}

	/**
	 * Delete settings
	 *
	 * @return bool
	 */
	public function delete() {
		return delete_option( self::OPTION_KEY );
	}

	/**
	 * Check if API credentials are set
	 *
	 * @return bool
	 */
	public function hasCredentials() {
		$settings = $this->getAll();
		return ! empty( $settings['api_key'] ) && ! empty( $settings['secret_key'] );
	}

	/**
	 * Get API key
	 *
	 * @return string
	 */
	public function getApiKey() {
		return $this->get( 'api_key', '' );
	}

	/**
	 * Get secret key
	 *
	 * @return string
	 */
	public function getSecretKey() {
		return $this->get( 'secret_key', '' );
	}

	/**
	 * Get API URL based on mode
	 *
	 * @return string
	 */
	public function getApiUrl() {
		$mode = $this->get( 'mode', 'sandbox' );
		return ( $mode === 'live' )
			? 'https://api.iyzipay.com'
			: 'https://sandbox-api.iyzipay.com';
	}

	/**
	 * Get integration type
	 *
	 * @return string
	 */
	public function getIntegrationType() {
		return $this->get( 'integration_type', 'shortcode' );
	}

	/**
	 * Check if direct integration is enabled
	 *
	 * @return bool
	 */
	public function isDirectIntegration() {
		return $this->getIntegrationType() === 'direct';
	}

	/**
	 * Check if tabs should be displayed on product page
	 * Alias for isDirectIntegration for better readability
	 *
	 * @return bool
	 */
	public function showProductTabs() {
		return $this->isDirectIntegration();
	}

	/**
	 * Check if VAT is enabled
	 *
	 * @return bool
	 */
	public function isVatEnabled() {
		return $this->get( 'enable_vat', false );
	}

	/**
	 * Get VAT rate
	 *
	 * @return float
	 */
	public function getVatRate() {
		return floatval( $this->get( 'vat_rate', 20 ) );
	}

	/**
	 * Check if dynamic installments are enabled
	 *
	 * @return bool
	 */
	public function isDynamicInstallmentsEnabled() {
		return $this->get( 'enable_dynamic_installments', false );
	}

	/**
	 * Calculate price with VAT
	 *
	 * @param float $price Base price.
	 *
	 * @return float
	 */
	public function calculatePriceWithVat( $price ) {
		if ( ! $this->isVatEnabled() ) {
			return $price;
		}

		$vat_rate = $this->getVatRate();
		return $price * ( 1 + ( $vat_rate / 100 ) );
	}

	/**
	 * Get custom CSS
	 *
	 * @return string
	 */
	public function getCustomCss() {
		return $this->get( 'custom_css', '' );
	}
}
