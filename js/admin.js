/**
 * Admin Script
 */

/* global jQuery, gforms_stripe_admin_strings */

(function($) {
	$(document).ready(function() {
		RegisterEvents();
	});

	// Set up the Marketing 360 Sign In click trigger.
	function RegisterEvents() {
		$('#gf-m360-api-auth').click(m360SignIn);
	}

	// Bring up the prompt for the user to sign in to Marketing 360
	function m360SignIn(e) {
		e.preventDefault();

		const loginPageUrl 	= gforms_m360_admin_strings['login_page_url'];
		const endpointUrl 	= gforms_m360_admin_strings['site_url'] + 'wp-json/gf_marketing_360_payments/' + gforms_m360_admin_strings['ver'] + '/sign_in';
		const nonce 		= gforms_m360_admin_strings['nonce'];

		const popupCenter = (url, title, w, h) => {
		    // Fixes dual-screen position                             Most browsers      Firefox
		    const dualScreenLeft = window.screenLeft !==  undefined ? window.screenLeft : window.screenX;
		    const dualScreenTop = window.screenTop !==  undefined   ? window.screenTop  : window.screenY;

		    const width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
		    const height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

		    const systemZoom = width / window.screen.availWidth;
		    const left = (width - w) / 2 / systemZoom + dualScreenLeft
		    const top = (height - h) / 2 / systemZoom + dualScreenTop
		    const newWindow = window.open(url, title, 
		      `
		      scrollbars=yes,
		      width=${w / systemZoom}, 
		      height=${h / systemZoom}, 
		      top=${top}, 
		      left=${left}
		      `
		    )

		    if (window.focus) newWindow.focus();
		    return newWindow;
		}

		const loginPage = popupCenter(loginPageUrl, 'Connect to your Marketing 360® account', 500, 500);
		loginPage.endpointUrl = endpointUrl;
		loginPage.nonce = nonce;

		window.addEventListener('message', function(e) {
			if (e.origin == window.location.origin) {
				console.log(e.data);
				if (!('accountNumber' in e.data)) {
					return;
				}
				if (!('client_id' in e.data)) {
					return;
				}
				if (!('client_secret' in e.data)) {
					return;
				}

				$('#m360_account_details').val(e.data.payload)

				const notice = `
					<p>Currently connected to Marketing 360® account: ${e.data.externalAccountNumber} ${e.data.displayName}. Please click "Update Settings" to enable payments. <a href="#" onclick="m360SignOut()">Disconnect Account</a></p>
				`;

				$('#gf-m360-notice-box').html(notice);
				$('#gf-m360-api-auth').text('Connect to a different Marketing 360® account');
			}
		});
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