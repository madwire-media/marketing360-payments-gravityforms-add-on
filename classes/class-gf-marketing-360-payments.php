<?php
// Gravity Forms Marketing 360 API connection class
class GF_Marketing_360_Payments {

	// Marketing 360 Payments Public API Endpoint.
	const MARKETING_360_PAYMENTS_LIVE_URL = 'https://payments.marketing360.com';

	// Marketing 360 Payments API Version.
	const VER = 'v1';

	// Stripe API Version.
	const STRIPE_VER = 'v1';

	// Integration ID for Gravity Forms Source.
	const INTEGRATION_ID = 'd828accdcc744213bd7d67534e0adc0b';

	// Marketing 360 Authentication Endpoint.
	private static $auth_url = 'https://login.marketing360.com/auth/realms/marketing360/protocol/openid-connect/token';

	// Marketing 360 Accounts List Endpoint.
	private static $accounts_url = 'https://app.marketing360.com/api/accounts';

	// M360 Account Number.
	private static $m360_account = '';

	// M360 API Client ID.
	private static $m360_client_id = '';

	// M360 API Client Secret.
	private static $m360_client_secret = '';

	// Construct the API route.
	public static function get_route( $resource ) {
		return self::get_payments_url() . '/' . self::VER . '/stripe/' . self::STRIPE_VER . '/' . $resource;
	}

	// Helper function for getting the M360 Payments API Endpoint.
	private static function get_payments_url() {
		return self::MARKETING_360_PAYMENTS_LIVE_URL;
	}

	// Set M360 Account Number.
	public static function set_m360_account( $m360_account ) {
		self::$m360_account = $m360_account;
	}

	// Get M360 Account Number.
	public static function get_m360_account() {
		if ( ! self::$m360_account ) {
			self::set_m360_account(gf_m360()->get_account());
		}
		return self::$m360_account;
	}

	// Set M360 API Client ID.
	public static function set_m360_client_id( $m360_client_id ) {
		self::$m360_client_id = $m360_client_id;
	}

	// Get M360 API Client ID.
	public static function get_m360_client_id() {
		if ( ! self::$m360_client_id ) {
			self::set_m360_client_id(gf_m360()->get_client_id());
		}
		return self::$m360_client_id;
	}

	// Set M360 API Client Secret.
	public static function set_m360_client_secret( $m360_client_secret ) {
		self::$m360_client_secret = $m360_client_secret;
	}

	// Get M360 API Client Secret.
	public static function get_m360_client_secret() {
		if ( ! self::$m360_client_secret ) {
			self::set_m360_client_secret(gf_m360()->get_client_secret());
		}
		return self::$m360_client_secret;
	}

	// Setup the request headers for requests to Marketing 360 Payments API.
	public static function get_m360_payments_request_headers($token = false, $account = false) {

		if (!$token) {
			$token = self::get_authorization();
		}

		$account = ($account) ? $account : self::get_m360_account();

		return [
			'Authorization'					=> 'Bearer ' . $token,
			'Marketing360-Account'			=> $account,
			'Marketing360-Payments-Source'	=> self::INTEGRATION_ID
		];
	}

	// Return the Bearer Token for request.
	public static function get_authorization() {
		return gf_m360()->get_client_token();
	}

	// Generate an M360 Access token by using M360 Account Credentials.
	public static function username_password_get_access_token($username = false, $password = false) {

		$response = wp_remote_post(
			self::$auth_url,
			[
				'method'      => 'POST',
				'timeout'     => 45,
				'headers'     => [
					'Content-Type' => 'application/x-www-form-urlencoded'
				],
				'body'        => [
					'username' => $username,
					'password' => $password,
					'grant_type' => 'password',
					'client_id' => 'gravity_forms_payments'
				],
			]
		);

		$response_code = $response['response']['code'];

		if ($response_code !== 200 ) {
			$error_message = $response['response']['message'];
			return new WP_Error($response_code, $error_message);
		} else {
			$result = json_decode($response['body']);
			return $result->access_token;
		}
	}

