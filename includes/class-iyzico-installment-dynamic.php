<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dynamic Installment System for Variable Products.
 *
 * @package Iyzico_Installment
 * @category Core
 * @author Iyzico
 * @license GPLv2 or later
 * @link https://iyzico.com
 */
class Iyzico_Installment_Dynamic {

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

		// Only initialize if dynamic installments are enabled
		if ( $this->_settings->isDynamicInstallmentsEnabled() ) {
			$this->_initHooks();
		}
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function _initHooks() {
		add_action(
			'wp_ajax_get_installment_options',
			array( $this, 'getInstallmentOptions' )
		);
		add_action(
			'wp_ajax_nopriv_get_installment_options',
			array( $this, 'getInstallmentOptions' )
		);
		add_shortcode(
			'dynamic_iyzico_installment',
			array( $this, 'dynamicInstallmentShortcode' )
		);
		add_action(
			'wp_footer',
			array( $this, 'addFooterScript' )
		);
		add_action(
			'wp_head',
			array( $this, 'addInstallmentStyles' )
		);
	}

	/**
	 * Add installment styles to the head
	 *
	 * @return void
	 */
	public function addInstallmentStyles() {
		if ( is_product() ) {
			?>
			<style>
				<?php
				// Add custom CSS if available
				$custom_css = $this->_settings->getCustomCss();
				if ( ! empty( $custom_css ) ) {
					// Comprehensive CSS sanitization
					echo $this->_sanitizeCss( $custom_css ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</style>
			<?php
		}
	}

	/**
	 * Add footer script for dynamic installment functionality
	 *
	 * @return void
	 */
	public function addFooterScript() {
		if ( is_product() ) {
			global $product;
			$current_product_id = $product->get_id();
			$current_price      = $product->get_price();

			// Apply VAT if enabled
			$price_with_vat = $this->_settings->calculatePriceWithVat( $current_price );
			?>
			<script type="text/javascript">
			window.installment_ajax = {
				ajax_url: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
				nonce: <?php echo wp_json_encode( wp_create_nonce( 'installment_nonce' ) ); ?>,
				product_id: <?php echo intval( $current_product_id ); ?>,
				server_price: <?php echo floatval( $current_price ); ?>,
				price_with_vat: <?php echo floatval( $price_with_vat ); ?>,
				vat_enabled: <?php echo $this->_settings->isVatEnabled() ? 'true' : 'false'; ?>,
				vat_rate: <?php echo floatval( $this->_settings->getVatRate() ); ?>,
				debug: <?php echo ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'true' : 'false'; ?>
			};
			
			// Debug function - only logs in debug mode
			function debugLog(message, data) {
				if (window.installment_ajax.debug && typeof console !== 'undefined') {
					if (data !== undefined) {
						console.log(message, data);
					} else {
						console.log(message);
					}
				}
			}
			
			debugLog('=== NEW PAGE LOAD ===');
			debugLog('Product ID:', window.installment_ajax.product_id);
			debugLog('Server Price:', window.installment_ajax.server_price);
			debugLog('Price with VAT:', window.installment_ajax.price_with_vat);
			debugLog('VAT Enabled:', window.installment_ajax.vat_enabled);
			
			jQuery(document).ready(function($) {
				$('.dynamic-iyzico-installment').empty();
				
				setTimeout(function() {
					loadPrice();
				}, 800);
				
				$(document).on('found_variation', 'form.variations_form', function(event, variation) {
					debugLog('=== VARIATION FOUND ===');
					debugLog('Variation price:', variation.display_price);
					
					if (variation && variation.display_price) {
						var finalPrice = variation.display_price;
						
						// Apply VAT if enabled
						if (window.installment_ajax.vat_enabled) {
							finalPrice = finalPrice * (1 + (window.installment_ajax.vat_rate / 100));
						}
						
						debugLog('Final price with VAT:', finalPrice);
						loadInstallments(finalPrice);
					}
				});

				$(document).on('reset_data', 'form.variations_form', function() {
					debugLog('=== VARIATION RESET ===');
					$('.dynamic-iyzico-installment').html('<p><?php echo esc_js( __( 'PLEASE_SELECT_OPTION', 'iyzico-installment' ) ); ?></p>');
				});

				function loadPrice() {
					var isVariableProduct = $('form.variations_form').length > 0;
					
					if (isVariableProduct) {
						$('.dynamic-iyzico-installment').html('<p><?php echo esc_js( __( 'PLEASE_SELECT_OPTION', 'iyzico-installment' ) ); ?></p>');
						return;
					}
					
					var price = window.installment_ajax.price_with_vat;
					debugLog('Using price with VAT:', price);
					
					if (price > 0) {
						loadInstallments(price);
					} else {
						$('.dynamic-iyzico-installment').html('<p><?php echo esc_js( __( 'PRICE_INFO_NOT_FOUND', 'iyzico-installment' ) ); ?></p>');
					}
				}

				function loadInstallments(price) {
					debugLog('=== LOADING INSTALLMENTS ===');
					debugLog('Product ID:', window.installment_ajax.product_id);
					debugLog('Price:', price);
					
					var containers = $('.dynamic-iyzico-installment');
					
					if (containers.length > 0 && price > 0) {
						containers.html('<p><?php echo esc_js( __( 'INSTALLMENTS_LOADING', 'iyzico-installment' ) ); ?></p>');
						
						$.ajax({
							url: window.installment_ajax.ajax_url,
							type: 'POST',
							data: {
								action: 'get_installment_options',
								price: price,
								product_id: window.installment_ajax.product_id,
								nonce: window.installment_ajax.nonce
							},
							cache: false,
							success: function(response) {
								debugLog('=== AJAX SUCCESS ===');
								debugLog('Response:', response);
								
								if (response.success) {
									// response.data is already sanitized with wp_kses_post on server side
									containers.html(response.data);
								} else {
									// Escape error messages for security
									var errorMsg = response.data || '<?php echo esc_js( __( 'UNKNOWN_ERROR', 'iyzico-installment' ) ); ?>';
									containers.html('<p><?php echo esc_js( __( 'ERROR_PREFIX', 'iyzico-installment' ) ); ?> ' + $('<div>').text(errorMsg).html() + '</p>');
								}
							},
							error: function(xhr, status, error) {
								debugLog('=== AJAX ERROR ===');
								debugLog('Status:', status);
								debugLog('Error:', error);
								
								// Don't expose technical error details to users
								containers.html('<p><?php echo esc_js( __( 'CONNECTION_ERROR_RETRY', 'iyzico-installment' ) ); ?></p>');
								
								// Log technical details for debugging (only in debug mode)
								if (window.installment_ajax.debug) {
									debugLog('XHR Status:', xhr.status);
									debugLog('XHR Response:', xhr.responseText);
								}
							}
						});
					}
				}
			});
			</script>
			<?php
		}
	}

	/**
	 * Render dynamic installment shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public function dynamicInstallmentShortcode( $atts ) {
		return '<div class="dynamic-iyzico-installment">' . esc_html__( 'INSTALLMENTS_LOADING', 'iyzico-installment' ) . '</div>';
	}

	/**
	 * Get installment options via AJAX
	 *
	 * @return void
	 */
	public function getInstallmentOptions() {
		// Rate limiting check
		$this->_checkRateLimit();

		// Nonce check
		if ( ! isset( $_POST['nonce'] )
			|| ! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
				'installment_nonce'
			)
		) {
			wp_send_json_error( __( 'SECURITY_CHECK_FAILED', 'iyzico-installment' ) );
		}

		// Check if dynamic installments are enabled
		if ( ! $this->_settings->isDynamicInstallmentsEnabled() ) {
			wp_send_json_error( __( 'DYNAMIC_INSTALLMENTS_NOT_ENABLED', 'iyzico-installment' ) );
		}

		// Check if API credentials are set
		if ( ! $this->_settings->hasCredentials() ) {
			wp_send_json_error( __( 'API_CREDENTIALS_NOT_CONFIGURED', 'iyzico-installment' ) );
		}

		// Input validation and sanitization
		$price      = isset( $_POST['price'] ) ? floatval( $_POST['price'] ) : 0;
		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

		// Price validation
		if ( $price <= 0 || $price > 1000000 ) {
			wp_send_json_error( __( 'INVALID_PRICE', 'iyzico-installment' ) );
		}

		// Product ID validation
		if ( $product_id <= 0 ) {
			wp_send_json_error( __( 'INVALID_PRODUCT_ID', 'iyzico-installment' ) );
		}

		// Check that the product actually exists
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( __( 'PRODUCT_NOT_FOUND', 'iyzico-installment' ) );
		}

		// Log using WooCommerce logger
		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();
			$logger->info(
				"Dynamic installment request - Product ID: $product_id, Price: $price",
				array( 'source' => 'iyzico-installment' )
			);
		}

