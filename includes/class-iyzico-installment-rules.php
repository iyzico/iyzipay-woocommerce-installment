<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Iyzico Installment Rules class.
 *
 * The decision engine that combines:
 *   1. Per-product override (Iyzico_Installment_Product_Meta) — highest priority.
 *   2. Category/brand general rule (Iyzico_Installment_Taxonomy_Rules) — used
 *      only when no product-level override is set.
 *   3. Default: enabled (preserves the plugin's original behaviour when no
 *      rule of any kind has been configured).
 *
 * This class does not read the plugin's global on/off setting
 * (integration_type / hasCredentials) — those checks remain exactly where
 * they already are in Iyzico_Installment_Frontend. This class only answers
 * one narrow question: "given that installment display is otherwise active,
 * should THIS product show it?"
 *
 * @package  Iyzico_Installment
 * @category Core
 * @author   Iyzico
 * @license  GPLv2 or later
 * @link     https://iyzico.com
 */
class Iyzico_Installment_Rules {

	/**
	 * Product meta instance (per-product override).
	 *
	 * @var Iyzico_Installment_Product_Meta
	 */
	private $_product_meta;

	/**
	 * Taxonomy rules instance (category/brand general rule).
	 *
	 * @var Iyzico_Installment_Taxonomy_Rules
	 */
	private $_taxonomy_rules;

	/**
	 * Constructor.
	 *
	 * @param Iyzico_Installment_Product_Meta   $product_meta   Product meta instance.
	 * @param Iyzico_Installment_Taxonomy_Rules $taxonomy_rules Taxonomy rules instance.
	 */
	public function __construct(
		Iyzico_Installment_Product_Meta $product_meta,
		Iyzico_Installment_Taxonomy_Rules $taxonomy_rules
	) {
		$this->_product_meta   = $product_meta;
		$this->_taxonomy_rules = $taxonomy_rules;
	}

	/**
	 * Decide whether installment display should be enabled for a product.
	 *
	 * Precedence:
	 *   1. Product-level override ('enable' or 'disable') always wins.
	 *   2. Otherwise, the category/brand rule applies if one exists.
	 *   3. Otherwise, default to enabled (true).
	 *
	 * @param WC_Product|int $product Product instance or ID.
	 *
	 * @return bool
	 */
	public function isEnabledForProduct( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! is_a( $product, 'WC_Product' ) ) {
			// No product context to evaluate — don't block display.
			return true;
		}

		// 1) Product-level override has the final say.
		$override = $this->_product_meta->getOverride( $product );
		if ( 'enable' === $override ) {
			return true;
		}
		if ( 'disable' === $override ) {
			return false;
		}

		// 2) No product override — fall back to the category/brand rule.
		$taxonomy_rule = $this->_taxonomy_rules->getRuleForProduct( $product );
		if ( 'enable' === $taxonomy_rule ) {
			return true;
		}
		if ( 'disable' === $taxonomy_rule ) {
			return false;
		}

		// 3) No rule at all — preserve original plugin behaviour.
		return true;
	}
}