	// Generate an M360 Access token by using a client ID and Secret.
	public static function id_secret_get_access_token($client_id = "", $client_secret = "") {

		$basic_auth = base64_encode("{$client_id}:{$client_secret}");
		$response = wp_remote_post(
			self::$auth_url,
			[
				'method'      => 'POST',
				'timeout'     => 45,
				'headers'     => [
					'Authorization' => "Basic {$basic_auth}"
				],
				'body'        => [
					'grant_type' => 'client_credentials'
				],
			]
		);

		$response_code = $response['response']['code'];

		if ($response_code !== 200 ) {
			$error_message = $response['response']['message'];
			return new WP_Error($response_code, $error_message);
		} else {
			$result = json_decode($response['body']);
			return $result->access_token;
		}
	}

	// The REST Endpoint function to return a list of M360 Accounts the provided credentials are authorized for.
	public static function rest_list_m360_accounts(WP_REST_Request $request) {

		$username = $request['username'];
		$password = $request['password'];

		$token = self::username_password_get_access_token($username, $password);

		if (is_wp_error($token)) {
			http_response_code($token->get_error_code());
			die($token->get_error_message());
		} else {
			$accounts = self::get_m360_accounts($token);

			if (is_wp_error($accounts)) {
				http_response_code($token->get_error_code());
				die($token->get_error_message());
			}

			if ($accounts) {
				foreach($accounts as $index => &$account) {
					$details = self::get_m360_account_details($token, $account->accountNumber);

					if (is_wp_error($details) || isset($account->details->errors)) {
						unset($accounts[$index]);
						continue;
					}

					if (!isset($details->clientId) || !isset($details->secret)) {
						continue;
					}

					$account->client_id = $details->clientId;
					$account->client_secret = $details->secret;
					$account->payload = serialize($account);

					ob_start(); ?>
						<div class="m360-account">
							<?php if ($account->accountIcon): ?>
								<div class="m360-account-icon">
									<img src="<?php echo esc_url($account->accountIcon); ?>">
								</div>
							<?php endif; ?>
							<div class="m360-account-info">
								<h2 class="display-name"><?php echo esc_html($account->displayName); ?></h2>
								<h3 class="account-number"><?php echo esc_html($account->externalAccountNumber); ?></h3>
							</div>
						</div>
					<?php $account->html = ob_get_clean();
				}
			}

			return array_values($accounts);
		}
	}

	// Get the list of authorized M360 accounts using the token.
	public static function get_m360_accounts($token) {
		$response = wp_remote_post(
			self::$accounts_url,
			[
				'method'	=> 'GET',
				'headers'	=> [
					'Authorization' => "Bearer {$token}"
				],
				'body'		=> [
					'limit' => 999
				]
			]
		);

		$response_code = $response['response']['code'];

		if ($response_code !== 200 ) {
			$error_message = $response['response']['message'];
			return new WP_Error($response_code, $error_message);
		} else {
			$accounts = json_decode($response['body'])->response;
			return $accounts;
		}
	}

	// Get the details for the M360 Account using the token.
	public static function get_m360_account_details($token, $account) {
		$response = wp_remote_post(
			self::get_payments_url(). '/' . self::VER . '/api/integrations/' . self::INTEGRATION_ID,
			[
				'method'	=> 'PUT',
				'headers'	=> [
					'Authorization'					=> 'Bearer ' . $token,
					'Content-Length' 				=> 0,
					'marketing360-account'			=> $account,
				]
			]
		);

		if (is_wp_error($response)) {
			return $response->get_error_message();
		}

		$response_code = $response['response']['code'];

		if ($response_code !== 201 ) {
			$error_message = $response['response']['message'];
			return new WP_Error($response_code, $error_message);
		} else {
			$account_details = json_decode($response['body']);
			return $account_details;
		}
	}

	// Get the Stripe details for the M360 Account using Account Number, ID, and Secret
	public static function get_stripe_details($client_id, $client_secret, $account) {

		$token = self::id_secret_get_access_token($client_id, $client_secret);

		if (is_wp_error($token)) {
			http_response_code($token->get_error_code());
			die($token->get_error_message());
		} else {
			$response = wp_remote_post(
		        self::get_payments_url(). '/' . self::VER . '/api/account',
		        [
		            'method'      => 'GET',
		            'timeout'     => 45,
		            'headers'     => self::get_m360_payments_request_headers($token, $account),
		        ]
		    );

		    $response_code = $response['response']['code'];

			if ( $response_code !== 200 ) {
				return new WP_Error($response_code, $response['response']['message']);
		    } else {
				$result = json_decode($response['body']);
				return $result;
		    }
		}
	}

