<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Iyzico Installment Taxonomy Rules class.
 *
 * Lets the merchant define a general "enable / disable" installment rule per
 * product category (product_cat) and per product brand (product_brand,
 * WooCommerce's native brand taxonomy). These rules are the "general rule"
 * layer sitting between the plugin's global settings and the per-product
 * override handled by Iyzico_Installment_Product_Meta.
 *
 * This class owns its own option key and its own save routine (independent
 * of the WordPress Settings API used by Iyzico_Installment_Admin) so it can
 * be rendered inside the same admin form without conflicting with the
 * existing settings_fields()/register_setting() flow.
 *
 * @package  Iyzico_Installment
 * @category Admin
 * @author   Iyzico
 * @license  GPLv2 or later
 * @link     https://iyzico.com
 */
class Iyzico_Installment_Taxonomy_Rules {

	/**
	 * Option key used to store taxonomy rules.
	 */
	const OPTION_KEY = 'iyzico_installment_taxonomy_rules';

	/**
	 * Nonce action/name used to protect the save request.
	 */
	const NONCE_ACTION = 'iyzico_installment_taxonomy_rules_save';
	const NONCE_NAME   = 'iyzico_installment_taxonomy_rules_nonce';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'maybeSave' ) );
	}

	/**
	 * Taxonomies managed by this feature, mapped to their section label.
	 *
	 * @return array<string,string> taxonomy => label
	 */
	private function _getManagedTaxonomies() {
		return array(
			'product_cat'   => __( 'INSTALLMENT_RULES_BY_CATEGORY', 'iyzico-installment' ),
			'product_brand' => __( 'INSTALLMENT_RULES_BY_BRAND', 'iyzico-installment' ),
		);
	}

	/**
	 * Get the Select2 placeholder text for a specific managed taxonomy.
	 *
	 * Kept separate per taxonomy so the placeholder can read "Select
	 * category..." vs "Select brand..." instead of one generic string
	 * shared by both selects.
	 *
	 * @param string $taxonomy Taxonomy name (e.g. 'product_cat').
	 *
	 * @return string
	 */
	private function _getSelectPlaceholder( $taxonomy ) {
		$placeholders = array(
			'product_cat'   => __( 'INSTALLMENT_RULES_SELECT_PLACEHOLDER_CATEGORY', 'iyzico-installment' ),
			'product_brand' => __( 'INSTALLMENT_RULES_SELECT_PLACEHOLDER_BRAND', 'iyzico-installment' ),
		);

		return isset( $placeholders[ $taxonomy ] )
			? $placeholders[ $taxonomy ]
			: __( 'INSTALLMENT_RULES_SELECT_PLACEHOLDER', 'iyzico-installment' );
	}

	/**
	 * Get all stored rules.
	 *
	 * @return array<string,array<int,string>> taxonomy => [ term_id => 'enable'|'disable' ]
	 */
	public function getAll() {
		$stored = get_option( self::OPTION_KEY, array() );

		$rules = array();
		foreach ( array_keys( $this->_getManagedTaxonomies() ) as $taxonomy ) {
			$rules[ $taxonomy ] = isset( $stored[ $taxonomy ] ) && is_array( $stored[ $taxonomy ] )
				? $stored[ $taxonomy ]
				: array();
		}

		return $rules;
	}

	/**
	 * Get the rule for a specific term.
	 *
	 * @param string $taxonomy Taxonomy name (e.g. 'product_cat').
	 * @param int    $term_id  Term ID.
	 *
	 * @return string 'enable', 'disable', or '' (no rule / default).
	 */
	public function getRule( $taxonomy, $term_id ) {
		$rules    = $this->getAll();
		$term_id  = intval( $term_id );

		if ( ! isset( $rules[ $taxonomy ][ $term_id ] ) ) {
			return '';
		}

		$value = $rules[ $taxonomy ][ $term_id ];

		return in_array( $value, array( 'enable', 'disable' ), true ) ? $value : '';
	}

	/**
	 * Get the rules that apply to a given product, across all managed
	 * taxonomies the product belongs to.
	 *
	 * When a product belongs to multiple terms with conflicting rules
	 * (e.g. two categories, one enabled one disabled), 'disable' wins ---
	 * this is the safer default so installment isn't shown where a
	 * merchant explicitly turned it off for at least one grouping.
	 *
	 * @param WC_Product $product Product instance.
	 *
	 * @return string 'enable', 'disable', or '' (no applicable rule).
	 */
	public function getRuleForProduct( $product ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return '';
		}

		$result = '';

		foreach ( array_keys( $this->_getManagedTaxonomies() ) as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$term_ids = wc_get_product_term_ids( $product->get_id(), $taxonomy );

			foreach ( $term_ids as $term_id ) {
				$rule = $this->getRule( $taxonomy, $term_id );

				if ( 'disable' === $rule ) {
					return 'disable';
				}

				if ( 'enable' === $rule ) {
					$result = 'enable';
				}
			}
		}

		return $result;
	}

	/**
	 * Render the settings section: for each managed taxonomy, two
	 * WooCommerce-style Select2 multi-select boxes ("Enable for these"
	 * and "Disable for these"). Uses WooCommerce's own bundled Select2
	 * (the 'wc-enhanced-select' script/style already shipped with
	 * WooCommerce — no extra library is loaded by this plugin).
	 *
	 * Intended to be called from Iyzico_Installment_Admin::renderSettingsPage(),
	 * inside the existing settings <form>, before the submit button.
	 *
	 * @return void
	 */
	public function renderFields() {
		$rules = $this->getAll();
		?>
		<div class="iyzico-settings-section">
			<h2><?php echo esc_html__( 'INSTALLMENT_RULES_SECTION_TITLE', 'iyzico-installment' ); ?></h2>
			<p class="description">
				<?php echo esc_html__( 'INSTALLMENT_RULES_SECTION_DESCRIPTION', 'iyzico-installment' ); ?>
			</p>

			<div class="iyzico-taxonomy-rules-grid">
				<?php foreach ( $this->_getManagedTaxonomies() as $taxonomy => $label ) : ?>
					<?php
					if ( ! taxonomy_exists( $taxonomy ) ) {
						continue;
					}

					$terms = get_terms(
						array(
							'taxonomy'   => $taxonomy,
							'hide_empty' => false,
							'orderby'    => 'name',
							'order'      => 'ASC',
						)
					);

					if ( is_wp_error( $terms ) || empty( $terms ) ) {
						continue;
					}

					$enabled_ids  = array();
					$disabled_ids = array();
					foreach ( $terms as $term ) {
						$current = isset( $rules[ $taxonomy ][ $term->term_id ] ) ? $rules[ $taxonomy ][ $term->term_id ] : '';
						if ( 'enable' === $current ) {
							$enabled_ids[] = $term->term_id;
						} elseif ( 'disable' === $current ) {
							$disabled_ids[] = $term->term_id;
						}
					}
					?>
					<div class="iyzico-form-group iyzico-taxonomy-rule-group">
						<label class="iyzico-taxonomy-group-title"><?php echo esc_html( $label ); ?></label>

						<div class="iyzico-taxonomy-select-row">
							<label for="iyzico-taxonomy-enable-<?php echo esc_attr( $taxonomy ); ?>" class="iyzico-taxonomy-select-label iyzico-taxonomy-select-label--enable">
								<?php echo esc_html__( 'INSTALLMENT_RULES_ENABLE_LABEL', 'iyzico-installment' ); ?>
							</label>
							<div class="iyzico-taxonomy-select-wrap">
								<select
									id="iyzico-taxonomy-enable-<?php echo esc_attr( $taxonomy ); ?>"
									name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $taxonomy ); ?>][enable][]"
									class="wc-enhanced-select"
									multiple="multiple"
									style="width:100%;"
									data-placeholder="<?php echo esc_attr( $this->_getSelectPlaceholder( $taxonomy ) ); ?>">
									<?php foreach ( $terms as $term ) : ?>
										<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( in_array( $term->term_id, $enabled_ids, true ), true ); ?>>
											<?php echo esc_html( $term->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="iyzico-taxonomy-select-row">
							<label for="iyzico-taxonomy-disable-<?php echo esc_attr( $taxonomy ); ?>" class="iyzico-taxonomy-select-label iyzico-taxonomy-select-label--disable">
								<?php echo esc_html__( 'INSTALLMENT_RULES_DISABLE_LABEL', 'iyzico-installment' ); ?>
							</label>
							<div class="iyzico-taxonomy-select-wrap">
								<select
									id="iyzico-taxonomy-disable-<?php echo esc_attr( $taxonomy ); ?>"
									name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $taxonomy ); ?>][disable][]"
									class="wc-enhanced-select"
									multiple="multiple"
									style="width:100%;"
									data-placeholder="<?php echo esc_attr( $this->_getSelectPlaceholder( $taxonomy ) ); ?>">
									<?php foreach ( $terms as $term ) : ?>
										<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( in_array( $term->term_id, $disabled_ids, true ), true ); ?>>
											<?php echo esc_html( $term->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<p class="description">
							<?php echo esc_html__( 'INSTALLMENT_RULES_CONFLICT_NOTE', 'iyzico-installment' ); ?>
						</p>
					</div>
				<?php endforeach; ?>
			</div>

			<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
		</div>
		<?php
	}

	/**
	 * Save the taxonomy rules when the settings form is submitted.
	 *
	 * Runs on admin_init (fires before options.php redirects), independent
	 * of the Settings API used for the plugin's main option group.
	 *
	 * @return void
	 */
	public function maybeSave() {
		if ( ! isset( $_POST[ self::NONCE_NAME ] )
			|| ! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ),
				self::NONCE_ACTION
			)
		) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$raw = isset( $_POST[ self::OPTION_KEY ] ) && is_array( $_POST[ self::OPTION_KEY ] )
			? wp_unslash( $_POST[ self::OPTION_KEY ] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			: array();

		$sanitized = array();

		foreach ( array_keys( $this->_getManagedTaxonomies() ) as $taxonomy ) {
			$sanitized[ $taxonomy ] = array();

			if ( empty( $raw[ $taxonomy ] ) || ! is_array( $raw[ $taxonomy ] ) ) {
				continue;
			}

			$enable_ids = isset( $raw[ $taxonomy ]['enable'] ) && is_array( $raw[ $taxonomy ]['enable'] )
				? array_map( 'intval', $raw[ $taxonomy ]['enable'] )
				: array();

			$disable_ids = isset( $raw[ $taxonomy ]['disable'] ) && is_array( $raw[ $taxonomy ]['disable'] )
				? array_map( 'intval', $raw[ $taxonomy ]['disable'] )
				: array();

			// Apply 'enable' first, then 'disable' — if a term was somehow
			// selected in both boxes, 'disable' wins (same tie-break
			// philosophy as getRuleForProduct()).
			foreach ( $enable_ids as $term_id ) {
				if ( $term_id > 0 ) {
					$sanitized[ $taxonomy ][ $term_id ] = 'enable';
				}
			}

			foreach ( $disable_ids as $term_id ) {
				if ( $term_id > 0 ) {
					$sanitized[ $taxonomy ][ $term_id ] = 'disable';
				}
			}
		}

		update_option( self::OPTION_KEY, $sanitized );
	}
}