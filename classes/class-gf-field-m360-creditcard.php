<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

// The Marketing 360 Payments Card field is a credit card field used specifically by the Marketing 360 Payments Add-On.
class GF_Field_M360_CreditCard extends GF_Field {

	// Field type.
	public $type = 'm360_creditcard';

	// Get field button title.
	public function get_form_editor_field_title() {
		return esc_attr('Mkt 360® Card', 'gravityformsm360');
	}

	// Additional JS to be included when this field type is loaded
	public function get_form_editor_inline_script_on_page_render() {
		$js = sprintf( "function SetDefaultValues_%s(field) {field.label = '%s';}", esc_attr($this->type), __( 'Credit Card', 'gravityformsm360' ));
		return $js;
	}

	// Get field settings in the form editor.
	public function get_form_editor_field_settings() {
	    return array(
	        'conditional_logic_field_setting',
	        'error_message_setting',
	        'label_setting',
	        'label_placement_setting',
	        'admin_label_setting',
	        'size_setting',
	        'rules_setting',
	        'visibility_setting',
	        'duplicate_setting',
	        'css_class_setting',
	    );
	}

	// Get form editor button.
	public function get_form_editor_button() {
		return array(
			'group' => 'pricing_fields',
			'text' => $this->get_form_editor_field_title()
		);
	}

	// Get field input.
	public function get_field_input( $form, $value='', $entry = null ) {
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$is_admin = $is_entry_detail || $is_form_editor;

		$form_id  = $form['id'];
		$id       = intval( $this->id );
		$field_id = $is_entry_detail || $is_form_editor || $form_id === 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$disabled_text = $is_form_editor ? "disabled='disabled'" : '';
		$class_suffix  = $is_entry_detail ? '_admin' : '';

		$form_sub_label_placement  = rgar( $form, 'subLabelPlacement' );
		$field_sub_label_placement = $this->subLabelPlacement;
		$is_sub_label_above        = $field_sub_label_placement === 'above' || ( empty( $field_sub_label_placement ) && $form_sub_label_placement === 'above' );
		$sub_label_class_attribute = $field_sub_label_placement === 'hidden_label' ? "class='hidden_sub_label screen-reader-text'" : '';

		$card_details_input     = GFFormsModel::get_input( $this, $this->id . '.1' );
		$card_details_sub_label = rgar( $card_details_input, 'customLabel', __( 'Card Details', 'gravityformsm360' ) );
		$card_details_sub_label = gf_apply_filters( array( 'gform_card_details', $form_id, $this->id ), $card_details_sub_label, $form_id );

		$cardholder_name_input      = GFFormsModel::get_input( $this, $this->id . '.5' );
		$hide_cardholder_name       = rgar( $cardholder_name_input, 'isHidden' );
		$cardholder_name_sub_label  = rgar( $cardholder_name_input, 'customLabel', __( 'Cardholder Name', 'gravityformsm360' ) );
		$cardholder_name_sub_label  = gf_apply_filters( array( 'gform_card_name', $form_id, $this->id ), $cardholder_name_sub_label, $form_id );
		$cardholder_name_placehoder = $this->get_input_placeholder_attribute( $cardholder_name_input );

		// Prepare the values for checking the Stripe Card field error.
		$settings_url            = add_query_arg( array(
			'page'    => 'gf_settings',
			'subview' => gf_m360()->get_slug(),
		), admin_url( 'admin.php' ) );

		$client_id = gf_m360()->get_client_id();
		$client_secret = gf_m360()->get_client_secret();
		$client_token = gf_m360()->get_client_token();
		$account_details = gf_m360()->get_account_details();
		$stripe_key = gf_m360()->get_stripe_key();

		$card_wrap_id = "gf-m360-payments-card-" . $form_id . "-" .$field_id;

		// If we are in the form editor, display a placeholder field.
		if ($is_admin) {
			// Display an ungenerated API token error.
			if (empty($client_token)) {
				ob_start(); ?>
					<div class="notice notice-error inline">
					<p><b>Hold up!</b> You haven't connected to a Marketing 360® account yet. Please <a href="<?php esc_url( $settings_url ); ?>">check your Marketing 360® Payments settings</a>.</p>
					</div>
				<?php return ob_get_clean();
			}
			// Display a disabled placeholder field.
			ob_start(); ?>

				<div id="<?php esc_html_e( $card_wrap_id ); ?>"></div>

				<script>
					TryMountCardAdminField("#<?php esc_html_e( $card_wrap_id ); ?>");
				</script>

			<?php return ob_get_clean();
		} else if ($stripe_key) {
			// If we're on the frontend and the Stripe key is valid, display the actual Stripe form. 
			$input_id = $card_wrap_id . "_token_input";
			ob_start(); ?>

				<div id="<?php esc_html_e( $card_wrap_id ); ?>"></div>
				<input id="<?php esc_html_e( $input_id ); ?>" type="hidden" name="stripe_response" value="<?php esc_html_e( $value ); ?>">

				<script>
					const stripeKey = "<?php esc_html_e( $stripe_key ); ?>";
					const stripeAccountId = "<?php esc_html_e( $account_details->stripeAccountId ); ?>";
					const stripe = Stripe(stripeKey, {
						stripeAccount: stripeAccountId
					});
					const elements = stripe.elements();
					const cardElement = elements.create('card');
					cardElement.mount("#<?php esc_html_e( $card_wrap_id ); ?>");

					cardElement.on('change', event => {

						stripe.createToken(cardElement).then(result => {
							jQuery('#<?php esc_html_e( $input_id ); ?>').val(JSON.stringify(result));
						})
					})
				</script>

			<?php return ob_get_clean();
		}
	}
}
GF_Fields::register(new GF_Field_M360_CreditCard());