	// Callback to add the Stripe details to the M360 Account details after clicking "Update Settings" in the Add-On Settings Screen
    public static function add_stripe_details_callback($field, $field_setting) {
        $u_field_setting = @unserialize($field_setting);

        if ($u_field_setting !== false) {

        	$stripe_details = self::get_stripe_details(
        		$u_field_setting->client_id,
        		$u_field_setting->client_secret,
        		$u_field_setting->accountNumber
        	);

        	$u_field_setting->stripeAccountId = $stripe_details->stripeAccountId;
        	$u_field_setting->stripeKey = $stripe_details->stripeKey;

            $field_setting = serialize($u_field_setting);
        }

        return $field_setting;
    }

    // Look up if a customer's email address already exists in M360.
	public static function customer_lookup($email) {
		$response = wp_remote_post(
			self::get_route('customers') . "?email=" . $email,
			[
				'method' => 'GET',
				'headers' => self::get_m360_payments_request_headers()
			]
		);

		$response_code = $response['response']['code'];

		if ($response_code !== 200) {
			$error_message = json_decode($response['body']);
			return new WP_Error($response_code, $error_message);
		} else {
			return json_decode($response['body'], true)['data'];
		}
	}

	// Create a new customer in M360 using email address.
	public static function create_customer($email) {
		$response = wp_remote_post(
			self::get_route('customers'),
			[
				'method' => 'POST',
				'headers' => self::get_m360_payments_request_headers(),
				'body' => [
					'email' => $email
				]
			]
		);

		$response_code = $response['response']['code'];

		if ($response_code !== 200) {
			$error_message = json_decode($response['body']);
			return new WP_Error($response_code, $error_message);
		} else {
			return json_decode($response['body'], true);
		}
	}

	// Get the customer ID in M360 using email address. If the provided email doesn't exist in M360, a new customer is created and their ID is returned.
	public static function get_customer_id($email) {
        $customers = self::customer_lookup($email);

        if (is_wp_error($customers)) {
            return $customers;
        }

        if (!$customers) {
            $customer = self::create_customer($email);
            if (is_wp_error($customer)) {
                return $customer;
            }
            return $customer['id'];
        } else {
            $customer = $customers[0];
            return $customer['id'];
        }
    }

    // Create a Stripe Payment Intent in M360.
    public static function create_payment_intent($args) {
    	$response = wp_remote_post(
    		self::get_route('payment_intents'),
    		[
    			'method' => 'POST',
    			'headers' => self::get_m360_payments_request_headers(),
    			'body' => $args
    		]
    	);

		$response_code = $response['response']['code'];

		if ($response_code !== 200) {
			$error_message = json_decode($response['body']);
			return new WP_Error($response_code, $error_message->error->message);
		} else {
			return json_decode($response['body'], true);
		}
    }    

    // Get a Stripe Payment Intent from M360.
    public static function get_payment_intent($intent_id) {
    	$response = wp_remote_post(
    		self::get_route('payment_intents/' . $intent_id),
    		[
    			'method' => 'GET',
    			'headers' => self::get_m360_payments_request_headers(),
    		]
    	);

		$response_code = $response['response']['code'];

		if ($response_code !== 200) {
			$error_message = json_decode($response['body']);
			return new WP_Error($response_code, $error_message->error->message);
		} else {
			return json_decode($response['body'], true);
		}
    }    

    // Confirm a Stripe Payment Intent inside M360.
    public static function confirm_payment_intent($intent_id) {
    	$response = wp_remote_post(
    		self::get_route('payment_intents/' . $intent_id . '/confirm'),
    		[
    			'method' => 'POST',
    			'headers' => self::get_m360_payments_request_headers()
    		]
    	);

		$response_code = $response['response']['code'];

		if ($response_code !== 200) {
			$error_message = json_decode($response['body']);
			return new WP_Error($response_code, $error_message->error->message);
		} else {
			return json_decode($response['body'], true);
		}
    }    

    // Capture a Stripe Payment Intent inside M360.
    public static function capture_payment_intent($intent_id) {
    	$response = wp_remote_post(
    		self::get_route('payment_intents/' . $intent_id . '/capture'),
    		[
    			'method' => 'POST',
    			'headers' => self::get_m360_payments_request_headers()
    		]
    	);

		$response_code = $response['response']['code'];

		if ($response_code !== 200) {
			$error_message = json_decode($response['body']);
			return new WP_Error($response_code, $error_message->error->message);
		} else {
			return json_decode($response['body'], true);
		}
    }
}

