/**
 * Admin Script
 */

/* global jQuery, gforms_stripe_admin_strings */

(function ($) {
	$(document).ready(function () {
		RegisterEvents();
	});

	// Set up the Marketing 360 Sign In click trigger.
	function RegisterEvents() {
		$('#gf-m360-api-auth').click(m360SignIn);
	}

	// Bring up the prompt for the user to sign in to Marketing 360
	function m360SignIn(e) {
		e.preventDefault();

		const signInPopup = $('#gf-m360-signin-popup-wrap');
		const signInForm = $('#gf-m360-signin-popup-form-login');

		signInPopup
			.show()
			.off()
			.click(function (e) {
				if (e.target === this) {
					$(this).hide();
				}
			})

		signInForm.submit(onFormSubmit);
	}

	// Handle the form submission inside the prompt
	function onFormSubmit(e) {
		e.preventDefault();

		const form = e.target;

		const accountsList = $('#gf-m360-signin-popup-accounts-list');
		const accountsListSubHeading = $('#gf-m360-signin-popup-subtitle');
		const contentWrapper = $('#gf-m360-signin-popup-content-wrapper');

		$('#gf-m360-signin-popup-login').val("Connecting...");

		$.ajax({
			url: gforms_m360_admin_strings['connect_url'],
			method: form.method,
			data: $(this).serialize(),
			headers: {
				'X-WP-Nonce': gforms_m360_admin_strings['nonce']
			}
		})
			.done(function (response) {
				if (Array.isArray(response)) {
					contentWrapper.hide();
					accountsList.show();
					accountsListSubHeading.show();
					response.forEach(function (account) {

						delete account.html;

						const accountDiv = $('<div>').addClass('m360-account');
						const accountInfo = $('<div>').addClass('m360-account-info');
						const displayName = $('<h2>').addClass('display-name').text(account.displayName);
						const accountNumber = $('<h3>').addClass('account-number').text(account.externalAccountNumber);

						accountInfo.append(displayName, accountNumber);
						accountDiv.append(accountInfo);

						accountDiv.click(function () {
							$('#m360_account_details').val(account.payload);
							$('#gf-m360-signin-popup-wrap').hide();
							contentWrapper.show();
							accountsList.hide();
							accountsListSubHeading.hide();

							const noticeP = $('<p>');
							noticeP.append(
								document.createTextNode(
									'Currently connected to Marketing 360® account: ' +
									account.externalAccountNumber + ' ' + account.displayName +
									'. Please click "Update Settings" to enable payments. '
								)
							);
							const disconnectLink = $('<a>').attr('href', '#').text('Disconnect Account').click(function (e) {
								e.preventDefault();
								m360SignOut();
							});
							noticeP.append(disconnectLink);

							$('#gf-m360-notice-box').empty().append(noticeP);
							$('#gf-m360-api-auth').text('Connect to a different Marketing 360® account');
						});

						accountsList.append(accountDiv);
					});
				}
			})
			.error(function (response) {
				$('#alert-error').text(response.responseText);

				$('#gf-m360-signin-popup-login').val("Connect");
				console.error(response);
			})
	}
})(jQuery);

// Display a disabled Stripe Card Field (for form editor)
function TryMountCardAdminField(cardWrapID) {
	const stripeKey = gforms_m360_admin_strings['stripe_key'];
	if (!stripeKey) {
		return;
	}
	const stripe = Stripe(stripeKey);
	const elements = stripe.elements();
	const cardElement = elements.create('card', {
		disabled: true
	});
	cardElement.mount(cardWrapID);
}

function m360SignOut() {
	jQuery('#m360_account_details').val("");
	const notice = `
		<p>You have disconnected from Marketing 360®. Please click "Update Settings" to finish disconnecting.</p>
	`;

	jQuery('#gf-m360-notice-box').html(notice);
	jQuery('#gf-m360-api-auth').text('Connect to Marketing 360®');
}