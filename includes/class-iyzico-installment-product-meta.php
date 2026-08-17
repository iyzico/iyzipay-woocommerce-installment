<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Iyzico Installment Product Meta class.
 *
 * Adds a per-product override control ("Default / Enable / Disable") to the
 * WooCommerce product edit screen so a single product can force the
 * installment display on or off regardless of the category/brand rule or
 * the plugin's global settings.
 *
 * This class is intentionally self-contained: it does not modify any of the
 * plugin's original files. It only reads/writes its own post meta key and
 * exposes a small public API that other classes (e.g. the future rules
 * engine) can call to find out what the merchant chose for a given product.
 *
 * @package  Iyzico_Installment
 * @category Admin
 * @author   Iyzico
 * @license  GPLv2 or later
 * @link     https://iyzico.com
 */
class Iyzico_Installment_Product_Meta {

	/**
	 * Post meta key used to store the per-product override.
	 *
	 * Possible values: '' (default/inherit), 'enable', 'disable'.
	 */
	const META_KEY = '_iyzico_installment_override';

	/**
	 * Nonce action/name used to protect the save request.
	 */
	const NONCE_ACTION = 'iyzico_installment_product_meta_save';
	const NONCE_NAME   = 'iyzico_installment_product_meta_nonce';

	/**
	 * Settings instance (kept for consistency with the plugin's DI pattern;
	 * not strictly required yet but avoids a breaking constructor change
	 * later if we need global settings here).
	 *
	 * @var Iyzico_Installment_Settings
	 */
	private $_settings;

	/**
	 * Constructor.
	 *
	 * @param Iyzico_Installment_Settings $settings Settings instance.
	 */
	public function __construct( Iyzico_Installment_Settings $settings ) {
		$this->_settings = $settings;

		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'renderField' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'saveField' ) );
	}

	/**
	 * Get the list of selectable override values with their labels.
	 *
	 * @return array<string,string> value => label
	 */
	private function _getOptions() {
		return array(
			''        => __( 'INSTALLMENT_OVERRIDE_DEFAULT', 'iyzico-installment' ),
			'enable'  => __( 'INSTALLMENT_OVERRIDE_ENABLE', 'iyzico-installment' ),
			'disable' => __( 'INSTALLMENT_OVERRIDE_DISABLE', 'iyzico-installment' ),
		);
	}

	/**
	 * Render the override select field inside the "General" product data tab.
	 *
	 * @return void
	 */
	public function renderField() {
		global $post;

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$current = get_post_meta( $post->ID, self::META_KEY, true );

		echo '<div class="options_group iyzico-installment-product-meta">';

		woocommerce_wp_select(
			array(
				'id'          => self::META_KEY,
				'label'       => __( 'INSTALLMENT_OVERRIDE_LABEL', 'iyzico-installment' ),
				'options'     => $this->_getOptions(),
				'value'       => $current,
				'desc_tip'    => true,
				'description' => __( 'INSTALLMENT_OVERRIDE_DESCRIPTION', 'iyzico-installment' ),
			)
		);

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		echo '</div>';
	}

	/**
	 * Save the override value when the product is saved.
	 *
	 * @param int $post_id Product post ID.
	 *
	 * @return void
	 */
	public function saveField( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE_NAME ] )
			|| ! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ),
				self::NONCE_ACTION
			)
		) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		$allowed = array_keys( $this->_getOptions() );
		$value   = isset( $_POST[ self::META_KEY ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::META_KEY ] ) )
			: '';

		if ( ! in_array( $value, $allowed, true ) ) {
			$value = '';
		}

		if ( '' === $value ) {
			delete_post_meta( $post_id, self::META_KEY );
		} else {
			update_post_meta( $post_id, self::META_KEY, $value );
		}
	}

	/**
	 * Get the override value for a given product.
	 *
	 * @param int|WC_Product $product Product ID or instance.
	 *
	 * @return string '' (default), 'enable', or 'disable'.
	 */
	public function getOverride( $product ) {
		$product_id = is_a( $product, 'WC_Product' ) ? $product->get_id() : intval( $product );

		if ( $product_id <= 0 ) {
			return '';
		}

		$value = get_post_meta( $product_id, self::META_KEY, true );

		return in_array( $value, array( 'enable', 'disable' ), true ) ? $value : '';
	}
}