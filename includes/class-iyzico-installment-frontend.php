<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Iyzico Installment Frontend class.
 *
 * @package  Iyzico_Installment
 * @category Core
 * @author   Iyzico
 * @license  GPLv2 or later
 * @link     https://iyzico.com
 */
class Iyzico_Installment_Frontend {

	/**
	 * Settings instance
	 *
	 * @var Iyzico_Installment_Settings
	 */
	private $_settings;

	/**
	 * API instance
	 *
	 * @var Iyzico_Installment_API
	 */
	private $_api;

	/**
	 * Constructor
	 *
	 * @param Iyzico_Installment_Settings $settings Settings instance.
	 * @param Iyzico_Installment_API      $api      API instance.
	 */
	public function __construct( Iyzico_Installment_Settings $settings, Iyzico_Installment_API $api ) {
		$this->_settings = $settings;
		$this->_api      = $api;

		add_shortcode( 'iyzico_installment', array( $this, 'renderShortcode' ) );

		// Add hooks for direct integration
		if ( $this->_settings->showProductTabs() ) {
			add_filter(
				'woocommerce_product_tabs',
				array( $this, 'addInstallmentTab' )
			);
			add_action(
				'wp_enqueue_scripts',
				array( $this, 'enqueueScripts' )
			);
		}
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @return void
	 */
	public function enqueueScripts() {
		if ( ! $this->_shouldLoadScripts() ) {
			return;
		}

		wp_enqueue_script(
			'iyzico-installment',
			IYZI_INSTALLMENT_ASSETS_URL . '/js/iyzico-installment.js',
			array( 'jquery' ),
			IYZI_INSTALLMENT_VERSION,
			true
		);

		wp_localize_script(
			'iyzico-installment',
			'iyzicoInstallment',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'iyzico_installment_nonce' ),
				'integrationType' => $this->_settings->getIntegrationType(),
				'isProductPage'   => is_product(),
				'productPrice'    => $this->_getProductPrice(),
				'installmentText' => __( 'INSTALLMENT', 'iyzico-installment' ),
				'totalText'       => __( 'TOTAL', 'iyzico-installment' ),
				'currencySymbol'  => get_woocommerce_currency_symbol(),
				'assetsUrl'       => IYZI_INSTALLMENT_ASSETS_URL,
			)
		);

