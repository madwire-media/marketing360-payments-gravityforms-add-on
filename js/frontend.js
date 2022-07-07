/**
 * Front-end Script
 */

// Display a usable Stripe Card field if a valid stripeKey is present.
function TryMountCardFrontendField() {
	const stripeKey = gforms_m360_frontend_strings['stripe_key'];
	if (!stripeKey) {
		return;
	}
	const stripe = Stripe(stripeKey);
	const elements = stripe.elements();
	const cardElement = elements.create('card');
	cardElement.mount(cardWrapID);
}