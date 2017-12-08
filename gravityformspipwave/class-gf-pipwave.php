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
    public function init() {
        parent::init();
        add_filter( 'gform_submit_button', array( $this, 'form_submit_button' ), 10, 2 );
        add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );
    }

    //-PIPWAVE SCRIPT?----------------------------------------------------------------------

    //generate signature
    function generate_pw_signature( $signatureParam ) {
        ksort( $signatureParam );
        $signature = "";
        foreach ( $signatureParam as $key => $value ) {
            $signature .= $key . ':' . $value;
        }
        return sha1( $signature );
    }

    //fire to pipwave
    function send_request_to_pw( $data, $pw_api_key ) {
        $agent = "Mozilla/4.0 ( compatible; MSIE 6.0; Windows NT 5.0 )";
        $ch = curl_init();
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
            //third row
            array( 
                'name'              => 'processing_fee_group',
                'label'             => esc_html__( 'Processing Fee Group', 'translator' ),
                'type'              => 'text',
                'class'             => 'medium',
                'tooltip'           => '<h6>' . esc_html__( 'Processing Fee Group', 'translator' ) . '</h6>' . sprintf( esc_html__( 'Payment Processing Fee Group can be configured %shere%s. Please fill referenceId in the blank.( if available )', 'translator' ), '<a href="https://merchant.pipwave.com/account/set-processing-fee-group#general-processing-fee-group" target="_blank">', '</a>' ),
            ),
         );

        $default_settings = parent::add_field_after( 'feedName', $fields, $default_settings );
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
            'complete_payment'          => esc_html__( 'Payment Completed', 'gravityformsstripe' ),
            'refund_payment'            => esc_html__( 'Payment Refunded', 'gravityformsstripe' ),
            'fail_payment'              => esc_html__( 'Payment Failed', 'gravityformsstripe' ),
        );
    }
    
    
    
    
    
    
    
    
    
    
}