		// Add custom CSS if provided
		$this->_addCustomCss();
	}

	/**
	 * Check if scripts should be loaded
	 *
	 * @return bool
	 */
	private function _shouldLoadScripts() {
		if ( ! $this->_settings->hasCredentials() ) {
			return false;
		}

		if ( is_product() && $this->_settings->showProductTabs() ) {
			return true;
		}

		global $post;
		if ( is_a( $post, 'WP_Post' )
			&& has_shortcode( $post->post_content, 'iyzico_installment' )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Get product price
	 *
	 * @return float
	 */
	private function _getProductPrice() {
		if ( ! is_product() ) {
			return 0;
		}

		global $post;
		$product = wc_get_product( $post );

		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return 0;
		}

		return $product->get_price();
	}

	/**
	 * Render shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public function renderShortcode( $atts ) {
		if ( ! $this->_settings->hasCredentials() ) {
			return '<p>' . esc_html__( 'API_CREDENTIALS_NOT_CONFIGURED', 'iyzico-installment' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'price' => $this->_getProductPrice(),
				'bin'   => '',
			),
			$atts,
			'iyzico_installment'
		);

		$price = floatval( $atts['price'] );
		$bin   = sanitize_text_field( $atts['bin'] );

		if ( $price <= 0 ) {
			return '<p>' . esc_html__( 'VALID_PRICE_NOT_SPECIFIED', 'iyzico-installment' ) . '</p>';
		}

		// Apply VAT if enabled
		$price = $this->_settings->calculatePriceWithVat( $price );

		$installment_info = $this->_api->getInstallmentInfo( $price, $bin );

		if ( is_wp_error( $installment_info ) ) {
			return '<p>' . esc_html( $installment_info->get_error_message() ) . '</p>';
		}

		wp_enqueue_script(
			'iyzico-installment',
			IYZI_INSTALLMENT_ASSETS_URL . '/js/iyzico-installment.js',
			array( 'jquery' ),
			IYZI_INSTALLMENT_VERSION,
			true
		);

		wp_localize_script(
			'iyzico-installment',
			'iyzicoInstallment',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'iyzico_installment_nonce' ),
				'integrationType' => $this->_settings->getIntegrationType(),
				'isProductPage'   => is_product(),
				'productPrice'    => $price,
				'installmentText' => __( 'INSTALLMENT', 'iyzico-installment' ),
				'totalText'       => __( 'TOTAL', 'iyzico-installment' ),
				'currencySymbol'  => get_woocommerce_currency_symbol(),
			)
		);

		// Add custom CSS if provided
		$this->_addCustomCss();

		return $this->_renderInstallmentTable( $installment_info );
	}

	/**
	 * Add installment tab to product pages
	 *
	 * @param array $tabs Existing tabs.
	 *
	 * @return array
	 */
	public function addInstallmentTab( $tabs ) {
		if ( ! is_product() || ! $this->_settings->hasCredentials() ) {
			return $tabs;
		}

		$price = $this->_getProductPrice();
		if ( $price <= 0 ) {
			return $tabs;
		}

		$tabs['iyzico_installment'] = array(
			'title'    => __( 'INSTALLMENT_OPTIONS', 'iyzico-installment' ),
			'priority' => 25,
			'callback' => array( $this, 'renderInstallmentTab' ),
		);

		return $tabs;
	}

	/**
	 * Render installment tab content
	 *
	 * @return void
	 */
	public function renderInstallmentTab() {
		$price = $this->_getProductPrice();

		// Apply VAT if enabled
		$price = $this->_settings->calculatePriceWithVat( $price );

		$installment_info = $this->_api->getInstallmentInfo( $price );

		if ( is_wp_error( $installment_info ) ) {
			echo '<p>' . esc_html( $installment_info->get_error_message() ) . '</p>';
			return;
		}

		echo wp_kses_post( $this->_renderInstallmentTable( $installment_info ) );
	}

	/**
	 * Render installment table
	 *
	 * @param array $installment_info Installment information.
	 *
	 * @return string
	 */
	private function _renderInstallmentTable( $installment_info ) {
		if ( empty( $installment_info['installmentDetails'] ) ) {
			return '<p>' . esc_html__( 'NO_INSTALLMENT_OPTIONS', 'iyzico-installment' ) . '</p>';
		}

		ob_start();
		?>
		<div class="iyzico-installment-container">
			<h3 class="iyzico-installment-title">
				<?php echo esc_html__( 'INSTALLMENT_OPTIONS', 'iyzico-installment' ); ?>
			</h3>

			<div class="iyzico-bank-grid">
				<?php foreach ( $installment_info['installmentDetails'] as $bank ) : ?>
					<div class="iyzico-bank-card"
						tabindex="0"
						aria-label="<?php echo esc_attr( $bank['bankName'] . ' - ' . $bank['cardFamilyName'] ); ?>">
						<div class="iyzico-bank-logo-top">
							<?php
							echo $this->_getBankLogo( $bank['bankName'], $bank['cardFamilyName'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</div>

						<div class="table-area">
							<table class="iyzico-installment-table"
									role="table"
									aria-label="<?php echo esc_attr( $bank['bankName'] ); ?> taksit tablosu">
								<thead>
									<tr>
										<th>
											<?php echo esc_html__( 'INSTALLMENT_COUNT', 'iyzico-installment' ); ?>
										</th>
										<th class="amount">
											<?php echo esc_html__( 'INSTALLMENT_AMOUNT', 'iyzico-installment' ); ?>
										</th>
										<th class="amount total">
											<?php echo esc_html__( 'TOTAL', 'iyzico-installment' ); ?>
										</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $bank['installmentPrices'] as $installment ) : ?>
										<tr>
											<td><?php echo esc_html( $installment['installmentNumber'] ); ?></td>
											<td class="amount">
												<?php echo wp_kses_post( wc_price( $installment['installmentPrice'] ) ); ?>
											</td>
											<td class="amount total">
												<?php echo wp_kses_post( wc_price( $installment['totalPrice'] ) ); ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get bank logo HTML
	 *
	 * @param string $bank_name   Bank name.
	 * @param string $card_family Card family name.
	 *
	 * @return string
	 */
	private function _getBankLogo( $bank_name, $card_family ) {
		$card_family_lower = strtolower( trim( $card_family ) );
		if ( strpos( $card_family_lower, 'bonus' ) !== false ) {
			return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/Bonus.png"
                    alt="' . esc_attr( $card_family ) . '"
                    class="bank-logo"
                    title="' . esc_attr( $card_family ) . '">';
		} elseif ( strpos( $card_family_lower, 'axess' ) !== false ) {
			return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/Axess.png"
                    alt="' . esc_attr( $card_family ) . '"
                    class="bank-logo"
                    title="' . esc_attr( $card_family ) . '">';
		} elseif ( strpos( $card_family_lower, 'maximum' ) !== false ) {
			return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/Maximum.png"
                    alt="' . esc_attr( $card_family ) . '"
                    class="bank-logo"
                    title="' . esc_attr( $card_family ) . '">';
		} elseif ( strpos( $card_family_lower, 'paraf' ) !== false ) {
			return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/Paraf.png"
                    alt="' . esc_attr( $card_family ) . '"
                    class="bank-logo"
                    title="' . esc_attr( $card_family ) . '">';
		} elseif ( strpos( $card_family_lower, 'cardfinans' ) !== false ) {
			return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/Cardfinans.png"
                    alt="' . esc_attr( $card_family ) . '"
                    class="bank-logo"
                    title="' . esc_attr( $card_family ) . '">';
		} elseif ( strpos( $card_family_lower, 'advantage' ) !== false ) {
			return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/Advantage.png"
                    alt="' . esc_attr( $card_family ) . '"
                    class="bank-logo"
                    title="' . esc_attr( $card_family ) . '">';
		} elseif ( strpos( $card_family_lower, 'world' ) !== false ) {
			return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/World.png"
                    alt="' . esc_attr( $card_family ) . '"
                    class="bank-logo"
                    title="' . esc_attr( $card_family ) . '">';
		} elseif ( strpos( $card_family_lower, 'sağlam' ) !== false
			|| strpos( $card_family_lower, 'saglam' ) !== false
		) {
			return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/SaglamKart.png"
                    alt="' . esc_attr( $card_family ) . '"
                    class="bank-logo"
                    title="' . esc_attr( $card_family ) . '">';
		} elseif ( strpos( $card_family_lower, 'combo' ) !== false ) {
			return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/BankkartCombo.png"
                    alt="' . esc_attr( $card_family ) . '"
                    class="bank-logo"
                    title="' . esc_attr( $card_family ) . '">';
		} elseif ( strpos( $card_family_lower, 'qnb' ) !== false
			|| strpos( $card_family_lower, 'cc' ) !== false
		) {
			return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/QNB-CC.png"
                    alt="' . esc_attr( $card_family ) . '"
                    class="bank-logo"
                    title="' . esc_attr( $card_family ) . '">';
		} else {
			return '<div class="bank-logo-default" title="' . esc_attr( $card_family ) . '">💳</div>';
		}
	}

	/**
	 * Add custom CSS to the frontend if provided
	 *
	 * @return void
	 */
	private function _addCustomCss() {
		$custom_css = $this->_settings->getCustomCss();

		if ( ! empty( $custom_css ) ) {
			// Sanitize CSS for security - Remove dangerous elements
			$custom_css = wp_strip_all_tags( $custom_css );
			$custom_css = str_replace(
				array(
					'<script',
					'</script',
					'javascript:',
					'expression(',
					'eval(',
					'onclick=',
					'onload=',
					'onerror=',
					'onmouseover=',
					'@import',
					'behavior:',
					'-moz-binding:',
					'vbscript:',
					'mocha:',
					'livescript:',
				),
				'',
				$custom_css
			);

			// Only allow if it contains basic CSS properties
			if ( preg_match( '/[{;}]/', $custom_css ) && ! preg_match( '/<[^>]*>/', $custom_css ) ) {
				// Add CSS to page with a unique handle to prevent conflicts
				wp_add_inline_style( 'iyzico-installment', $custom_css );
			}
		}
	}
}