		// Cache busting
		nocache_headers();

		// Get installment info using the API
		$installment_info = $this->_api->getInstallmentInfo( $price, '' );

		if ( is_wp_error( $installment_info ) ) {
			wp_send_json_error( $installment_info->get_error_message() );
		}

		// Render the installment table
		$installment_html = $this->_renderInstallmentTable( $installment_info );

		// Output sanitization
		wp_send_json_success( wp_kses_post( $installment_html ) );
	}

	/**
	 * Rate limiting check for AJAX requests
	 * Prevents DDoS attacks by limiting requests per IP
	 *
	 * @return void
	 */
	private function _checkRateLimit() {
		$user_ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		if ( empty( $user_ip ) ) {
			return; // Skip if IP cannot be determined
		}

		$transient_key = 'iyzico_rate_limit_' . md5( $user_ip );
		$requests      = get_transient( $transient_key );

		// Allow max 15 requests per minute per IP
		if ( $requests && $requests >= 15 ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				$logger = wc_get_logger();
				$logger->warning(
					"Rate limit exceeded for IP: $user_ip",
					array( 'source' => 'iyzico-installment' )
				);
			}
			wp_send_json_error( __( 'TOO_MANY_REQUESTS', 'iyzico-installment' ) );
		}

		// Increment request counter
		set_transient( $transient_key, ( $requests + 1 ), 60 ); // 1 minute
	}

	/**
	 * Comprehensive CSS sanitization
	 * Removes potentially dangerous CSS constructs
	 *
	 * @param string $css CSS to sanitize.
	 *
	 * @return string
	 */
	private function _sanitizeCss( $css ) {
		// Remove all HTML tags first
		$css = wp_strip_all_tags( $css );

		// Define dangerous patterns
		$dangerous_patterns = array(
			// JavaScript related
			'javascript:',
			'expression(',
			'eval(',
			'vbscript:',
			'mocha:',
			'livescript:',
			// Event handlers
			'onclick=',
			'onload=',
			'onerror=',
			'onmouseover=',
			'onfocus=',
			'onblur=',
			// Imports and bindings
			'@import',
			'behavior:',
			'-moz-binding:',
			'binding:',
			// Data URLs and other protocols
			'data:',
			'url(javascript:',
			'url(data:',
			'url(vbscript:',
			// Script tags
			'<script',
			'</script',
			'<style',
			'</style',
		);

		// Remove dangerous patterns (case insensitive)
		$css = str_ireplace( $dangerous_patterns, '', $css );

		// Additional security: only allow alphanumeric, CSS-safe characters
		if ( ! preg_match( '/^[a-zA-Z0-9\s\.\#\-_:;{}(),\[\]"%\/\*\+>~=!@]*$/', $css ) ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				$logger = wc_get_logger();
				$logger->warning(
					'CSS sanitization failed: Invalid characters detected',
					array( 'source' => 'iyzico-installment' )
				);
			}
			return '';
		}

		// Validate that it looks like CSS (has selectors and declarations)
		if ( ! preg_match( '/[{;}]/', $css ) ) {
			return '';
		}

		// Final HTML tag check (double safety)
		if ( preg_match( '/<[^>]*>/', $css ) ) {
			return '';
		}

		return $css;
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
}
