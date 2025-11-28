/**
 * iyzico Installment JS — logos fallback + responsive rendering
 */
(function ($) {
	'use strict';

	var L               = window.iyzicoInstallment || {};
	L.copySuccess       = L.copySuccess || 'Kopyalandı!';
	L.copyError         = L.copyError || 'Kopyalama başarısız.';
	L.emptyCredentials  = L.emptyCredentials || 'API anahtarı ve gizli anahtar girilmelidir.';
	L.testing           = L.testing || 'Bağlantı testi...';
	L.connected         = L.connected || 'Bağlantı başarılı';
	L.disconnected      = L.disconnected || 'Bağlantı yok';
	L.connectionSuccess = L.connectionSuccess || 'API bağlantısı başarılı.';
	L.connectionError   = L.connectionError || 'API bağlantısı başarısız.';
	L.installmentText   = L.installmentText || 'Taksit';
	L.totalText         = L.totalText || 'Toplam';
	L.currencySymbol    = L.currencySymbol || '';
	L.assetsUrl         = L.assetsUrl || ''; // eklendi: PHP tarafından lokalize edilmelidir

	$(
		function () {
			// Shortcode kopyalama (korumalı)
			$( document ).on(
				'click',
				'.iyzico-copy-shortcode',
				function (e) {
					e.preventDefault();
					var $btn          = $( this );
					var target        = $btn.attr( 'data-target' ) || 'iyzico-shortcode'; // attr kullan data yerine
					var shortcodeText = $( '#' + target ).text() || $btn.data( 'shortcode' ) || '';
					if ( ! shortcodeText) {
						alert( L.copyError );
						return;
					}

					if (navigator.clipboard && navigator.clipboard.writeText) {
						navigator.clipboard.writeText( shortcodeText ).then(
							function () {
								var original = $btn.text();
								$btn.text( L.copySuccess );
								setTimeout(
									function () {
										$btn.text( original );
									},
									1800
								);
							}
						).catch(
							function () {
								alert( L.copyError );
							}
						);
					} else {
						var $temp = $( '<textarea>' )
						.css( {position:'absolute', left:'-9999px'} )
						.text( shortcodeText )
						.appendTo( 'body' );
						$temp.select();
						try {
							document.execCommand( 'copy' );
							$temp.remove();
							var original = $btn.text();
							$btn.text( L.copySuccess );
							setTimeout(
								function () {
									$btn.text( original );
								},
								1800
							);
						} catch (ex) {
							$temp.remove();
							alert( L.copyError );
						}
					}
				}
			);

			// Test API bağlantısı (admin)
			$( document ).on(
				'click',
				'#iyzico-test-api',
				function (e) {
					e.preventDefault();
					var $button   = $( this );
					var apiKey    = $( '#iyzico_api_key' ).val();
					var secretKey = $( '#iyzico_secret_key' ).val();
					var mode      = $( '#iyzico_mode' ).val();

					if ( ! apiKey || ! secretKey) {
						alert( L.emptyCredentials );
						return;
					}

					var originalText = $button.text();
					$button.prop( 'disabled', true ).text( L.testing );

					$.ajax(
						{
							url: L.ajaxUrl,
							type: 'POST',
							dataType: 'json',
							data: {
								action: 'iyzico_test_api',
								api_key: apiKey,
								secret_key: secretKey,
								mode: mode,
								nonce: L.nonce
							}
						}
					).done(
						function (response) {
							if (response && response.success) {
								$( '.iyzico-api-status' )
								.removeClass( 'disconnected' )
								.addClass( 'connected' )
								.text( L.connected );
								alert( response.data && response.data.message ? response.data.message : L.connectionSuccess );
							} else {
								$( '.iyzico-api-status' )
								.removeClass( 'connected' )
								.addClass( 'disconnected' )
								.text( L.disconnected );
								alert( (response && response.data && response.data.message) ? response.data.message : L.connectionError );
							}
						}
					).fail(
						function () {
							$( '.iyzico-api-status' )
							.removeClass( 'connected' )
							.addClass( 'disconnected' )
							.text( L.disconnected );
							alert( L.connectionError );
						}
					).always(
						function () {
							$button.prop( 'disabled', false ).text( originalText );
						}
					);
				}
			);

			// Eğer product page ve direct integration ise AJAX ile getir
			if (typeof window.iyzicoInstallment !== 'undefined' && window.iyzicoInstallment.isProductPage && window.iyzicoInstallment.integrationType === 'direct') {
				var productPrice = parseFloat( window.iyzicoInstallment.productPrice || 0 );
				if (productPrice > 0) {
					fetchInstallmentInfo( productPrice );
				}
			}

			// card select & keyboard
			var $container = $( '.iyzico-installment-container' );
			$container.on(
				'click',
				'.iyzico-bank-card',
				function () {
					var $card = $( this );
					$card.closest( '.iyzico-bank-grid' )
					.find( '.iyzico-bank-card.selected' )
					.not( $card )
					.removeClass( 'selected' );
					$card.toggleClass( 'selected' ).focus();
				}
			);
			$container.on(
				'keydown',
				'.iyzico-bank-card',
				function (e) {
					if (e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32) {
						e.preventDefault();
						$( this ).trigger( 'click' );
					}
				}
			);
			$container.on(
				'click',
				'.iyzico-installment-table tbody tr',
				function () {
					var $tr = $( this );
					$tr.closest( 'tbody' )
					.find( 'tr.active' )
					.removeClass( 'active' );
					$tr.addClass( 'active' );
				}
			);
		}
	);

	// ---------- helpers ----------

	function fetchInstallmentInfo(price) {
		var $container = $( '.iyzico-installment-container' );
		if ($container.length === 0) {
			return;
		}

		$container.find( '.iyzico-bank-grid' ).remove();
		var $loader = $( '<div class="iyzico-loader">Yükleniyor...</div>' )
			.css( {padding:'12px', color:'#6b7280', 'font-size':'13px'} );
		$container.append( $loader );

		$.ajax(
			{
				url: window.iyzicoInstallment.ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action:'iyzico_get_installment_info',
					price: price,
					nonce: window.iyzicoInstallment.nonce
				}
			}
		).done(
			function (response) {
				$loader.remove();
				if (response && response.success && response.data && response.data.installmentDetails && response.data.installmentDetails.length) {
					renderInstallmentTable( response.data );
				} else {
					var msg = (response && response.data && response.data.message) ? response.data.message : 'Taksit bilgisi bulunamadı.';
					$container.append( $( '<p>' ).text( msg ).css( {color:'#6b7280', padding:'10px'} ) );
				}
			}
		).fail(
			function () {
				$loader.remove();
				$container.append( $( '<p>' ).text( 'Taksit bilgisi alınamadı.' ).css( {color:'#d9534f', padding:'10px'} ) );
			}
		);
	}

	// Logo mapping: cardFamilyName => filename (case-insensitive, partial match)
	function getLogoForCardFamily(cardFamily) {
		var base = (window.iyzicoInstallment && window.iyzicoInstallment.assetsUrl)
			? window.iyzicoInstallment.assetsUrl.replace( /\/$/, '' )
			: (L.assetsUrl || '').replace( /\/$/, '' );
		var name = (cardFamily || '').toLowerCase();

		var map = [
			{ key: ['bonus'], file: 'Bonus.png' },
			{ key: ['axess'], file: 'Axess.png' },
			{ key: ['maximum'], file: 'Maximum.png' },
			{ key: ['paraf'], file: 'Paraf.png' },
			{ key: ['cardfinans'], file: 'Cardfinans.png' },
			{ key: ['advantage'], file: 'Advantage.png' },
			{ key: ['world'], file: 'World.png' },
			{ key: ['saglam','sağlam'], file: 'SaglamKart.png' },
			{ key: ['combo'], file: 'BankkartCombo.png' },
			{ key: ['qnb','cc'], file: 'QNB-CC.png' }
		];

		for (var i = 0; i < map.length; i++) {
			var keys = map[i].key;
			for (var k = 0; k < keys.length; k++) {
				if (name.indexOf( keys[k] ) !== -1) {
					return base + '/images/' + map[i].file;
				}
			}
		}
		return ''; // no match
	}

	function renderInstallmentTable(data) {
		var $container = $( '.iyzico-installment-container' );
		if ($container.length === 0 || ! data || ! data.installmentDetails) {
			return;
		}

		var $grid = $( '<div>' ).addClass( 'iyzico-bank-grid' );

		data.installmentDetails.forEach(
			function (bank) {
				var bankName   = bank.bankName || '';
				var cardFamily = bank.cardFamilyName || '';

				var $card = $( '<div>' )
				.addClass( 'iyzico-bank-card' )
				.attr( 'tabindex', '0' )
				.attr( 'aria-label', bankName + ' - ' + cardFamily );

				var $logoTop = $( '<div>' ).addClass( 'iyzico-bank-logo-top' );

				// 1) Eğer sunucu logoUrl verdiyse onu kullan
				if (bank.logoUrl) {
					$logoTop.append(
						$( '<img>' )
						.attr( 'src', bank.logoUrl )
						.attr( 'alt', cardFamily )
						.addClass( 'bank-logo' )
					);
				} else {
					// 2) JS mapping ile assets içinden logo dene
					var mapped = getLogoForCardFamily( cardFamily );
					if (mapped) {
						$logoTop.append(
							$( '<img>' )
							.attr( 'src', mapped )
							.attr( 'alt', cardFamily )
							.addClass( 'bank-logo' )
							.on(
								'error',
								function () {
									// hata olursa fallback göster
									$( this ).replaceWith(
										$( '<div>' )
										.addClass( 'bank-logo-default' )
										.text( cardFamily || bankName )
									);
								}
							)
						);
					} else {
						// 3) fallback text / emoji
						$logoTop.append(
							$( '<div>' )
							.addClass( 'bank-logo-default' )
							.text( cardFamily || bankName )
						);
					}
				}

				$card.append( $logoTop );

				var $tableArea = $( '<div>' ).addClass( 'table-area' );
				var $table     = $( '<table>' )
				.addClass( 'iyzico-installment-table' )
				.attr( 'role', 'table' )
				.attr( 'aria-label', bankName + ' taksit tablosu' );
				var $thead     = $( '<thead>' );
				var $headRow   = $( '<tr>' );
				$headRow.append( $( '<th>' ).text( 'Taksit Sayısı' ) );
				$headRow.append( $( '<th>' ).addClass( 'amount' ).text( 'Taksit Tutarı' ) );
				$headRow.append( $( '<th>' ).addClass( 'amount total' ).text( 'Toplam' ) );
				$thead.append( $headRow );
				$table.append( $thead );

				var $tbody = $( '<tbody>' );
				(bank.installmentPrices || []).forEach(
					function (inst) {
						var $tr = $( '<tr>' );
						$tr.append( $( '<td>' ).text( inst.installmentNumber || '' ) );
						$tr.append(
							$( '<td>' )
							.addClass( 'amount' )
							.text( formatPrice( inst.installmentPrice ) + ' ' + (window.iyzicoInstallment ? window.iyzicoInstallment.currencySymbol : L.currencySymbol) )
						);
						$tr.append(
							$( '<td>' )
							.addClass( 'amount total' )
							.text( formatPrice( inst.totalPrice ) + ' ' + (window.iyzicoInstallment ? window.iyzicoInstallment.currencySymbol : L.currencySymbol) )
						);
						$tbody.append( $tr );
					}
				);
				$table.append( $tbody );
				$tableArea.append( $table );
				$card.append( $tableArea );

				$grid.append( $card );
			}
		);

		$container.find( '.iyzico-bank-grid' ).remove();
		$container.append( $grid );
	}

	function formatPrice(value) {
		var num = Number( value );
		if (isNaN( num )) {
			return value || '';
		}
		try {
			return new Intl.NumberFormat(
				'tr-TR',
				{
					minimumFractionDigits: 2,
					maximumFractionDigits: 2
				}
			).format( num );
		} catch (e) {
			return num.toFixed( 2 ).replace( '.', ',' );
		}
	}

})( jQuery );
