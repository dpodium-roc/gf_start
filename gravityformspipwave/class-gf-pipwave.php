<?php

add_action( 'wp', array( 'GFPipwave', 'maybe_thankyou_page' ), 5 );

GFForms::include_payment_addon_framework();

class GFPipwave extends GFPaymentAddOn {

    protected $_version                     = GF_PIPWAVE_VERSION;
    protected $_min_gravityforms_version    = '1.9';
    protected $_slug                        = 'gravityformspipwave';
    protected $_path                        = 'gravityformspipwave/pipwave.php';
    protected $_full_path                   = __FILE__;
    protected $_title                       = 'Gravity Forms Pipwave Add-On';
    protected $_short_title                 = 'Pipwave';
	protected $_supports_callbacks          = true;

    private static $_instance               = null;

    public static function get_instance() {
        if( self::$_instance == null ) {
            self::$_instance = new GFPipwave();
        }
        return self::$_instance;
    }

    //handle hook and loading languages file
    /*
    public function init() {
        parent::init();
        add_filter( 'gform_submit_button', array( $this, 'form_submit_button' ), 10, 2 );

    }
    */
    //-PIPWAVE SCRIPT?----------------------------------------------------------------------


    //set data [prepare data needed to send to pipwave]
    public function setData( $entry, $settings, $feed ) {

	    $country = rgar( $entry, $feed['meta']['shippingInformation_country'] );
	    $shippingCountryCode                   = GF_Fields::get( 'address' )->get_country_code( $country );

	    $country = rgar( $entry, $feed['meta']['billingInformation_country'] );
	    $billingCountryCode                   = GF_Fields::get( 'address' )->get_country_code( $country );

	    //var_dump($entry);
	    //var_dump($feed);
	    $string = rgar( $entry, $feed['meta']['fee_shipping_amount'] );
	    $shipping_amount = preg_replace('/[^0-9.]/', '', $string );

	    //modify success url
	    $pageURL = $this->get_current_url();

	    $urlInfo = 'ids=' . urlencode($feed['form_id']) . '|' . urlencode( rgar( $entry, 'id' ) );
	    $urlInfo .= '&hash=' . wp_hash( $urlInfo );
	    $successUrl = add_query_arg( 'gf_pipwave_return', base64_encode($urlInfo), $pageURL );

        $data = array(
            'action' => 'initiate-payment', 
            'timestamp' => time(), 
            'api_key' => rgar( $settings, 'api_key' ),
            'api_secret' => rgar( $settings, 'api_secret' ),
            'txn_id' => rgar( $entry, 'id' ),
            'amount' => (float)rgar( $entry, $feed['meta']['fee_payment_amount'] ),
            'currency_code' => rgar( $entry, 'currency' ),
            'shipping_amount' => (float)$shipping_amount,
            'session_info' => array(
	            'ip_address' => rgar( $entry, 'ip' ),
            ),
            'buyer_info' => array(
            	//not sure about this id thing
                'id' => rgar( $entry, $feed['meta']['billingInformation_email'] ),
                'email' => rgar( $entry, $feed['meta']['billingInformation_email'] ),
                'first_name' => rgar( $entry, $feed['meta']['billingInformation_firstName'] ),
                'last_name' => rgar( $entry, $feed['meta']['billingInformation_lastName'] ),
                'contact_no' => rgar( $entry, $feed['meta']['billingInformation_contactNumber'] ),
                'country_code' => $billingCountryCode,
                'surcharge_group' => $feed['meta']['processing_fee_group'],
            ), 
            'shipping_info' => array(
                'name' => rgar( $entry, $feed['meta']['shippingInformation_firstName'] ) . ' ' . rgar( $entry, $feed['meta']['shippingInformation_lastName'] ),
                'city' => rgar( $entry, $feed['meta']['shippingInformation_city'] ),
                'zip' => rgar( $entry, $feed['meta']['shippingInformation_zip'] ),
                'country_iso2' => $shippingCountryCode,
                'email' => rgar( $entry, $feed['meta']['shippingInformation_email'] ),
                'contact_no' => rgar( $entry, $feed['meta']['shippingInformation_contactNumber'] ),
                'address1' => rgar( $entry, $feed['meta']['shippingInformation_address'] ),
                'address2' => rgar( $entry, $feed['meta']['shippingInformation_address2'] ),
                'state' => rgar( $entry, $feed['meta']['shippingInformation_state'] ),
            ), 
            'billing_info' => array(
	            'name' => rgar( $entry, $feed['meta']['billingInformation_firstName'] ) . ' ' . rgar( $entry, $feed['meta']['billingInformation_lastName'] ),
	            'city' => rgar( $entry, $feed['meta']['billingInformation_city'] ),
	            'zip' => rgar( $entry, $feed['meta']['billingInformation_zip'] ),
	            'country_iso2' => $billingCountryCode,
	            'email' => rgar( $entry, $feed['meta']['billingInformation_email'] ),
	            'contact_no' => rgar( $entry, $feed['meta']['billingInformation_contactNumber'] ),
	            'address1' => rgar( $entry, $feed['meta']['billingInformation_address'] ),
	            'address2' => rgar( $entry, $feed['meta']['billingInformation_address2'] ),
	            'state' => rgar( $entry, $feed['meta']['billingInformation_state'] ),
            ), 
            'api_override' => array(
                'success_url' => ! empty( $feed['meta']['successUrl'] ) ? urlencode( $feed['meta']['successUrl'] ) : $successUrl,
                'fail_url' => ! empty( $feed['meta']['failUrl'] ) ? urlencode( $feed['meta']['failUrl'] ) :  get_bloginfo( 'url' ) ,
                'notification_url' => 'https://a3a9ef36.ngrok.io/wordpress/?page=gf_pipwave_ipn', //urlencode( get_bloginfo( 'url' ) . '/?page=gf_pipwave_ipn' ),
            ), 
        );
        return $data;

    }

