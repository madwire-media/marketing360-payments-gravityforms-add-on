<?php

defined('ABSPATH') || die();

// Include the payment add-on framework.
GFForms::include_payment_addon_framework();

class GF_M360_AddOn extends GFPaymentAddOn
{

    // Contains an instance of this class, if available.
    private static $_instance = null;

    // The version of the Marketing 360 Add-On.
    protected $_version = GF_M360_VERSION;

    // The minimum Gravity Forms version that is compatible with this Add-On
    protected $_min_gravityforms_version = "1.9.14.17";

    // The plugin slug.
    protected $_slug = 'gravityforms-marketing-360-payments';

    // The main plugin file.
    protected $_path = 'gravityforms-marketing-360-payments/marketing-360-payments.php';

    // The URL where this Add-On can be found.
    protected $_url = 'https://marketing360.com';

    // The full path to this class file.
    protected $_full_path = __FILE__;

    // The title of this Add-On.
    protected $_title = 'Gravity Forms Marketing 360® Payments Add-On';

    // The short title of the Add-On.
    protected $_short_title = 'Marketing 360® Payments';

    // Defines if callbacks/webhooks/IPN will be enabled and the appropriate database table will be created.
    protected $_supports_callbacks = true;

    // Defines if user will not be able to create feeds for a form until a credit card field has been added.
    protected $_requires_credit_card = false;

    // Whether Add-on framework has settings renderer support or not, settings renderer was introduced in Gravity Forms 2.5
    protected $_has_settings_renderer;

    // The URL for the frontend Stripe CDN
    protected $_stripe_url = "https://js.stripe.com/v3";

    // The current Stripe CDN version
    protected $_stripe_version = "v3";

    // The handle for the admin-facing JavaScript
    protected $_admin_js_handle = "gforms_m360_admin";

    // The handle for the admin-facing CSS
    protected $_admin_css_handle = "gforms_m360_admin";

    // The relative path for the admin-facing JavaScript
    protected $_admin_js_path = "/js/admin.js";

    // The relative path for the admin-facing CSS
    protected $_admin_css_path = "/css/admin.css";

    /**
     * Length of time to cache the Bearer Token in seconds
     * 240 = 4min
     */
    const TOKEN_EXPIRATION_LENGTH_IN_SECONDS = 240;

    // The capability needed to uninstall the Add-On.
    protected $_capabilities_uninstall = 'gravityforms_m360_uninstall';

    // The capabilities needed for the Marketing 360 Payments Add-On
    protected $_capabilities = array('gravityforms_m360', 'gravityforms_m360_uninstall');

    // Fires before the WordPress action "init" fires
    public function pre_init()
    {

        parent::pre_init();

        require_once('classes/class-gf-marketing-360-payments.php');
        require_once('classes/class-gf-field-m360-creditcard.php');

    }

    // Get a singleton instance of this Add-On
    public static function get_instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    // The markup for the "api_connection_check" plugin setting field
    public function settings_api_connection_check($field)
    {
        ob_start(); ?>
<?php $this->settings_hidden($field);
    $details = $this->get_account_details();
    $button_text = ($details) ? 'Connect to a different Marketing 360® account' : 'Connect to your Marketing 360® account'; ?>
    
<div id="gf-m360-notice-box">
    <?php if ($details): ?>
        <p>
            <?php echo sprintf(
                __( 'Currently connected to Marketing 360® account: %s %s. ', 'gravityformsm360' ),
                esc_html( $details->externalAccountNumber ),
                esc_html( $details->displayName )
            ); ?><a href="#" onclick="m360SignOut()">Disconnect Account</a>
        </p>
    <?php endif; ?>
</div>

<button id="gf-m360-api-auth" class="button-secondary">
    <?php echo esc_html($button_text); ?>
</button>

<?php echo ob_get_clean();
    }

    // Register global settings for the Add-On
    public function plugin_settings_fields()
    {
        $fields = array(
            array(
                'title' => __('Marketing 360® Account', 'gravityformsm360'),
                'fields' => $this->api_settings_fields()
            )
        );
        return $fields;
    }

    // Return an array of setting objects for the Add-On
    public function api_settings_fields()
    {

        // If no ssl certificate found, just return the ssl error message.
        if (!is_ssl()) {
            return array(
                array(
                    'name' => 'ssl_error',
                    'type' => 'ssl_error'
                )
            );
        }

        $fields = array(
            array(
                'label' => __('Connect to Marketing 360®', 'gravityformsm360'),
                'name' => 'm360_account_details',
                'type' => 'api_connection_check',
                'save_callback' => 'GF_Marketing_360_Payments::add_stripe_details_callback'
            ),
        );
        return $fields;
    }

