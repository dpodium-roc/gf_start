<?php

GFForms::include_payment_addon_framework();

class GFPipwave extends GFPaymentAddOn {

    protected $_version = GF_PIPWAVE_VERSION;
    protected $_min_gravityforms_version = '1.9';
    protected $_slug = 'gravityformspipwave';
    protected $_path = 'gravityformspipwave/pipwave.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Gravity Forms Pipwave Add-On';
    protected $_short_title = 'Pipwave';

    private static $_instance = null;

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
        add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );
    }
    */
    //-PIPWAVE SCRIPT?----------------------------------------------------------------------

    //set data
    function setData( $entry, $settings, $feed ) {

        //add ngrok url to replace 'localhost'
        //$notificationUrl = 'https://9ca45aa5.ngrok.io/omg/omg/notification/notification/index';
	    /*
	     * //Set return mode to 2 (PayPal will post info back to page). rm=1 seems to create lots of problems with the redirect back to the site. Defaulting it to 2.
		$return_mode = '2';

		$return_url = '&return=' . urlencode( $this->return_url( $form['id'], $entry['id'] ) ) . "&rm={$return_mode}";

		//Cancel URL
		$cancel_url = ! empty( $feed['meta']['cancelUrl'] ) ? '&cancel_return=' . urlencode( $feed['meta']['cancelUrl'] ) : '';

		//Don't display note section
		$disable_note = ! empty( $feed['meta']['disableNote'] ) ? '&no_note=1' : '';

		//Don't display shipping section
		$disable_shipping = ! empty( $feed['meta']['disableShipping'] ) ? '&no_shipping=1' : '';

		//URL that will listen to notifications from PayPal
		$ipn_url = urlencode( get_bloginfo( 'url' ) . '/?page=gf_paypal_ipn' );

	    */

	    $feed['meta']['feedName'];
	    $x[] = 'temp variable';
	    $country = rgar( $entry, $feed['meta']['shippingInformation_country'] );
	    $shippingCountryCode                   = GF_Fields::get( 'address' )->get_country_code( $country );
	    $country = rgar( $entry, $feed['meta']['billingInformation_country'] );
	    $billingCountryCode                   = GF_Fields::get( 'address' )->get_country_code( $country );

	    //var_dump(rgar( $entry, $feed['meta']['processing_fee_group'] ));
	    //var_dump($feed['meta']['processing_fee_group']);
	    //var_dump($feed['meta']['paymentAmount']);
	    //var_dump($entry);
	    //var_dump($feed);
	    $string = rgar( $entry, $feed['meta']['fee_shipping_amount'] );
	    $shipping_amount = preg_replace('/[^0-9.]/', '', $string);
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
                'success_url' => 'www.google.com',
                'fail_url' => 'www.yahoo.com',
                'notification_url' => 'www.pipwave.com', //$notificationUrl,
            ), 
        );

        /*
        $itemInfo = array();
        foreach ($x as $item) {
            //$product = $item->getProduct();

            // some weird things came out (repetition) if without if else
            //if ((float)$product->getPrice()!=0) {
            if ((float)$item!=0) {
            $itemInfo[] = array(
                'name' => $x,
                'sku' => $x,
                'currency_code' => rgar( $entry, 'currency' ),
                'amount' => $x,
                'quantity' => $x,
                );
            }
        }
        if (count($itemInfo) > 0) {
            $data['item_info'] = $itemInfo;
        }
        */
        return $data;

    }

    function setSignatureParam( $data ) {
        //need modification, call object manager?
        //read some_functions_get_information.php [deskstop]
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
    public function redirect_url( $feed, $submission_data, $form, $entry ) {

        //change payment status to 'processing'
        GFAPI::update_entry_property( $entry['id'], 'payment_status', 'Processing' );

        $settings = $this->get_plugin_settings();
	    //print_r( $entry );

        $data = $this->setData( $entry, $settings, $feed );
        //print_r( $data );
	    var_dump($data);

        $signatureParam = $this->setSignatureParam( $data );
        $pwSignature = $this->generate_pw_signature( $signatureParam );
        
        //after put in pipwave signature, the data is now complete
        $data['signature'] = $pwSignature;

        var_dump($data['signature']);
        var_dump($data);
        //get response to render
        $response = $this->send_request_to_pw( $data, $data['api_key'] );


        //prepare url for render
        $testMode = rgar( $settings, 'test_mode' );
        echo $testMode;
        $someUrl = $this->setUrl( $testMode );

        //this is the form to redirect buyer to 3rd party
	    var_dump($someUrl);
	    var_dump($data['api_key']);
	    var_dump($response);
	    $result = $this->renderSdk( $response, $data['api_key'], $someUrl['RENDER_URL'], $someUrl['LOADING_IMAGE_URL'] );
	    var_dump($result);
	    echo $result;
        //echo $result;
        //print_r( $result );
        //how to display $result???
	    //$return_url = '&return=';
	    $url = $testMode == 'production' ? $this->production_url : $this->sandbox_url;
	    //$url .= "?notify_url={$data['api_override']['notification_url']}&charset=UTF-8&currency_code={$data['currency_code']}&custom={$return_url}";

	    return $url;
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
    public function send_request_to_pw( $data, $pw_api_key ) {
        $agent = "Mozilla/4.0 ( compatible; MSIE 6.0; Windows NT 5.0 )";
        $ch = curl_init();
	    curl_setopt( $ch, CURLOPT_PROXY, 'my-proxy.offgamers.lan:3128' );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'x-api-key:' . $pw_api_key ) );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
        curl_setopt( $ch, CURLOPT_URL, 'https://api.pipwave.com/payment' );
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
    public function renderSdk($response, $api_key, $sdk_url, $loading_img){
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

    //- OTHERS ---------------------------------------------------------------------------------------------------------------------------------------
    
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
                $someUrl = '';
            }
        }
        return $someUrl;
    }
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

	    //coz buyer.id and buyer.email need this
	    $billing_info['field_map'][3]['required'] = true;
	    //coz buyer.country need this
	    $billing_info['field_map'][9]['required'] = true;
	    //var_dump($billing_info['field_map'][3] );

	    $default_settings = parent::replace_field( 'billingInformation', $billing_info, $default_settings );


