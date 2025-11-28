<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Iyzico Installment Logger class.
 *
 * @package Iyzico_Installment
 * @category Core
 * @author Iyzico
 * @license GPLv2 or later
 * @link https://iyzico.com
 */
class Iyzico_Installment_Logger {

	/**
	 * Log file path
	 *
	 * @var string
	 */
	private $_logFile;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_logFile = IYZI_INSTALLMENT_PATH . 'logs/debug.log';
	}

	/**
	 * Log message
	 *
	 * @param string $message Message to log.
	 * @param string $level   Log level (info, warning, error).
	 *
	 * @return void
	 */
	public function log( $message, $level = 'info' ) {
		// Ensure log directory exists
		$this->_ensureLogDirectoryExists();

		// Format message
		$timestamp   = wp_date( 'Y-m-d H:i:s' );
		$log_message = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

		// Write to log file using WP_Filesystem
		global $wp_filesystem;
		
		// Initialize WP_Filesystem if not already done
		if ( empty( $wp_filesystem ) ) {
			include_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Write to log file with proper error handling
		if ( ! $wp_filesystem->put_contents( $this->_logFile, $log_message, FS_CHMOD_FILE ) ) {
			// Fallback to file_put_contents if WP_Filesystem fails
			$file_put_contents_result = file_put_contents( $this->_logFile, $log_message, FILE_APPEND );
			if ( $file_put_contents_result === false ) {
				// Log to error log as last resort
				error_log( "Iyzico Logger: Failed to write to log file: {$this->_logFile}" );
				error_log( "Iyzico Logger: Message was: {$log_message}" );
				return;
			}
		} else {
			// If file exists and we used put_contents, we need to append the content
			if ( $wp_filesystem->exists( $this->_logFile ) ) {
				$existing_content = $wp_filesystem->get_contents( $this->_logFile );
				$wp_filesystem->put_contents( $this->_logFile, $existing_content . $log_message, FS_CHMOD_FILE );
			} else {
				$wp_filesystem->put_contents( $this->_logFile, $log_message, FS_CHMOD_FILE );
			}
		}
	}

	/**
	 * Log info message
	 *
	 * @param string $message Message to log.
	 *
	 * @return void
	 */
	public function info( $message ) {
		$this->log( $message, 'INFO' );
	}

	/**
	 * Log warning message
	 *
	 * @param string $message Message to log.
	 *
	 * @return void
	 */
	public function warning( $message ) {
		$this->log( $message, 'WARNING' );
	}

	/**
	 * Log error message
	 *
	 * @param string $message Message to log.
	 *
	 * @return void
	 */
	public function error( $message ) {
		$this->log( $message, 'ERROR' );
	}

	/**
	 * Log exception
	 *
	 * @param Exception $exception Exception.
	 *
	 * @return void
	 */
	public function exception( $exception ) {
		$message = 'Exception: ' . $exception->getMessage()
				. ' in ' . $exception->getFile()
				. ' on line ' . $exception->getLine();
		$trace   = $exception->getTraceAsString();
		$this->error( $message . PHP_EOL . $trace );
	}

	/**
	 * Ensure log directory exists
	 *
	 * @return void
	 */
	private function _ensureLogDirectoryExists() {
		global $wp_filesystem;

		// Initialize WP_Filesystem if not already done
		if ( empty( $wp_filesystem ) ) {
			include_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$log_dir = dirname( $this->_logFile );

		if ( ! $wp_filesystem->is_dir( $log_dir ) ) {
			$wp_filesystem->mkdir( $log_dir, FS_CHMOD_DIR );
		}
	}

	/**
	 * Get log file content
	 *
	 * @return string
	 */
	public function getLogContent() {
		global $wp_filesystem;

		// Initialize WP_Filesystem if not already done
		if ( empty( $wp_filesystem ) ) {
			include_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( $wp_filesystem->exists( $this->_logFile ) ) {
			return $wp_filesystem->get_contents( $this->_logFile );
		}

		return '';
	}

	/**
	 * Clear log file
	 *
	 * @return void
	 */
	public function clearLog() {
		global $wp_filesystem;

		// Initialize WP_Filesystem if not already done
		if ( empty( $wp_filesystem ) ) {
			include_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( $wp_filesystem->exists( $this->_logFile ) ) {
			$wp_filesystem->put_contents( $this->_logFile, '', FS_CHMOD_FILE );
		}
	}

	/**
	 * Check if log file exists
	 *
	 * @return bool
	 */
	public function logFileExists() {
		global $wp_filesystem;

		// Initialize WP_Filesystem if not already done
		if ( empty( $wp_filesystem ) ) {
			include_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem->exists( $this->_logFile );
	}

	/**
	 * Check if log file is writable
	 *
	 * @return bool
	 */
	public function isLogFileWritable() {
		global $wp_filesystem;

		// Initialize WP_Filesystem
		if ( empty( $wp_filesystem ) ) {
			include_once ABSPATH . '/wp-admin/includes/file.php';

			$credentials = request_filesystem_credentials( '', '', false, false, null );
			if ( ! WP_Filesystem( $credentials ) ) {
				// Return false if WP_Filesystem fails to initialize
				return false;
			}
		}

		// Check if file exists and is writable
		if ( $wp_filesystem->exists( $this->_logFile ) ) {
			return $wp_filesystem->is_writable( $this->_logFile );
		}

		// If file doesn't exist, check if directory is writable
		$log_dir = dirname( $this->_logFile );
		if ( $wp_filesystem->exists( $log_dir ) ) {
			return $wp_filesystem->is_writable( $log_dir );
		}

		return false;
	}
}