    // Return the scripts which should be enqueued.
    public function scripts()
    {
        $scripts = array(
            array(
                'handle' => 'stripe',
                'src' => $this->_stripe_url,
                'version' => $this->_stripe_version,
                'deps' => array(),
                'in_footer' => false,
                'callback' => array($this, 'localize_scripts'),
                'enqueue' => array(
                    array('field_types' => array('m360_creditcard'))
                )
            ),
            array(
                'handle' => $this->_admin_js_handle,
                'src' => $this->get_base_url() . $this->_admin_js_path,
                'version' => $this->_version . time(),
                'deps' => array('jquery', 'stripe'),
                'in_footer' => false,
                'enqueue' => array(
                    array(
                        'admin_page' => array('plugin_settings', 'form_settings'),
                        'tab' => array($this->get_slug(), $this->get_short_title()),
                    ),
                    array(
                        array('field_types' => array('m360_creditcard')),
                        array('admin_page' => array('form_editor'))
                    ),
                ),
                'strings' => array(
                    'connect_url' => get_rest_url(null, 'gf_marketing_360_payments/' . GF_Marketing_360_Payments::VER . '/sign_in'),
                    'nonce' => wp_create_nonce('wp_rest'),
                    'stripe_key' => $this->get_stripe_key(),
                )
            ),
        );

        return array_merge(parent::scripts(), $scripts);
    }

    // Return the styles which should be enqueued.
    public function styles()
    {
        $styles = array(
            array(
                'handle' => $this->_admin_css_handle,
                'src' => $this->get_base_url() . $this->_admin_css_path,
                'version' => $this->_version . time(),
                'enqueue' => array(
                    array(
                        'admin_page' => array('plugin_settings', 'form_settings'),
                        'tab' => array($this->get_slug(), $this->get_short_title()),
                    ),
                )
            ),
        );

        return array_merge(parent::styles(), $styles);
    }

    // Generates the markup for the SSL error message field.
    public function settings_ssl_error($field, $echo = true)
    {
        $html = $this->_has_settings_renderer ? '<div class="alert gforms_note_error">' : '<div class="alert_red" style="padding:20px; padding-top:5px;">';
        $html .= '<h4>' . __('SSL Certificate Required', 'gravityformsm360') . '</h4>';
        /* Translators: 1: Open link tag 2: Close link tag */
        $html .= sprintf(__('Make sure you have an SSL certificate installed and enabled, then %1$sclick here to reload the settings page%2$s.', 'gravityformsm360'), '<a href="' . esc_url($this->get_settings_page_url()) . '">', '</a>');
        $html .= '</div>';

        if ($echo) {
            echo esc_html($html);
        }

        return $html;

    }

    // Generates plugin settings page URL or feed details page URL depending on current screen.
    public function get_settings_page_url()
    {
        if (!$this->is_detail_page()) {
            return admin_url('admin.php?page=gf_settings&subview=' . $this->get_slug(), 'https');
        }

        return add_query_arg(
            array(
                'page' => 'gf_edit_forms',
                'view' => 'settings',
                'subview' => $this->get_slug(),
                'id' => rgget('id'),
                'fid' => $this->get_current_feed_id(),
            ),
            admin_url('admin.php', 'https')
        );
    }

    // Gets the full Account Details object.
    public function get_account_details()
    {
        $raw = $this->get_plugin_setting('m360_account_details');
        if (empty($raw)) {
            return null;
        }
        // GF may return the value already decoded as an array or object.
        if (is_object($raw)) {
            return $raw;
        }
        if (is_array($raw)) {
            return (object) $raw;
        }
        $decoded = json_decode($raw);
        return $decoded ?: null;
    }

    // Overwrites the account details object
    public function set_account_details($account_details)
    {
        $settings = $this->get_plugin_settings();
        $settings['m360_account_details'] = json_encode($account_details);
        $this->update_plugin_settings($settings);
    }

    // Get the Stripe Key from the full account details object.
    public function get_stripe_key()
    {
        $details = $this->get_account_details();
        return isset($details->stripeKey) ? $details->stripeKey : null;
    }