//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~




	    //print_r($default_settings);
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
    
    //-- our own custom pipwave page ---------------------------------------------------------------------------------------------------------
	public function plugin_page(){

    	$html = <<<EOD
            <h1>Write here</h1>
            <pre>
            To be the most TRUSTED and PREFERRED software development company for EVERYONE to do business online

			We provide merchants SIMPLE , RELIABLE and COST-EFFECTIVE way to sell online
			
			Our SIX Core Values:
			
			    TEAMWORK
			    CREATIVITY
			    <RESPONSIBILITY></RESPONSIBILITY>
			    ACCOUNTABILITY
			    TRUSTWORTHY
			    COMMITMENT
            </pre>
            <p>go dashboard>form>setting to configure setting 1</p>
			<p>go dashboard>form>select form>setting>pipwave to configure setting 2</p>
EOD;
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
	//===========================================================================================
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
	//================================================================================

    public function customer_query_string( $feed, $entry ) {
        $fields = '';
        foreach ( $this->get_customer_fields() as $field ) {
            $field_id = $feed['meta'][ $field['meta_name'] ];
            $value    = rgar( $entry, $field_id );

            if ( $field['name'] == 'country' ) {
                $value = class_exists( 'GF_Field_Address' ) ? GF_Fields::get( 'address' )->get_country_code( $value ) : GFCommon::get_country_code( $value );
            } elseif ( $field['name'] == 'state' ) {
                $value = class_exists( 'GF_Field_Address' ) ? GF_Fields::get( 'address' )->get_us_state_code( $value ) : GFCommon::get_us_state_code( $value );
            }

            if ( ! empty( $value ) ) {
                $fields .= "&{$field['name']}=" . urlencode( $value );
            }
        }

        return $fields;
    }
    //============================================================================================================
    public function save_feed_settings( $feed_id, $form_id, $settings ){
        return parent::save_feed_settings( $feed_id, $form_id, $settings );
    }

    //============================================================================================================
}