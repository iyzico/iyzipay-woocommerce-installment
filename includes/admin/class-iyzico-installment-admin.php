<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Iyzipay\Model\ApiTest;
use Iyzipay\Options;

/**
 * Iyzico Installment Admin class.
 *
 * @package Iyzico_Installment
 * @category Admin
 * @author Iyzico
 * @license GPLv2 or later
 * @link https://iyzico.com
 */
class Iyzico_Installment_Admin {

	/**
	 * Settings instance
	 *
	 * @var Iyzico_Installment_Settings
	 */
	private $_settings;

	/**
	 * Constructor
	 *
	 * @param Iyzico_Installment_Settings $settings Settings instance.
	 */
	public function __construct( Iyzico_Installment_Settings $settings ) {
		$this->_settings = $settings;

		add_action( 'admin_menu', array( $this, 'registerMenu' ) );
		add_action( 'admin_init', array( $this, 'registerSettings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );

		// Ajax handlers
		add_action( 'wp_ajax_iyzico_test_api', array( $this, 'ajaxTestApi' ) );
	}

	/**
	 * Register admin menu
	 *
	 * @return void
	 */
	public function registerMenu() {
		add_menu_page(
			__( 'IYZICO_INSTALLMENT_OPTIONS', 'iyzico-installment' ),
			__( 'IYZICO_INSTALLMENT_SHORT', 'iyzico-installment' ),
			'manage_options',
			'iyzico_installment',
			array( $this, 'renderSettingsPage' ),
			'dashicons-money-alt',
			30
		);
	}

	/**
	 * Register settings
	 *
	 * @return void
	 */
	public function registerSettings() {
		register_setting(
			'iyzico_installment_options',
			Iyzico_Installment_Settings::OPTION_KEY,
			array(
				'sanitize_callback' => array( $this, 'sanitizeSettings' ),
			)
		);
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input Settings input.
	 *
	 * @return array
	 */
	public function sanitizeSettings( $input ) {
		$sanitized = array();

		if ( isset( $input['api_key'] ) ) {
			$sanitized['api_key'] = sanitize_text_field( $input['api_key'] );
		}

		if ( isset( $input['secret_key'] ) ) {
			$sanitized['secret_key'] = sanitize_text_field( $input['secret_key'] );
		}

		if ( isset( $input['integration_type'] ) ) {
			$sanitized['integration_type'] = sanitize_text_field( $input['integration_type'] );
		}

		if ( isset( $input['mode'] ) ) {
			$sanitized['mode'] = in_array( $input['mode'], array( 'sandbox', 'live' ) )
				? $input['mode']
				: 'sandbox';
		}

		if ( isset( $input['enable_vat'] ) ) {
			$sanitized['enable_vat'] = (bool) $input['enable_vat'];
		}

		if ( isset( $input['vat_rate'] ) ) {
			$sanitized['vat_rate'] = floatval( $input['vat_rate'] );
		}

		if ( isset( $input['enable_dynamic_installments'] ) ) {
			$sanitized['enable_dynamic_installments'] = (bool) $input['enable_dynamic_installments'];
		}

		if ( isset( $input['custom_css'] ) ) {
			// CSS security check
			$custom_css = wp_unslash( $input['custom_css'] );
			// Remove harmful script tags and JavaScript code
			$custom_css = str_replace(
				array( '<script', '</script', 'javascript:', 'expression(', 'eval(', 'onclick=', 'onload=' ),
				'',
				$custom_css
			);
			// Remove HTML tags (CSS only)
			$custom_css              = wp_strip_all_tags( $custom_css );
			$sanitized['custom_css'] = $custom_css;
		}

		if ( isset( $input['enable_dynamic_installments'] ) ) {
			$sanitized['enable_dynamic_installments'] = (bool) $input['enable_dynamic_installments'];
		}

		return $sanitized;
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook Current hook.
	 *
	 * @return void
	 */
	public function enqueueScripts( $hook ) {
		if ( 'toplevel_page_iyzico_installment' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'iyzico-installment-admin',
			IYZI_INSTALLMENT_ASSETS_URL . '/css/iyzico-installment-admin.css',
			array(),
			IYZI_INSTALLMENT_VERSION
		);

		wp_enqueue_script(
			'iyzico-installment-admin',
			IYZI_INSTALLMENT_ASSETS_URL . '/js/iyzico-installment.js',
			array( 'jquery' ),
			IYZI_INSTALLMENT_VERSION,
			true
		);

		wp_localize_script(
			'iyzico-installment-admin',
			'iyzicoInstallment',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'iyzico_installment_nonce' ),
				'copySuccess'       => __( 'COPY_SUCCESS', 'iyzico-installment' ),
				'copyError'         => __( 'COPY_ERROR', 'iyzico-installment' ),
				'emptyCredentials'  => __( 'EMPTY_CREDENTIALS', 'iyzico-installment' ),
				'testing'           => __( 'TESTING', 'iyzico-installment' ),
				'connected'         => __( 'CONNECTED', 'iyzico-installment' ),
				'disconnected'      => __( 'DISCONNECTED', 'iyzico-installment' ),
				'connectionSuccess' => __( 'CONNECTION_SUCCESS', 'iyzico-installment' ),
				'connectionError'   => __( 'CONNECTION_ERROR', 'iyzico-installment' ),
			)
		);
	}

	/**
	 * Render settings page
	 *
	 * @return void
	 */
	public function renderSettingsPage() {
		$settings = $this->_settings->getAll();
		?>
		<div class="wrap iyzico-settings-wrapper">
			<h1><?php echo esc_html__( 'IYZICO_INSTALLMENT_OPTIONS', 'iyzico-installment' ); ?></h1>

			<div class="iyzico-admin-container">
				<div class="iyzico-admin-header">
					<div class="iyzico-logo-container">
						<?php
						// Display logo using WordPress functions
						if ( function_exists( 'wp_get_attachment_image' ) ) {
							// Check if logo exists in media library
							$logo_attachment_id = attachment_url_to_postid(
								IYZI_INSTALLMENT_ASSETS_URL . '/images/iyzico-logo.svg'
							);

							if ( $logo_attachment_id ) {
								echo wp_get_attachment_image(
									$logo_attachment_id,
									'full',
									false,
									array(
										'class' => 'iyzico-logo',
										'alt'   => esc_attr__( 'IYZICO', 'iyzico-installment' ),
									)
								);
							} else {
								// Use SVG directly with proper wrapper for better accessibility
								$svg_url = esc_url(
									IYZI_INSTALLMENT_ASSETS_URL . '/images/iyzico-logo.svg'
								);
								?>
								<div class="iyzico-logo-wrapper">
									<object type="image/svg+xml"
											data="<?php echo esc_url( $svg_url ); ?>"
											class="iyzico-logo">
										<?php esc_html_e( 'IYZICO', 'iyzico-installment' ); ?>
									</object>
								</div>
								<?php
							}
						} else {
							// Use SVG directly with proper wrapper for better accessibility
							$svg_url = esc_url(
								IYZI_INSTALLMENT_ASSETS_URL . '/images/iyzico-logo.svg'
							);
							?>
							<div class="iyzico-logo-wrapper">
								<object type="image/svg+xml"
										data="<?php echo esc_url( $svg_url ); ?>"
										class="iyzico-logo">
									<?php esc_html_e( 'IYZICO', 'iyzico-installment' ); ?>
								</object>
							</div>
							<?php
						}
						?>
						<span class="iyzico-version">v<?php echo esc_html( IYZI_INSTALLMENT_VERSION ); ?></span>
					</div>
				</div>

				<div class="iyzico-admin-content">
					<form method="post" action="options.php">
						<?php settings_fields( 'iyzico_installment_options' ); ?>

						<div class="iyzico-settings-section">
							<h2><?php echo esc_html__( 'API_SETTINGS', 'iyzico-installment' ); ?></h2>
							
							<div class="iyzico-form-group">
								<label for="iyzico_mode">
									<?php echo esc_html__( 'ENVIRONMENT', 'iyzico-installment' ); ?>
								</label>
								<select id="iyzico_mode"
										name="<?php echo esc_attr( Iyzico_Installment_Settings::OPTION_KEY ); ?>[mode]"
										class="iyzico-form-control">
									<option value="sandbox" <?php selected( $settings['mode'], 'sandbox' ); ?>>
										<?php echo esc_html__( 'TEST_SANDBOX', 'iyzico-installment' ); ?>
									</option>
									<option value="live" <?php selected( $settings['mode'], 'live' ); ?>>
										<?php echo esc_html__( 'LIVE', 'iyzico-installment' ); ?>
									</option>
								</select>
								<p class="description">
									<?php echo esc_html__( 'TEST_ENV_WARNING', 'iyzico-installment' ); ?>
								</p>
							</div>

							<div class="iyzico-form-group">
								<label><?php echo esc_html__( 'API_CONNECTION_STATUS', 'iyzico-installment' ); ?></label>
								<div class="iyzico-api-status <?php echo esc_attr( $this->_getConnectionStatusClass() ); ?>">
									<?php echo esc_html( $this->_getConnectionStatusText() ); ?>
								</div>
								<p class="description">
									<?php echo esc_html__( 'API_STATUS_DESCRIPTION', 'iyzico-installment' ); ?>
								</p>
							</div>
							
							<div class="iyzico-form-group">
								<label for="iyzico_api_key">
									<?php echo esc_html__( 'API_KEY', 'iyzico-installment' ); ?>
								</label>
								<input type="text"
										id="iyzico_api_key"
										name="<?php echo esc_attr( Iyzico_Installment_Settings::OPTION_KEY ); ?>[api_key]"
										value="<?php echo esc_attr( $settings['api_key'] ); ?>"
										class="regular-text iyzico-form-control">
							</div>

							<div class="iyzico-form-group">
								<label for="iyzico_secret_key">
									<?php echo esc_html__( 'API_SECRET_KEY', 'iyzico-installment' ); ?>
								</label>
								<input type="password"
										id="iyzico_secret_key"
										name="<?php echo esc_attr( Iyzico_Installment_Settings::OPTION_KEY ); ?>[secret_key]"
										value="<?php echo esc_attr( $settings['secret_key'] ); ?>"
										class="regular-text iyzico-form-control">
							</div>

							<div class="iyzico-form-group">
								<button type="button" id="iyzico-test-api" class="button button-secondary">
									<?php echo esc_html__( 'TEST_API_CONNECTION', 'iyzico-installment' ); ?>
								</button>
							</div>
						</div>

						<div class="iyzico-settings-section">
							<h2><?php echo esc_html__( 'DISPLAY_SETTINGS', 'iyzico-installment' ); ?></h2>
							
							<div class="iyzico-form-group">
								<label for="iyzico_integration_type"><?php echo esc_html__( 'WORKING_MODE', 'iyzico-installment' ); ?></label>
								<select id="iyzico_integration_type" name="<?php echo esc_attr( Iyzico_Installment_Settings::OPTION_KEY ); ?>[integration_type]" class="iyzico-form-control">
									<option value="shortcode" <?php selected( $settings['integration_type'], 'shortcode' ); ?>>
										<?php echo esc_html__( 'SHORTCODE_ONLY', 'iyzico-installment' ); ?>
									</option>
									<option value="direct" <?php selected( $settings['integration_type'], 'direct' ); ?>>
										<?php echo esc_html__( 'SHORTCODE_PLUS_TAB', 'iyzico-installment' ); ?>
									</option>
								</select>
								<p class="description">
									<?php echo esc_html__( 'WORKING_MODE_DESCRIPTION', 'iyzico-installment' ); ?>
								</p>
							</div>

							<div class="iyzico-form-group">
								<label><?php echo esc_html__( 'SHORTCODE', 'iyzico-installment' ); ?></label>
								<div class="iyzico-shortcode-box">
									<code id="iyzico-shortcode">[iyzico_installment price="1000" bin=""]</code>
									<button type="button" class="iyzico-copy-shortcode button" data-target="iyzico-shortcode">
										<?php echo esc_html__( 'COPY', 'iyzico-installment' ); ?>
									</button>
								</div>
								<p class="description">
									<?php echo esc_html__( 'SHORTCODE_DESCRIPTION', 'iyzico-installment' ); ?>
								</p>
							</div>
						</div>

						<div class="iyzico-settings-section">
							<h2><?php echo esc_html__( 'VAT_DYNAMIC_SETTINGS', 'iyzico-installment' ); ?></h2>
							
							<div class="iyzico-form-group">
								<label>
									<input type="checkbox" name="<?php echo esc_attr( Iyzico_Installment_Settings::OPTION_KEY ); ?>[enable_vat]" value="1" <?php checked( $settings['enable_vat'], true ); ?>>
									<?php echo esc_html__( 'VAT_INCLUDED_PRICE', 'iyzico-installment' ); ?>
								</label>
								<p class="description">
									<?php echo esc_html__( 'VAT_INCLUDED_DESCRIPTION', 'iyzico-installment' ); ?>
								</p>
							</div>

							<div class="iyzico-form-group">
								<label for="iyzico_vat_rate"><?php echo esc_html__( 'VAT_RATE', 'iyzico-installment' ); ?></label>
								<input type="number" id="iyzico_vat_rate" name="<?php echo esc_attr( Iyzico_Installment_Settings::OPTION_KEY ); ?>[vat_rate]" 
									value="<?php echo esc_attr( $settings['vat_rate'] ); ?>" min="0" max="100" step="0.01" class="small-text iyzico-form-control">
								<p class="description">
									<?php echo esc_html__( 'VAT_RATE_DESCRIPTION', 'iyzico-installment' ); ?>
								</p>
							</div>

							<div class="iyzico-form-group">
								<label>
									<input type="checkbox" name="<?php echo esc_attr( Iyzico_Installment_Settings::OPTION_KEY ); ?>[enable_dynamic_installments]" value="1" <?php checked( $settings['enable_dynamic_installments'], true ); ?>>
									<?php echo esc_html__( 'DYNAMIC_INSTALLMENT_ACTIVE', 'iyzico-installment' ); ?>
								</label>
								<p class="description">
									<?php echo esc_html__( 'DYNAMIC_INSTALLMENT_DESCRIPTION', 'iyzico-installment' ); ?>
								</p>
							</div>

							<div class="iyzico-form-group">
								<label><?php echo esc_html__( 'DYNAMIC_INSTALLMENT_SHORTCODE', 'iyzico-installment' ); ?></label>
								<div class="iyzico-shortcode-box">
									<code id="iyzico-dynamic-shortcode">[dynamic_iyzico_installment]</code>
									<button type="button" class="iyzico-copy-shortcode button" data-target="iyzico-dynamic-shortcode">
										<?php echo esc_html__( 'COPY', 'iyzico-installment' ); ?>
									</button>
								</div>
								<p class="description">
									<?php echo esc_html__( 'DYNAMIC_SHORTCODE_DESCRIPTION', 'iyzico-installment' ); ?>
								</p>
							</div>
						</div>

						<div class="iyzico-settings-section">
							<h2><?php echo esc_html__( 'CUSTOM_CSS_SETTINGS', 'iyzico-installment' ); ?></h2>
							
							<div class="iyzico-form-group">
								<label for="iyzico_custom_css"><?php echo esc_html__( 'CUSTOM_CSS_CODES', 'iyzico-installment' ); ?></label>
								<textarea id="iyzico_custom_css" name="<?php echo esc_attr( Iyzico_Installment_Settings::OPTION_KEY ); ?>[custom_css]" 
									rows="10" cols="50" class="large-text code iyzico-form-control" placeholder="/* Buraya özel CSS kodlarınızı yazabilirsiniz */&#10;&#10;.iyzico-installment-table {&#10;    border: 1px solid #ddd;&#10;    border-radius: 5px;&#10;}&#10;&#10;.iyzico-installment-amount {&#10;    color: #27ae60;&#10;    font-weight: bold;&#10;}"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
								<p class="description">
									<?php echo esc_html__( 'CUSTOM_CSS_DESCRIPTION', 'iyzico-installment' ); ?>
								</p>
								<p class="description">
									<strong><?php echo esc_html__( 'EXAMPLE_CSS_CLASSES', 'iyzico-installment' ); ?></strong><br>
									<code>.iyzico-installment-table</code> - <?php echo esc_html__( 'INSTALLMENT_TABLE', 'iyzico-installment' ); ?><br>
									<code>.iyzico-installment-amount</code> - <?php echo esc_html__( 'INSTALLMENT_AMOUNTS', 'iyzico-installment' ); ?><br>
									<code>.iyzico-installment-header</code> - <?php echo esc_html__( 'TABLE_HEADER', 'iyzico-installment' ); ?><br>
									<code>.iyzico-card-logo</code> - <?php echo esc_html__( 'CARD_LOGOS', 'iyzico-installment' ); ?>
								</p>
							</div>
						</div>
						
						<?php submit_button( esc_html__( 'SAVE_SETTINGS', 'iyzico-installment' ), 'primary', 'submit', true ); ?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get connection status class
	 *
	 * @return string
	 */
	/**
	 * Get connection status class
	 *
	 * @return string
	 */
	private function _getConnectionStatusClass() {
		if ( ! $this->_settings->hasCredentials() ) {
			return 'disconnected';
		}

		$status = get_transient( 'iyzico_installment_api_status' );
		return $status ? 'connected' : 'disconnected';
	}

	/**
	 * Get connection status text
	 *
	 * @return string
	 */
	private function _getConnectionStatusText() {
		if ( ! $this->_settings->hasCredentials() ) {
			return __( 'CONNECTION_NOT_CONFIGURED', 'iyzico-installment' );
		}

		$status = get_transient( 'iyzico_installment_api_status' );
		return $status ? __( 'CONNECTED', 'iyzico-installment' ) : __( 'DISCONNECTED', 'iyzico-installment' );
	}

	/**
	 * Ajax handler for testing API connection
	 *
	 * @return void
	 */
	public function ajaxTestApi() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] )
			|| ! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
				'iyzico_installment_nonce'
			)
		) {
			wp_send_json_error(
				array( 'message' => __( 'SECURITY_VERIFICATION_FAILED', 'iyzico-installment' ) )
			);
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'INSUFFICIENT_PERMISSIONS', 'iyzico-installment' ) )
			);
		}

		// Get credentials
		$api_key    = isset( $_POST['api_key'] )
			? sanitize_text_field( wp_unslash( $_POST['api_key'] ) )
			: '';
		$secret_key = isset( $_POST['secret_key'] )
			? sanitize_text_field( wp_unslash( $_POST['secret_key'] ) )
			: '';

		if ( empty( $api_key ) || empty( $secret_key ) ) {
			wp_send_json_error(
				array( 'message' => __( 'EMPTY_CREDENTIALS', 'iyzico-installment' ) )
			);
		}

		// Test API
		try {
			$options = new Options();
			$options->setApiKey( $api_key );
			$options->setSecretKey( $secret_key );

			// Environment URL
			$mode     = isset( $_POST['mode'] )
				? sanitize_text_field( wp_unslash( $_POST['mode'] ) )
				: 'sandbox';
			$base_url = ( $mode === 'live' )
				? 'https://api.iyzipay.com'
				: 'https://sandbox-api.iyzipay.com';
			$options->setBaseUrl( $base_url );

			$test = ApiTest::retrieve( $options );

			if ( $test->getStatus() === 'success' ) {
				// Update settings
				$this->_settings->updateMultiple(
					array(
						'api_key'    => $api_key,
						'secret_key' => $secret_key,
						'mode'       => $mode,
					)
				);

				// Store connection status
				set_transient( 'iyzico_installment_api_status', true, DAY_IN_SECONDS );

				wp_send_json_success();
			} else {
				delete_transient( 'iyzico_installment_api_status' );
				wp_send_json_error( array( 'message' => $test->getErrorMessage() ) );
			}
		} catch ( \Exception $e ) {
			delete_transient( 'iyzico_installment_api_status' );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}
}