    // Get the Marketing 360 Account ID from the full account details object.
    public function get_account()
    {
        $details = $this->get_account_details();
        return isset($details->accountNumber) ? $details->accountNumber : null;
    }

    // Get the Marketing 360 Client ID from the full account details object.
    public function get_client_id()
    {
        $details = $this->get_account_details();
        return isset($details->client_id) ? $details->client_id : null;
    }

    // Get the Marketing 360 Client Secret from the full account details object.
    public function get_client_secret()
    {
        $details = $this->get_account_details();
        return isset($details->client_secret) ? $details->client_secret : null;
    }

    // Get/regenerate the Marketing 360 Client Token.
    public function get_client_token()
    {
        $time = time();
        $token = get_option('m360_client_token');
        $token_expiration = get_option('m360_client_token_expiration');

        if (
            $token &&
            $token_expiration &&
            $time < $token_expiration
        ) {
            return $token;
        }

        // Attempt to acquire a lock before refreshing.
        $lock_key = 'm360_token_refresh_lock';
        $lock_ttl = 30;
        $lock_set = wp_cache_add($lock_key, 1, '', $lock_ttl);

        if (!$lock_set) {
            sleep(1);
            $token = get_option('m360_client_token');
            if ($token) {
                return $token;
            }
        }

        $client_id = $this->get_client_id();
        $client_secret = $this->get_client_secret();

        $token = GF_Marketing_360_Payments::id_secret_get_access_token($client_id, $client_secret);

        if (is_wp_error($token)) {
            $this->log_error(__METHOD__ . '(): Failed to refresh bearer token. ' . $token->get_error_message());
            $token = null;
        } else {
            $this->log_debug(__METHOD__ . '(): Bearer token refreshed successfully.');
            $this->set_client_token($token);
        }

        wp_cache_delete($lock_key);

        return $token;
    }

    // Set the Marketing 360 Client Token and the new expiration date.
    public function set_client_token($token)
    {
        update_option('m360_client_token', $token, false);
        update_option('m360_client_token_expiration', time() + self::TOKEN_EXPIRATION_LENGTH_IN_SECONDS, false);
    }

    // Configures the settings which should be rendered on the feed edit page.
    public function feed_settings_fields()
    {

        return array(
            array(
                'description' => '',
                'fields' => array(
                    array(
                        'name' => 'feedName',
                        'label' => __('Name', 'gravityformsm360'),
                        'type' => 'text',
                        'class' => 'medium',
                        'required' => true,
                        'tooltip' => '<h6>' . esc_html__('Name', 'gravityformsm360') . '</h6>' . esc_html__('Enter a feed name to uniquely identify this setup.', 'gravityformsm360')
                    ),
                    array(
                        'name' => 'transactionType',
                        'label' => __('Transaction Type', 'gravityformsm360'),
                        'type' => 'select',
                        'onchange' => "jQuery(this).parents('form').submit();",
                        'choices' => array(
                            array(
                                'label' => __('Products and Services', 'gravityformsm360'),
                                'value' => 'product'
                            ),
                        )
                    ),
                )
            ),
            array(
                'title' => __('Products &amp; Services Settings', 'gravityformsm360'),
                'fields' => array(
                    array(
                        'name' => 'paymentAmount',
                        'label' => __('Payment Amount', 'gravityformsm360'),
                        'type' => 'select',
                        'choices' => $this->product_amount_choices(),
                        'required' => true,
                        'default_value' => 'form_total',
                        'tooltip' => '<h6>' . esc_html__('Payment Amount', 'gravityformsm360') . '</h6>' . esc_html__("Select which field determines the payment amount, or select 'Form Total' to use the total of all pricing fields as the payment amount.", 'gravityformsm360')
                    ),
                )
            ),
            array(
                'title' => __('Other Settings', 'gravityformsm360'),
                'fields' => $this->other_settings_fields()
            ),

        );
    }

    // Adds additional settings to the feed edit page.
    public function other_settings_fields()
    {
        $other_settings = array(
            array(
                'name' => 'billingInformation',
                'label' => __('Billing Information', 'gravityformsm360'),
                'type' => 'field_map',
                'field_map' => $this->billing_info_fields(),
                'tooltip' => '<h6>' . esc_html__('Billing Information', 'gravityformsm360') . '</h6>' . esc_html__('Map your Form Fields to the available listed fields.', 'gravityformsm360')
            ),
        );

        $other_settings[] = array(
            'name' => 'conditionalLogic',
            'label' => __('Conditional Logic', 'gravityformsm360'),
            'type' => 'feed_condition',
            'tooltip' => '<h6>' . esc_html__('Conditional Logic', 'gravityformsm360') . '</h6>' . esc_html__('When conditions are enabled, form submissions will only be sent to the payment gateway when the conditions are met. When disabled, all form submissions will be sent to the payment gateway.', 'gravityformsm360')
        );

        return $other_settings;
    }

