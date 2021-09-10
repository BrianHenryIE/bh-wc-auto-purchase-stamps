(function( $ ) {
	'use strict';


	// look into jQuery( document.body ).trigger( 'post-load' );
	// plugin-install.php?s=WooCommerce&tab=search&type=term&open-plugin-details-modal=woocommerce
	// Open the plugin details modal on plugin-install.php.
	$(function() {
		// plugin-install.php?s=WooCommerce&tab=search&type=term
		// &open-plugin-details-modal=woocommerce
		let url = location;
		let searchParams = new URLSearchParams(url.search);

		var pluginName = searchParams.get('open-plugin-details-modal');
		if(pluginName != null) {

			setTimeout(
				function()
				{
					jQuery('.plugin-card-' + pluginName + ' .open-plugin-details-modal').click();
				}, 2500);

		}

		// if(pluginName != null) {
		// 	jQuery('.plugin-card-woocommerce .open-plugin-details-modal').click();
		// }
	});

	// mail-tester-address
	// onchange... update the link

	// enable disable/button

	// send-spf-dkim-test-email-button
	// on click... ajax
	// on return: success/error

	//
	// onclick .. ajax
	// on return success/error
	$( window ).load(function() {

		// We're using a fake password input so the browser doesn't suggest saving it.
		var dontSavePassword = document.getElementById('bh-dont-save-password');
		var realPasswordInput = document.getElementById( 'bh-gmail-to-gmail-password' );

		dontSavePassword.addEventListener( 'focus', function () {
			realPasswordInput.focus();
		});


		// send_spf_dkim_test_email
		var sendSpfDkimTestEmailButton = document.getElementById('send-spf-dkim-test-email-button');

		sendSpfDkimTestEmailButton.addEventListener('click', function (event) {

			event.preventDefault();

			var data = $('#send-spf-dkim-test-email-form').serializeArray();

			// TODO: Validate email address.

			// Clear previous result.
			$('#send-spf-dkim-test-email-response').empty();

			$.post(ajaxurl, data, function (response) {

				$.each(response, function (noticeType, notices) {

					$.each(notices, function (error, message) {

						$('#send-spf-dkim-test-email-response').append("<div class=\"notice notice-" + noticeType + " is-dismissible\"><p>" + message + "</p></div>");

					});
				});


			});

		}, false);

		var sendTestEmailGmailButton = document.getElementById('send-test-email-gmail-button');

		sendTestEmailGmailButton.addEventListener('click', function (event) {

			event.preventDefault();
			
			var data = $('#send-test-email-gmail-form').serializeArray();

			// TODO: Validate email address.

			// Clear previous result.
			$('#send-test-email-gmail-response').empty();


			$.post(ajaxurl, data, function (response) {

				$.each(response, function (noticeType, notices) {

					$.each(notices, function (error, message) {

						$('#send-test-email-gmail-response').append("<div class=\"notice notice-" + noticeType + " is-dismissible\"><p>" + message + "</p></div>");

					});
				});


			});

		}, false);


	});



	// send-whitelisting-sample-button
	// ajax

})( jQuery );
