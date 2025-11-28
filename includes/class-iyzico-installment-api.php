<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Iyzipay\Model\InstallmentInfo;
use Iyzipay\Options;
use Iyzipay\Request\RetrieveInstallmentInfoRequest;

/**
 * Iyzico Installment API class.
 *
 * @package  Iyzico_Installment
 * @category Core
 * @author   Iyzico
 * @license  GPLv2 or later
 * @link     https://iyzico.com
 */
class Iyzico_Installment_API {

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

		// Register AJAX endpoints
		add_action(
			'wp_ajax_iyzico_get_installment_info',
			array( $this, 'ajaxGetInstallmentInfo' )
		);
		add_action(
			'wp_ajax_nopriv_iyzico_get_installment_info',
			array( $this, 'ajaxGetInstallmentInfo' )
		);
	}

	/**
	 * Get iyzico options
	 *
	 * @return \Iyzipay\Options
	 */
	private function _getOptions() {
		$options = new Options();
		$options->setApiKey( $this->_settings->getApiKey() );
		$options->setSecretKey( $this->_settings->getSecretKey() );
		$options->setBaseUrl( $this->_settings->getApiUrl() );

		return $options;
	}

	/**
	 * Get installment info
	 *
	 * @param float  $price      Product price.
	 * @param string $bin_number Credit card BIN number (optional).
	 *
	 * @return array|\WP_Error
	 */
	public function getInstallmentInfo( $price, $bin_number = '' ) {
		if ( ! $this->_settings->hasCredentials() ) {
			return new \WP_Error(
				'missing_credentials',
				__( 'MISSING_API_CREDENTIALS', 'iyzico-installment' )
			);
		}

		try {
			$options = $this->_getOptions();

			$request = new RetrieveInstallmentInfoRequest();
			$request->setLocale( 'tr' );
			$request->setConversationId( uniqid( 'iyzico_installment_' ) );
			$request->setPrice( $price );
			$request->setBinNumber( $bin_number );

			$response = InstallmentInfo::retrieve( $request, $options );

			if ( $response->getStatus() === 'success' ) {
				return $this->_formatInstallmentResponse( $response );
			} else {
				return new \WP_Error(
					'api_error',
					$response->getErrorMessage() ?: __( 'INSTALLMENT_INFO_ERROR', 'iyzico-installment' )
				);
			}
		} catch ( \Exception $e ) {
			return new \WP_Error( 'api_exception', $e->getMessage() );
		}
	}

	/**
	 * Format installment response
	 *
	 * @param \Iyzipay\Model\InstallmentInfo $response API response.
	 *
	 * @return array
	 */
	private function _formatInstallmentResponse( $response ) {
		return array(
			'status'             => $response->getStatus(),
			'conversationId'     => $response->getConversationId(),
			'installmentDetails' => $this->_getInstallmentDetails( $response ),
		);
	}

	/**
	 * Get installment details
	 *
	 * @param \Iyzipay\Model\InstallmentInfo $response API response.
	 *
	 * @return array
	 */
	private function _getInstallmentDetails( $response ) {
		$installmentDetails = $response->getInstallmentDetails();
		$result             = array();

		if ( $installmentDetails ) {
			foreach ( $installmentDetails as $detail ) {
				$result[] = array(
					'binNumber'         => $detail->getBinNumber(),
					'price'             => $detail->getPrice(),
					'cardType'          => $detail->getCardType(),
					'cardAssociation'   => $detail->getCardAssociation(),
					'cardFamilyName'    => $detail->getCardFamilyName(),
					'force3ds'          => $detail->getForce3ds(),
					'bankCode'          => $detail->getBankCode(),
					'bankName'          => $detail->getBankName(),
					'forceCvc'          => $detail->getForceCvc(),
					'installmentPrices' => $this->_getInstallmentPrices( $detail ),
				);
			}
		}

		return $result;
	}

	/**
	 * Get installment prices
	 *
	 * @param \Iyzipay\Model\InstallmentDetail $detail Installment detail.
	 *
	 * @return array
	 */
	private function _getInstallmentPrices( $detail ) {
		$installmentPrices = $detail->getInstallmentPrices();
		$result            = array();

		if ( $installmentPrices ) {
			foreach ( $installmentPrices as $price ) {
				$result[] = array(
					'installmentNumber' => $price->getInstallmentNumber(),
					'totalPrice'        => $price->getTotalPrice(),
					'installmentPrice'  => $price->getInstallmentPrice(),
				);
			}
		}

		return $result;
	}

	/**
	 * Ajax handler for getting installment info
	 *
	 * @return void
	 */
	public function ajaxGetInstallmentInfo() {
		// Verify nonce
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

		$price      = isset( $_POST['price'] ) ? (float) $_POST['price'] : 0;
		$bin_number = isset( $_POST['bin_number'] )
			? sanitize_text_field( wp_unslash( $_POST['bin_number'] ) )
			: '';

		if ( $price <= 0 ) {
			wp_send_json_error(
				array( 'message' => __( 'INVALID_PRICE', 'iyzico-installment' ) )
			);
		}

		$response = $this->getInstallmentInfo( $price, $bin_number );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		} else {
			wp_send_json_success( $response );
		}
	}
}