    // Initialize authorizing the transaction for the product & services type feed or return the Stripe.js error.
    public function authorize($feed, $submission_data, $form, $entry)
    {

        $email = sanitize_email($submission_data['email']);

        if (!$email) {
            return $this->auth_error(__("Email not valid", 'gravityformsm360'), 'email');
        }

        $stripe_error = $this->get_stripe_js_error();
        if ($stripe_error) {
            return $this->auth_error($stripe_error);
        }

        $customer_id = GF_Marketing_360_Payments::get_customer_id($email);

        if (is_wp_error($customer_id)) {
            return $this->auth_error($customer_id->get_error_message());
        }

        return $this->authorize_payment_intent($feed, $submission_data, $form, $entry, $customer_id);
    }

    // Create the Marketing 360 Payments charge authorization and return any authorization errors which occur.
    public function authorize_payment_intent($feed, $submission_data, $form, $entry, $customer_id)
    {

        $payment_amount = $submission_data['payment_amount'];
        $email = $submission_data['email'];
        $currency = strtolower(rgar($entry, 'currency'));
        $amount = strval(round($payment_amount, 2) * 100);
        $payment_method_data = $this->get_payment_method_data();

        $product_names = array();

        $products = GFCommon::get_product_fields($form, $entry, true)['products'];

        foreach ($products as $product) {
            $product_names[] = $product['name'];
        }

        $description = implode(", ", $product_names);
        $this->log_debug(__METHOD__ . '(): Creating payment intent. Amount: ' . $amount . ' ' . strtoupper($currency));
        $payment_intent = GF_Marketing_360_Payments::create_payment_intent([
            'amount' => $amount,
            'receipt_email' => $email,
            'currency' => $currency,
            'customer' => $customer_id,
            'setup_future_usage' => 'off_session',
            'payment_method_data' => $payment_method_data,
            'capture_method' => 'manual',
            'description' => $description
        ]);

        if (is_wp_error($payment_intent)) {
            $this->log_error(__METHOD__ . '(): Payment intent creation failed. ' . $payment_intent->get_error_message());
            return $this->auth_error($payment_intent->get_error_message());
        }

        $this->log_debug(__METHOD__ . '(): Payment intent created. ID: ' . $payment_intent['id'] . '. Confirming.');
        $confirmed_payment_intent = GF_Marketing_360_Payments::confirm_payment_intent($payment_intent['id']);

        if (is_wp_error($confirmed_payment_intent)) {
            $this->log_error(__METHOD__ . '(): Payment intent confirmation failed. ID: ' . $payment_intent['id'] . '. ' . $confirmed_payment_intent->get_error_message());
            return $this->auth_error($confirmed_payment_intent->get_error_message());
        }

        if ($confirmed_payment_intent['status'] === 'requires_action') {
            $this->log_debug(__METHOD__ . '(): Payment intent requires additional action (3DS). ID: ' . $confirmed_payment_intent['id']);
            return array(
                'is_authorized' => false,
                'requires_action' => true,
                'transaction_id' => $confirmed_payment_intent['id'],
                'client_secret' => $confirmed_payment_intent['client_secret'],
                'error_message' => __('This card requires additional authentication.', 'gravityformsm360'),
                'error_type' => 'card'
            );
        }

        $this->log_debug(__METHOD__ . '(): Payment intent confirmed. ID: ' . $confirmed_payment_intent['id']);
        return array(
            "is_authorized" => true,
            "transaction_id" => $confirmed_payment_intent['id']
        );
    }

    // General-use method for handling authorization errors.
    public function auth_error($error_message, $error_type = "card")
    {
        return array(
            'is_authorized' => false,
            'error_message' => $error_message,
            'error_type' => $error_type
        );
    }

    // Get the Payment Method data from the frontend Stripe field.
    public function get_payment_method_data()
    {
        $response = json_decode(rgpost('stripe_response'));

        $data = [];

        if (!isset($response->token)) {
            return $data;
        }

        $data['type'] = 'card';
        $data['card'] = [
            'token' => $response->token->id
        ];
        return $data;
    }