    public function setSignatureParam( $data ) {
        $signatureParam = array(
            'api_key' => $data['api_key'],
            'api_secret' => $data['api_secret'],
            'txn_id' => $data['txn_id'],
            'amount' => $data['amount'],
            'currency_code' => $data['currency_code'],
            'action' => $data['action'],
            'timestamp' => $data['timestamp'],
        );
        return $signatureParam;
    }
    //compare signature after receiving notification from pipwave
	public function compareSignature( $signature, $newSignature ) {
		if ($signature != $newSignature) {
			return $transaction_status = -1;
		}
		return;
	}
	//generate signature
	public function generate_pw_signature( $signatureParam ) {
		ksort( $signatureParam );
		$signature = "";
		foreach ( $signatureParam as $key => $value ) {
			$signature .= $key . ':' . $value;
		}
		return sha1( $signature );
	}
	//fire to pipwave
	public function send_request_to_pw( $data, $pw_api_key, $url ) {
		$agent = "Mozilla/4.0 ( compatible; MSIE 6.0; Windows NT 5.0 )";
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_PROXY, 'my-proxy.offgamers.lan:3128' );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'x-api-key:' . $pw_api_key ) );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_VERBOSE, 1 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 120 );
		curl_setopt( $ch, CURLOPT_USERAGENT, $agent );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		$response = curl_exec( $ch );
		if ( $response == false ) {
			echo "<pre>";
			echo 'CURL ERROR: ' . curl_errno( $ch ) . '::' . curl_error( $ch );
			die;
		}
		curl_close( $ch );
		return json_decode( $response, true );
	}
	//render sdk THIS is the form that appear
	//now dont need this
	public function renderSdk( $response, $api_key, $sdk_url, $loading_img ){
		if ($response['status'] == 200) {
			$api_data = json_encode([
				'api_key' => $api_key,
				'token' => $response['token']
			]);
			$result = <<<EOD
                    <div id="pwscript" class="text-center"></div>
                    <div id="pwloading" style="text-align: center;">
                        <img src="$loading_img" />
                    </div>
                    <script type="text/javascript">
                        var pwconfig = $api_data;
                        (function (_, p, w, s, d, k) {
                            var a = _.createElement("script");
                            a.setAttribute('src', w + d);
                            a.setAttribute('id', k);
                            setTimeout(function() {
                                var reqPwInit = (typeof reqPipwave != 'undefined');
                                if (reqPwInit) {
                                    reqPipwave.require(['pw'], function(pw) {
                                        pw.setOpt(pwconfig);
                                        pw.startLoad();
                                    });
                                } else {
                                    _.getElementById(k).parentNode.replaceChild(a, _.getElementById(k));
                                }
                            }, 800);
                        })(document, 'script', "$sdk_url", "pw.sdk.min.js", "pw.sdk.min.js", "pwscript");
                    </script>
EOD;
		} else {
			$result = isset($response['message']) ? (is_array($response['message']) ? implode('; ', $response['message']) : $response['message']) : "Error occured";
		}

		return $result;
	}

	//-custom script------------------------------------------------------------------------------------------------
	//get current page url
	public function get_current_url() {
		$pageURL = GFCommon::is_ssl() ? 'https://' : 'http://';

		$server_port = apply_filters( 'gform_paypal_return_url_port', $_SERVER['SERVER_PORT'] );

		if ( $server_port != '80' ) {
			$pageURL .= $_SERVER['SERVER_NAME'] . ':' . $server_port . $_SERVER['REQUEST_URI'];
		} else {
			$pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		}
		return $pageURL;
	}

	//get $someUrl['URL'],['RENDER_URL'],['LOADING_IMAGE_URL']
	public function setUrl( $testMode ) {
		$someUrl = $this->getUrlByTestMode( $testMode );
		return $someUrl;
	}

	//used in setUrl()
	public function getUrlByTestMode( $testMode ) {
		if ( $testMode == 0 ) {
			$someUrl = [
				'URL' => 'https://api.Pipwave.com/payment',
				'RENDER_URL' => 'https://secure.Pipwave.com/sdk/',
				'LOADING_IMAGE_URL' => 'https://secure.Pipwave.com/images/loading.gif',
			];
		} else {
			if ( $testMode == 1 ) {
				$someUrl = [
					'URL' => 'https://staging-api.Pipwave.com/payment',
					'RENDER_URL' => 'https://staging-checkout.Pipwave.com/sdk/',
					'LOADING_IMAGE_URL' => 'https://staging-checkout.Pipwave.com/images/loading.gif',
				];
			} else {
				$someUrl = '';//error
			}
		}
		return $someUrl;
	}

	//--------------------------------------------------------------------------------------------------------------
    //this will run when the submit button is clicked
    public function redirect_url( $feed, $submission_data, $form, $entry ) {

	    if ( ! rgempty( 'gf_pipwave_return', $_GET ) ) {
		    return false;
	    }

	    //change payment status to 'processing'
        GFAPI::update_entry_property( $entry['id'], 'payment_status', 'PendingPayment' );


	    $settings = $this->get_plugin_settings();
	    //print_r( $entry );
		//var_dump($feed);

	    $data = $this->setData( $entry, $settings, $feed );
	    //print_r( $data );
	    //var_dump($data);

	    $signatureParam = $this->setSignatureParam( $data );
	    $pwSignature = $this->generate_pw_signature( $signatureParam );

	    //after put in pipwave signature, the data is now complete
	    $data['signature'] = $pwSignature;

	    //var_dump($data['signature']);
	    //var_dump($data);

	    //prepare url for render
	    $testMode = rgar( $settings, 'test_mode' );
	    //echo $testMode;
	    $someUrl = $this->setUrl( $testMode );

	    //this is the form to redirect buyer to 3rd party
	    //var_dump($someUrl);
	    //var_dump($data['api_key']);
	    $response = $this->send_request_to_pw( $data, $data['api_key'], $someUrl['URL'] );
	    //var_dump($response);
	    //$result = $this->renderSdk( $response, $data['api_key'], $someUrl['RENDER_URL'], $someUrl['LOADING_IMAGE_URL'] );
	    //var_dump($result);
	    //echo $result;
	    //echo $result;

        //print_r( $result );
        //how to display $result???
	    //$return_url = '&return=';
	    //$url = 'http://staging-api-ag.pipwave.com/payment';//$testMode == 'production' ? $this->production_url : $this->sandbox_url;
	    //$url .= "?notify_url={$data['api_override']['notification_url']}&charset=UTF-8&currency_code={$data['currency_code']}&custom={$return_url}";
	    //$return_url = 'http://staging-api-ag.pipwave.com/payment';
	    //$ipn_url = '';
	    //$currency = $data['currency_code'];

	    //$data = json_encode( $data );
	    //$url .= "?notify_url={$ipn_url}&charset=UTF-8&currency_code={$currency}&data={$data}&pw_api_key={data['api_key']}";
	    //$url .= "?data={$data}&pw_api_key={$data['api_key']}";
		$url = 'https://staging-checkout.pipwave.com/pay?token=';
		$url .= $response['token'];

		//$url = '';
	    return $url;
    }

    //to check whether is pipwave or other payment gateway
	// called by callback()
	public function is_callback_valid() {
		if ( rgget( 'page' ) != 'gf_pipwave_ipn' ) {
			return false;
		}

		return true;
	}

	//receive notification from pipwave [transaction status and data]
	public function callback() {
		header( 'HTTP/1.1 200 OK' );
		echo "OK";
		//IPN from Pipwave
		$post_data = json_decode( file_get_contents( 'php://input' ), true );
		//var_dump($post_data);

		$timestamp = (isset($post_data['timestamp']) && !empty($post_data['timestamp'])) ? $post_data['timestamp'] : time();
		$pw_id = (isset($post_data['pw_id']) && !empty($post_data['pw_id'])) ? $post_data['pw_id'] : '';
		$order_number = (isset($post_data['txn_id']) && !empty($post_data['txn_id'])) ? $post_data['txn_id'] : '';
		$order_id = (isset($post_data['extra_param1']) && !empty($post_data['extra_param1'])) ? $post_data['extra_param1'] : '';
		$amount = (isset($post_data['amount']) && !empty($post_data['amount'])) ? $post_data['amount'] : '';
		$currency_code = (isset($post_data['currency_code']) && !empty($post_data['currency_code'])) ? $post_data['currency_code'] : '';
		$transaction_status = (isset($post_data['transaction_status']) && !empty($post_data['transaction_status'])) ? $post_data['transaction_status'] : '';
		$payment_method = isset($post_data['payment_method_title']) ? __('pipwave', 'wc_pipwave') . " - " . $post_data['payment_method_title'] : $this->title;
		$signature = (isset($post_data['signature']) && !empty($post_data['signature'])) ? $post_data['signature'] : '';
		$txn_sub_status = (isset($post_data['txn_sub_status']) && !empty($post_data['txn_sub_status'])) ? $post_data['txn_sub_status'] : time();

		// pipwave risk execution result
		$pipwave_score = isset($post_data['pipwave_score']) ? $post_data['pipwave_score'] : '';
		$rule_action = isset($post_data['rules_action']) ? $post_data['rules_action'] : '';
		$message = isset($post_data['message']) ? $post_data['message'] : '';

		$settings = $this->get_plugin_settings();

		$data_for_signature = array(
			'timestamp' => $timestamp,
			'api_key' => rgar( $settings, 'api_key' ),
			'pw_id' => $pw_id,
			'txn_id' => $order_number,
			'amount' => $amount,
			'currency_code' => $currency_code,
			'transaction_status' => $transaction_status,
			'api_secret' => rgar( $settings, 'api_secret' ),
		);
		//var_dump($data_for_signature);
		$newSignature = $this->generate_pw_signature( $data_for_signature );
		if ( $this->compareSignature( $signature, $newSignature ) ) {
			$transaction_status = $this->compareSignature( $signature, $newSignature );
		}
		$entry = GFAPI::get_entry( $order_number );

		//$transaction_status = 25;
		//$txn_sub_status = 502;
		$entry = $this->processNotification( $transaction_status, $entry, $txn_sub_status );
		$entry = GFAPI::get_entry( $order_number );
		var_dump( $entry );
		$action = '';
		//var_dump($GLOBALS);
		return $action;
	}

	//sub function
	//to get transaction status and change the payment status on gravity form
	public function processNotification( $transaction_status, $entry, $txn_sub_status )
	{
		switch ($transaction_status) {
			case 5: // pending
				//i didnt test this
				GFAPI::update_entry_property( $entry['id'], 'payment_status', 'PendingMerchantConfirmation' );
				break;
			case 1: // failed
				GFAPI::update_entry_property( $entry['id'], 'payment_status', 'Fail' );
				break;
			case 2: // cancelled
				GFAPI::update_entry_property( $entry['id'], 'payment_status', 'Cancelled' );
				break;
			case 10: // complete
				//$status = SELF::PIPWAVE_PAID;
				//$order->setState($status)->setStatus($status);

				//502
				if ( $txn_sub_status == 502 ) {
					GFAPI::update_entry_property( $entry['id'], 'payment_status', 'Completed' );
				}
				break;
			case 20: // refunded
				GFAPI::update_entry_property( $entry['id'], 'payment_status', 'Refunded' );
				break;
			case 25: // partial refunded
				GFAPI::update_entry_property( $entry['id'], 'payment_status', 'PartialRefunded' );

				//$order->addStatusHistoryComment('Payment status: Partial Refunded. Amount: '.$refund_amount)->setIsCustomerNotified(true);
				break;
			case -1: // signature mismatch
				GFAPI::update_entry_property( $entry['id'], 'payment_status', 'SignatureMismatch' );

				//$order->addStatusHistoryComment('Payment status: Signature Mismatch.')->setIsCustomerNotified(true);
				break;
			case 123456789:
				break;
			default:
				GFAPI::update_entry_property( $entry['id'], 'payment_status', 'UnknownError' );
		}
		return $entry;
	}


	//this should be the payment success page
	public static function maybe_thankyou_page() {
		$instance = self::get_instance();

		if ( ! $instance->is_gravityforms_supported() ) {
			return;
		}


		if ( $str = rgget( 'gf_pipwave_return' ) ) {
			$str = base64_decode( $str );
			//var_dump($str);

			parse_str( $str, $query );
			if ( wp_hash( 'ids=' . $query['ids'] ) == $query['hash'] ) {

				list( $form_id, $lead_id ) = explode( '|', $query['ids'] );

				$form = GFAPI::get_form( $form_id );
				$lead = GFAPI::get_entry( $lead_id );
				//var_dump($form);
				if ( ! class_exists( 'GFFormDisplay' ) ) {
					require_once( GFCommon::get_base_path() . '/form_display.php' );
				}


				//var_dump($lead);

				$confirmation = GFFormDisplay::handle_confirmation( $form, $lead, false );

				//var_dump($confirmation);
				//die();
				if ( is_array( $confirmation ) && isset( $confirmation['redirect'] ) ) {
					header( "Location: {$confirmation['redirect']}" );
					exit;
				}


				GFFormDisplay::$submission[ $form_id ] = array( 'is_confirmation'      => true,
				                                                'confirmation_message' => $confirmation,
				                                                'form'                 => $form,
				                                                'lead'                 => $lead
				);

				return false;
			}

		}
	}






    //- OTHERS ---------------------------------------------------------------------------------------------------------------------------------------
    

    //-ADMIN----------------------------------------------------------------------------
    
    
    
    //-SETTING PAGE-------GFdemo>dashboard>form>left panel>settings>pipwave------------------------------------------------------------------------------------------
    
    public function plugin_settings_fields() {

        return array( 
            //first section
            array( 
                'title'         => esc_html__( 'pipwave', 'translator' ),
                'fields'        => array( 
                    //first row
                    array( 
                        'name'              => 'api_key',
                        'label'             => esc_html__( 'API Key', 'translator' ),
                        'type'              => 'text',
                        'class'             => 'medium',
                        'required'          => true,
                        'tooltip'           => '<h6>' . esc_html__( 'API Key', 'translator' ) . '</h6>' . sprintf( esc_html__( 'API Key provided by pipwave is in this %slink%s.', 'translator' ), '<a href="https://merchant.pipwave.com/development-setting/index" target="_blank">', '</a>' ),
                     ),
                    //second row
                    array( 
                        'name'              => 'api_secret',
                        'label'             => esc_html__( 'API Secret', 'translator' ),
                        //type = password is not implemented
                        'type'              => 'text',
                        'class'             => 'medium',
                        'required'          => true,
                        'tooltip'           => '<h6>' . esc_html__( 'API Secret', 'translator' ) . '</h6>' . sprintf( esc_html__( 'API Secret provided by pipwave is in this %slink%s.', 'translator' ), '<a href="https://merchant.pipwave.com/development-setting/index" target="_blank">', '</a>' ),
                     ),
                    //third row
	                /*
                    array(
                        'name'              => 'test_mode',
                        'label'             => esc_html__( 'Test Mode', 'translator' ),
                        'type'              => 'radio',
                        'default_value'     => '0',
                        'choices'           => array(
                            array(
                                'label'     => esc_html__( 'Yes', 'translator' ),
                                'value'     => '1',
                            ),
                            array(
                                'label'     => esc_html__( 'No', 'translator' ),
                                'value'     => '0',
                                'selected'  => true,
                            ),
                        ),
                        'horizontal'        => true,
                    ),
	                */
                    //save
                    array( 
                        'type'              => 'save',
                        'message'           => array( 'success' => esc_html__( 'Settings have been updated.', 'translator' ) ),
                     ),
                 ),
             ),
         );
    }
    
    
    //-FORM SETTING PAGE----------GFDemo>Form>Choose a form>Edit>Setting>pipwave-------------------------------------------------------------------------
    public function feed_settings_fields() {
        $default_settings = parent::feed_settings_fields();

        //make test mode field, put in top section
        $fields = array(
            array( 
                'name'              => 'processing_fee_group',
                'label'             => esc_html__( 'Processing Fee Group', 'translator' ),
                'type'              => 'text',
                'class'             => 'medium',
                'tooltip'           => '<h6>' . esc_html__( 'Processing Fee Group', 'translator' ) . '</h6>' . sprintf( esc_html__( 'Payment Processing Fee Group can be configured %shere%s. Please fill referenceId in the blank.( if available )', 'translator' ), '<a href="https://merchant.pipwave.com/account/set-processing-fee-group#general-processing-fee-group" target="_blank">', '</a>' ),
            ),
	        array(
		        'name' => 'successUrl',
		        'label' => 'Success Url',
		        'type' => 'text',
		        'class'             => 'medium',
		        'tooltip' => '<h6>Success Url</h6>pipwave will redirect to this page if payment success.',
	        ),
	        array(
		        'name' => 'failUrl',
		        'label' => 'Fail Url',
		        'type' => 'text',
		        'class'             => 'medium',
		        'tooltip' => '<h6>Fail Url</h6>pipwave will redirect to this page if payment fail.',
		        ),
         );

        //var_dump($default_settings);
        $default_settings = parent::add_field_after( 'feedName', $fields, $default_settings );
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	    //get set shipping amount
	    $fee = $this->feed_shipping_amount();
	    //var_dump($fee);

	    //put shipping ammount before billing information
	    $default_settings = parent::add_field_before( 'billingInformation', $fee, $default_settings );

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	    //get set product
	    $product = $this->feed_product();
	    //var_dump($fee);

	    //put shipping ammount before billing information
	    $default_settings = parent::add_field_before( 'billingInformation', $product, $default_settings );

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

		//create shipping information sub from copy and paste billing address
	    $shipping_info = parent::get_field( 'billingInformation', $default_settings );

	    //var_dump($shipping_info);

	    //change the name, label, tooltip
	    $shipping_info['name'] = 'shippingInformation';
	    $shipping_info['label'] = 'Shipping Information';
	    $shipping_info['tooltip'] = '<h6>Shipping Information</h6>Map your Form Fields to the available listed fields.';

	    //add customer first name / last name
	    $shipping_fields = $shipping_info['field_map'];
	    $add_first_name = true;
	    $add_last_name = true;
	    $add_contact_no = true;
	    foreach ( $shipping_fields as $mapping ) {
		    //check first/last name if it exist in billing fields
		    if ( $mapping['name'] == 'firstName' ) {
			    $add_first_name = false;
		    } else if ( $mapping['name'] == 'lastName' ) {
			    $add_last_name = false;
		    } else if ( $mapping['name'] == 'contactNumber' ) {
			    $add_contact_no = false;
		    }
	    }

	    if ( $add_contact_no ) {
		    array_unshift( $shipping_info['field_map'], array( 'name' => 'contactNumber', 'label' => esc_html__( 'Contact Number', 'translator' ), 'required' => false ) );
	    }
	    if ( $add_last_name ) {
		    //add last name
		    array_unshift( $shipping_info['field_map'], array( 'name' => 'lastName', 'label' => esc_html__( 'Last Name', 'translator' ), 'required' => false ) );
	    }
	    if ( $add_first_name ) {
		    array_unshift( $shipping_info['field_map'], array( 'name' => 'firstName', 'label' => esc_html__( 'First Name', 'translator' ), 'required' => false ) );
	    }

	    //place shipping information after billing information
	    $default_settings = parent::add_field_after( 'billingInformation', $shipping_info, $default_settings );

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	    // get biling info section
	    $billing_info = parent::get_field( 'shippingInformation', $default_settings );

	    $billing_info['name'] = 'billingInformation';
	    $billing_info['label'] = 'Billing Information';
	    $billing_info['tooltip'] = '<h6>Billing Information</h6>Map your Form Fields to the available listed fields.';

	    //coz buyer.id and buyer.email need this
	    $billing_info['field_map'][3]['required'] = true;
	    //coz buyer.country need this
	    $billing_info['field_map'][9]['required'] = true;
	    //var_dump($billing_info['field_map'][3] );

	    $default_settings = parent::replace_field( 'billingInformation', $billing_info, $default_settings );

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	    //var_dump($default_settings[3][1]);
	    //print_r($default_settings);

	    //$x = urlencode( get_bloginfo( 'url' ) );
	    //var_dump($x);
        return $default_settings;
    }

    public function feed_shipping_amount() {
	    $test[0] = array(
		    'name' => 'payment_amount',
		    'label' => 'Payment Amount',
		    'required' => true,
		    'tooltip' => '<h6>Payment Amount</h6>Map this to the final amount.',
	    );
	    $test[1] = array(
		    'name' => 'shipping_amount',
		    'label' => 'Shipping Amount',
		    'required' => false,
	    );

	    $fee = array(
		    'name' => 'fee',
		    'label' => 'Fee',
		    'type' => 'field_map',
		    'field_map' => $test,
		    'tooltip' => '<h6>Shipping Amount</h6>Map your Form Fields to the available listed fields.',
	    );
	    return $fee;
    }
	public function feed_product() {
		$test[0] = array(
			'name' => 'product_name',
			'label' => 'Product Name',
			'required' => false,
			'tooltip' => '<h6>Product</h6>Map this to your product.',
		);

		$product = array(
			'name' => 'product',
			'label' => 'Product',
			'type' => 'field_map',
			'field_map' => $test,
		);
		return $product;
	}






    
    
    //--testing-----------------------------------------------------------
    //--FRONTEND TEST-----------------------------------------------------------------
    
    
    //--to tell what notification event we have------------------------------------------------------------------
    /*
     *
     public function supported_notification_events( $form ) {

        // If this form does not have feed, return false.
        if ( ! $this->has_feed( $form['id'] ) ) {
            return false;
        }

        // Return notification events.
        return array(
            'complete_payment'          => esc_html__( 'Payment Completed', 'translator' ),
            'refund_payment'            => esc_html__( 'Payment Refunded', 'translator' ),
            'fail_payment'              => esc_html__( 'Payment Failed', 'translator' ),
        );
    }
    */
    //-- our own custom pipwave page ---------------------------------------------------------------------------------------------------------
	/**
	 *
	 */
	public function plugin_page(){



    	$html = <<<EOD
<style>
    .center {
	    text-align: center;
	}	
</style>
	<div class = "center"><img src="https://www.pipwave.com/wp-content/themes/zerif-lite-child/images/logo_bnw.png" /></div>
    <h1>Install & Configure pipwave in Gravity Forms</h1>
    <p>You will need a pipwave account. If you don't have one, don't worry, you can create one during the configuration process. Please click one of the option below :</p>
    	<ul>
    		<li><a href="#figure">With Figure</a></li>
    		<li><a href="#text">Only Text</a></li>
    	</ul>
	<h2>Getting Started</h2>
	<h4 id="figure">With figure</h4>
EOD;
        echo $html;


		$message = [
			'',
			'Click Dashboard',//1
			'Click Plugins',//2
			'Search for our plugin. If found, please proceed to step 10. If not found please proceed to next step.',
			'Click Add New',
			'Click Upload Plugin',
			'Click Browse...',
			'Select the zip file of the plugin',
			'Click open. Then Click Install Now',
			'Click Dashboard. Then hover to Plugins. Then Click Installed Plugins',
			'Find our plugin (pipwave). Then Click Activate',
			'Click Settings',
			'Key in Api key and secret. Both of them can be obtained in the "question" figure',
			'Click Form',
			'Select your form, Click Setting, then Click pipwave',
			'Click pipwave',
			'Click Add New, then enter the information required. \'*\' firgure means the information is needed',
		];

		for ( $i = 1; $i < 17; $i++) {
			$img = GFCommon::get_base_url() . '../../gravityformspipwave/images/height/' . $i . 'height.png';
			$html = '<p>Step ' . $i . ' ' . $message[$i] . '</p>';
			$html .= '<img src = ' . $img . ' width="1000" ></img>';
			echo $html;
		}



		$html = '
<h3 id="text">Only Text</h3>
<h4>Installation</h4>
	<ol>
		<li>CLICK Dashboard</li>
		<li>CLICK plugins</li>
		<li>Search the plugin</li>
		<ol>
			<li>If not found, download the zip file of the plugin from any source</li>
			<li>Go back to gravity form</li> 
			<li>CLICK Add New</li>
			<li>CLICK upload plugin</li>
			<li>CLICK browse</li>
			<li>SELECT the zip file</li>
			<li>CLICK Install</li>
		</ol>
		<li>CLICK plugins</li>
		<li>CLICK activate</li>
	</ol>
<h4>Configuration 1</h4>
    <ol>
	    <li>CLICK `Dashboard`</li>
	    <li>HOVER to `form` [don\'t click `form`]</li>
	    <li>CLICK `Settings` [the one that appear after you hover] [not the setting below form]</li>
	    <li>CLICK `Pipwave`</li>
	    <li>ENTER pipwave api key and api secret</li>
	    <li>links are available on the `question mark`</li>
	    <li>Let\'s go to the nest one</li>
    </ol>
<h4>Configuration 2</h4>
    <ol>
	    <li>CLICK `Dashboard`</li>
	    <li>CLICK `Form` [this time is click it]</li>
	    <li>HOVER/SELECT any form</li>
	    <li>CLICK `Settings`</li>
		<li>CLICK `Pipwave`</li>
		<li>CLICK `Addnew`</li>
		<li>FILL in the information [* means it is necessary]</li>
    </ol>
		';

		echo $html;


		
	}
	
    
    
    
    //=============================================================================
    public function feed_list_no_item_message() {
		$settings = $this->get_plugin_settings();
		if ( ( rgar( $settings, 'api_key' ) == null || rgar( $settings, 'api_key' ) == '' ) || ( rgar( $settings, 'api_secret' ) == null || rgar( $settings, 'api_secret' ) == '' ) ) {
			return sprintf( esc_html__( 'To get started, please configure your %sPipwave Settings%s!', 'translator' ), '<a href="' . admin_url( 'admin.php?page=gf_settings&subview=' . $this->_slug ) . '">', '</a>' );
		} else {
			return parent::feed_list_no_item_message();
		}
	}
	//= this not needed anymore ==========================================================================================
    public function get_feed_value() {
    return array(
        array( 'name' => 'first_name', 'label' => 'First Name', 'meta_name' => 'billingInformation_firstName' ),
        array( 'name' => 'last_name', 'label' => 'Last Name', 'meta_name' => 'billingInformation_lastName' ),
        array( 'name' => 'email', 'label' => 'Email', 'meta_name' => 'billingInformation_email' ),
        array( 'name' => 'address1', 'label' => 'Address', 'meta_name' => 'billingInformation_address' ),
        array( 'name' => 'address2', 'label' => 'Address 2', 'meta_name' => 'billingInformation_address2' ),
        array( 'name' => 'city', 'label' => 'City', 'meta_name' => 'billingInformation_city' ),
        array( 'name' => 'state', 'label' => 'State', 'meta_name' => 'billingInformation_state' ),
        array( 'name' => 'zip', 'label' => 'Zip', 'meta_name' => 'billingInformation_zip' ),
        array( 'name' => 'country', 'label' => 'Country', 'meta_name' => 'billingInformation_country' ),
        );
    }

    //============================================================================================================
    public function save_feed_settings( $feed_id, $form_id, $settings ){
        return parent::save_feed_settings( $feed_id, $form_id, $settings );
    }

    //============================================================================================================
	//-IPN TESTING----------------------------------------------------------------------------------------------------------------

}