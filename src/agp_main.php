<?php 

require_once dirname(__FILE__). '/agp_payments.php';
require_once dirname(__FILE__). '/agp_tokens.php';
require_once dirname(__FILE__). '/agp_qrcode.php';
require_once dirname(__FILE__). '/woocommerce/wc_agp_payment.php';
require_once dirname(__FILE__). '/woocommerce/wc_agp_main.php';
 
Class AGP_Main {
	
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array($this, 'admin_script_load') );
		add_action( 'wp_enqueue_scripts', array($this, 'script_load') );
		add_action( 'wp_ajax_check_payment', array($this, 'check_payment'));
		add_action( 'wp_ajax_nopriv_check_payment', array($this, 'check_payment'));
	}
	
	public function admin_script_load(){
		wp_enqueue_script( 'agp-script', AGP_DIR . 'assets/js/agp-admin.js','','',true);
		wp_enqueue_style( 'agp-script', AGP_DIR . 'assets/css/agp_admin.css' );
	}
	
	public function script_load(){
		wp_enqueue_style( 'agp-style', AGP_DIR . 'assets/css/agp.css' );
		wp_enqueue_script( 'agp-script', AGP_DIR . 'assets/js/agp.js','','',true );
		wp_localize_script( 'agp-script', 'agp_ajax_script', array( 
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce('agp-nonce')
			) 
		);
	}
	
	public function check_payment() {
		if ( ! wp_verify_nonce( $_POST['agp_nonce'], 'agp-nonce' ) ) {
			die ();
		}
		
		global $AGP_Payments;
	 
		if (!isset($_POST['order_id']) || empty($_POST['order_id'])) return;
		if (!isset($_POST['token']) || empty($_POST['token'])) return;
		if (!isset($_POST['memo']) || empty($_POST['memo'])) return;
		if (!isset($_POST['order_total']) || empty($_POST['order_total'])) return;
		if (!isset($_POST['order_currency']) || empty($_POST['order_currency'])) return;
		if (!isset($_POST['expired_payment']) || empty($_POST['expired_payment'])) return;
		
		$order_id 			= intval($_POST['order_id']);
		$token 				= sanitize_text_field($_POST['token']);
		$issuer 			= sanitize_text_field($_POST['issuer']);
		$memo 				= sanitize_text_field($_POST['memo']);
		$order_total 		= floatval($_POST['order_total']);
		$order_currency 	= sanitize_text_field($_POST['order_currency']);
		$expired_payment 	= sanitize_text_field($_POST['expired_payment']);
		
		$order = wc_get_order($order_id);
		
		$the_payment_hash = $AGP_Payments->CheckPayment($token, $issuer, $memo, $order_total, $order_currency, $expired_payment);
		if (!empty($the_payment_hash)) {
			update_post_meta( $order_id, 'OrderTransactionHash', $the_payment_hash );
			
			$order->update_status( 'completed' );
			echo 'success';
		}
		else echo 'not found';
		
		wp_die();
	}
}

global $AGP;
$AGP = new AGP_Main();