    // Capture the Marketing 360 Payments charge which was authorized during validation.
    public function capture($auth, $feed, $submission_data, $form, $entry)
    {
        $transaction_id = $auth['transaction_id'];

        $capture_status = [
            'is_success' => false,
            'error_message' => '',
            'transaction_id' => $transaction_id,
            'amount' => $submission_data['payment_amount'],
            'payment_method' => 'card'
        ];
        $this->log_debug(__METHOD__ . '(): Capturing payment intent. ID: ' . $transaction_id);
        $captured_payment_intent = GF_Marketing_360_Payments::capture_payment_intent($transaction_id);

        if (is_wp_error($captured_payment_intent)) {
            $this->log_error(__METHOD__ . '(): Capture failed. ID: ' . $transaction_id . '. ' . $captured_payment_intent->get_error_message());
            $capture_status['error_message'] = $captured_payment_intent->get_error_message();
            return $capture_status;
        }

        $charge = $captured_payment_intent['charges']['data'][0];

        if (!$charge['paid']) {
            $this->log_error(__METHOD__ . '(): Charge not marked as paid after capture. ID: ' . $transaction_id);
            $capture_status['error_message'] = __('Something went wrong and your payment could not be completed', 'gravityformsm360');
            return $capture_status;
        }

        $this->log_debug(__METHOD__ . '(): Payment captured successfully. ID: ' . $transaction_id);
        $capture_status['is_success'] = true;
        return $capture_status;
    }

    // Check if a Stripe.js has an error or is missing the ID and then return the appropriate message.
    public function get_stripe_js_error()
    {

        // Get Stripe.js response.
        $response = $this->get_stripe_js_response();
        // If an error message is provided, return error message.
        if (isset($response->error)) {
            return $response->error->message;
        }

        return false;
    }

    // Response from Stripe.js is posted to the server as 'stripe_response'.
    public function get_stripe_js_response()
    {
        $response = json_decode(rgpost('stripe_response'));

        if (isset($response->token)) {
            $response->id = $response->token->id;
        } elseif (isset($response->paymentMethod)) {
            $response->id = $response->paymentMethod->id;
        }

        return $response;

    }

    // Gets the payment validation result, or displays an error on the frontend $authorization_result contains an error message.
    public function get_validation_result($validation_result, $authorization_result)
    {
        if (empty($authorization_result['error_message'])) {
            return parent::get_validation_result($validation_result, $authorization_result);
        }

        $error_page = 0;
        foreach ($validation_result['form']['fields'] as &$field) {
            switch ($authorization_result['error_type']) {
                case 'email':
                    if ($field->type === 'email') {
                        $field->failed_validation = true;
                        $field->validation_message = $authorization_result['error_message'];
                        $error_page = $field->pageNumber;
                        break;
                    }
                    break;
                case 'card':
                    if ($field->type === 'creditcard' || $field->type === 'm360_creditcard') {
                        if ($field->type === 'm360_creditcard' && (rgar($authorization_result, 'requires_action') || rgars($authorization_result, 'subscription/requires_action'))) {
                            $error_page = (GFCommon::has_pages($validation_result['form'])) ? GFFormDisplay::get_max_page_number($validation_result['form']) : $field->pageNumber;
                            add_filter('gform_validation_message', array($this, 'stripe_elements_requires_action_message'));
                            add_filter('gform_after_submission', function () use ($authorization_result) {
                                ?>
                                <script>
                                    document.dispatchEvent(new CustomEvent('gform_payment_requires_action', {
                                        detail: { client_secret: '<?php echo esc_js($authorization_result['client_secret']); ?>' }
                                    }));
                                </script>
                                <?php
                            });
                        } else {
                            $field->failed_validation = true;
                            $field->validation_message = $authorization_result['error_message'];
                            $error_page = $field->pageNumber;
                        }
                        break;
                    }
                    break;
            }
        }

        $validation_result['credit_card_page'] = $error_page;
        $validation_result['is_valid'] = false;

        return $validation_result;
    }

    // Display the 3DS authentication message on the frontend validation error.
    public function stripe_elements_requires_action_message($message)
    {
        return '<div class="validation_error">' . esc_html__('Your card requires additional authentication. Please complete the verification below.', 'gravityformsm360') . '</div>';
    }/* end get_validation_result */

    // Hide the uninstall button (purposefully empty function)
    public function render_uninstall()
    {

    }
}