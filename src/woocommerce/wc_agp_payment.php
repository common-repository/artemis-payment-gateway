<?php 

add_action('plugins_loaded', 'init_artemis_payment_gateway_class');

function init_artemis_payment_gateway_class(){

    class WC_artemis_payment_gateway extends WC_Payment_Gateway {

		public $token_address;
		public $currency_prefix;
		private $qrcode = '';
		private $token = '';
		
        public function __construct() {
			
            $this->id                 = 'artemis_payment_gateway';
            $this->icon               = apply_filters('woocommerce_custom_gateway_icon', AGP_DIR . 'assets/image/icon.png');
            $this->has_fields         = false;
            $this->method_title       = __( 'Artemis Payment Gateway', 'artemis-payment-gateway' );
            $this->method_description = __( 'Accept payments in the Stellar cryptocurrency or another currency via the Stellar protocol.', 'artemis-payment-gateway' );

            $this->init_form_fields();
			$this->init_settings();

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ), 10, 1 );

            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
			add_action( 'woocommerce_new_order', array($this, 'orderCreated'), 10, 1);
        }
			
		public function init_settings() {
			parent::init_settings();

			$this->title        			= $this->get_option( 'title' );
			$this->description  			= $this->get_option( 'description' );
			$this->memo  					= $this->get_option( 'memo' );
			$this->thankyou_message  		= $this->get_option( 'thankyou_message', 'Thank you. We have got your payment' );
			$this->wait_payment 			= $this->get_option( 'wait_payment' );
			$this->order_status 			= $this->get_option( 'order_status', 'on-hold' );
			$this->stellar_address 			= $this->get_option( 'stellar_address' );
		}
		
        public function init_form_fields() {
			
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'artemis-payment-gateway' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable', 'artemis-payment-gateway' ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', 'artemis-payment-gateway' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'artemis-payment-gateway' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'order_status' => array(
                    'title'       => __( 'Order Status', 'artemis-payment-gateway' ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Choose whether status you wish after checkout.', 'artemis-payment-gateway' ),
                    'default'     => 'wc-completed',
                    'desc_tip'    => true,
                    'options'     => wc_get_order_statuses()
                ),
                'description' => array(
                    'title'       => __( 'Description', 'artemis-payment-gateway' ),
                    'type'        => 'textarea',
                    'default'     => __('Choose Payment', 'artemis-payment-gateway'),
                    'desc_tip'    => true,
                ),
				'memo' => array(
                    'title'       => __( 'Memo Prefix', 'artemis-payment-gateway' ),
                    'type'        => 'text',
                    'description' => __( 'This is prefix for memo, {prefix} + order ID. Leave empty if you want to show the woocommerce order ID only', 'artemis-payment-gateway' ),
                    'desc_tip'    => true,
                ),
				'wait_payment' => array(
                    'title'       => __( 'Wait Payment (minutes)', 'artemis-payment-gateway' ),
                    'type'        => 'number',
                    'description' => __( 'Waiting Payment in minutes, default 1800', 'artemis-payment-gateway' ),
                    'desc_tip'    => true,
                ),
				'thankyou_message' => array(
                    'title'       => __( 'Thank you Message', 'artemis-payment-gateway' ),
                    'type'        => 'text',
                    'default' => __( 'Thank you', 'artemis-payment-gateway' ),
                    'desc_tip'    => true,
                ),
				'stellar_address' => array(
                    'title'   => __( 'Stellar Address', 'artemis-payment-gateway' ),
                    'type'    => 'text',
                    'default' => ''
                ),
            );
        }

        /**
         * Output for the order received page.
         */
		public function orderCreated($order_id) {
			if (empty($this->wait_payment)) $this->wait_payment = 1800;
			
			$wait_payment = '+' . intval($this->wait_payment) . '  minutes';
			$expired_payment = date("Y-m-d H:i:s", strtotime($wait_payment));
			$order_memo = $this->memo . $order_id;
			
			add_post_meta( $order_id, '_expired_payment', $expired_payment );
			add_post_meta( $order_id, 'agp_transaction_memo', $order_memo);
		}
		
        public function thankyou_page($order_id) {
			global $AGP_Tokens;
			
			$agp_tokens = $AGP_Tokens->filled_token();
			
			$order = wc_get_order($order_id);
			
			$order_total = $order->get_meta('agp_choosen_payment_value');
			$order_currency = $order->get_meta('agp_choosen_payment_currency');
			$OrderTransactionHash = $order->get_meta('OrderTransactionHash');
			$expired_payment = $order->get_meta('_expired_payment');
			$order_memo = $order->get_meta('agp_transaction_memo');
			
			$total_agp = $order_total . ' ' . $order_currency;
			
			$token = $this->stellar_address;
			if ($order_currency == 'XLM') {
				$to_agp = 'web+stellar:pay?destination=' . $this->stellar_address . '&amount=' . $order_total . '&asset_code=' . $order_currency;
			}
			else {
				$issuer_address = $agp_tokens[$order_currency]['issuer_address'];
				$to_agp = 'web+stellar:pay?destination=' . $this->stellar_address . '&amount=' . $order_total . '&asset_code=' . $order_currency . '&asset_issuer=' . $issuer_address;
			}
			
			$this->agp_complete_your_order($order_memo, $total_agp, $token, $to_agp, $OrderTransactionHash);
			
			$this->agp_countdown_timer($expired_payment, $OrderTransactionHash);
			$this->agp_load_check_payment($order_id, $OrderTransactionHash);
        }
		
		public function get_regular_sale_price($id, $item_key) {
			$return = '';
			$regular_price = get_post_meta($id, $item_key, true);
			if (metadata_exists('post', $id, $item_key. '_sale_price')) {
				$sale_price = get_post_meta($id, $item_key. '_sale_price', true);
				if (!empty($sale_price)) $return = $sale_price;
				else {
					$return = $regular_price;
				}
			}
			else {
				$return = $regular_price;
			}
			
			return $return;
		}
		
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
            if ( $this->instructions && ! $sent_to_admin && 'artemis_payment_gateway' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
                echo wpautop( wptexturize( esc_html($this->instructions) ) ) . PHP_EOL;
            }
        }
		
		public function agp_cart() {
			global $AGP_Tokens;
			
			$total_array = array();
			$t = 0;
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$quantity = 1;
					
				$p_id = $cart_item['product_id'];
				foreach($AGP_Tokens->filled_token() as $key => $item) {
					$agp_price = 0;
					if(isset($cart_item['variation']) && count($cart_item['variation']) > 0 ){
						$current_product = new  WC_Product_Variable($p_id);					
						$variations = $current_product->get_available_variations();
						foreach($variations as $index => $data){
							if ($data['variation_id'] == $cart_item['variation_id'])
								$agp_price = $data['_variation' . $item['meta_key']];
						}
					}
					else {
						$agp_price = $this->get_regular_sale_price($p_id, $item['meta_key']);
					}
					
					if (empty($agp_price)) continue;
					
					if (agp_check_applied_coupon()) {
						$coupon_total = agp_check_applied_coupon();
						$total = floatval($agp_price) * $cart_item['quantity'];
						$total = $total * ($coupon_total / 100);
					}
					else {
						$total = floatval($agp_price) * $cart_item['quantity'];
					}
					
					$total_array[$key][$t] = $total;
				
					$t++;
					
				}
			}
			
			return $total_array;
			
		}

        public function payment_fields(){
			global $AGP_Tokens;
			
			$agp_payment_selection = '';
            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( esc_html($description) ) );
            }
			$total_array = $this->agp_cart();
			
			$agp_payment_selection .= '<div class="agp-option"><select name="agp_choosen_payment">';
			
			if (!empty($total_array)) {
				foreach($total_array as $key => $val) {
					if (count($val) !== count(WC()->cart->get_cart())) continue;
					$checked = '';
					
					$total = array_sum($val);
					
					if (empty($total)) continue;
					$agp_payment_selection .= '<option value="'.esc_attr($key).'|'.esc_attr($total).'"/>' . esc_attr($key) . ' ' . esc_attr($total) . ' - <span class="domain-name">'.esc_attr($AGP_Tokens->get_domain_name($key)).'</span></option>';
						
				}
			}
			
			$agp_payment_selection .= '</select></div>';
            ?>
            <div id="custom_input">
                <p class="form-row form-row-wide">
					<div class="agp-options">
						<?php 
						$allowed_html = array(
							'select' => array(
								'id'   => array(),
								'class' => array(),
								'name' => array(),
							),
							 'option' => array(
								'value'  => array()
							 ),
						);
						echo wp_kses($agp_payment_selection, $allowed_html); ?>
					</div>
                </p>
            </div>
            <?php
        }
		
        public function process_payment( $order_id ) {

            $order = wc_get_order( $order_id );

            $status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;

            $order->update_status( $status, __( 'Checkout with Artemis Payment Gateway. ', 'artemis-payment-gateway' ) );
			
            $order->reduce_order_stock();

            WC()->cart->empty_cart();

            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );
        }
		
		public function agp_complete_your_order($memo, $value, $to, $to_agp, $OrderTransactionHash) {
			global $AGP_Qrcode;
			
			if (isset($_GET['success']) || !empty($OrderTransactionHash)) {
				echo '<div class="agp-thankyou-success agp-sec-suc"><h2>' . esc_html($this->thankyou_message) . '</h2></div>';
			}
			else { ?>
				<div class="agp-thank-you" id="agp-thank-you">
					<div class="agp-title"><?php _e('To complete your order', 'artemis-payment-gateway'); ?></div>
					<div class="agp-payment-wrapper">
						<div class="agp-payment agp-ty-sec">
							<div class="agp-form-wrapper">
								<div class="agp-form">
									<div class="agp-field-wrapper">
										<div class="agp-field">
											<label>
												<?php _e('SEND', 'artemis-payment-gateway'); ?>
												<span><a class="agp_copy_text" onclick="agp_copy_text('agp_copy_text_val')"><?php _e('copy', 'artemis-payment-gateway'); ?></a></span>
											</label>
											<input type="text" id="agp_copy_text_val" value="<?php echo esc_attr($value); ?>" readonly="readonly" />
										</div>
										<div class="agp-field">	
											<label>
												<?php _e('Memo', 'artemis-payment-gateway'); ?>
												<span><a class="agp_copy_text" onclick="agp_copy_text('agp_val_memo')"><?php _e('copy', 'artemis-payment-gateway'); ?></a></span>
											</label>
											<input type="text" id="agp_val_memo" value="<?php echo esc_attr($memo); ?>" readonly="readonly" />
										</div>
									</div>
								</div>
								<div class="agp-form">
									<label>
										<?php _e('TO', 'artemis-payment-gateway'); ?>
										<span><a class="agp_copy_text" onclick="agp_copy_text('agp_val_address')"><?php _e('copy', 'artemis-payment-gateway'); ?></a></span>
									</label>
									<input type="text" id="agp_val_address" value="<?php echo esc_attr($to); ?>" readonly="readonly" />
								</div>
							</div>
						</div>
						<div class="agp-qr-counter-token agp-ty-sec">
							<div class="agp-qr-counter agp-ty-sec2">
								<div class="agp-qrcode">
									<h5><?php _e('SCAN QR CODE BELOW', 'artemis-payment-gateway'); ?></h5>
									<?php echo esc_html($AGP_Qrcode->generateQR($to_agp)); ?>
								</div>
								<div class="agp-countdown-timer">
									<div class="timer">
										<p id="agp-countdown"></p>
										<p class="agp-waiting-payment">
											<span><?php _e('Awaiting payment', 'artemis-payment-gateway'); ?></span>
											<span><?php _e('(checked every 15 secs)', 'artemis-payment-gateway'); ?></span>
										</p>
									</div>
								</div>
							</div>
							<div class="agp-open-token">
								<div class="agp-open-token-img">
									<a href="<?php echo esc_url($to_agp); ?>"><img src="<?php echo esc_url(AGP_DIR . '/assets/image/open-token.svg'); ?>" class="agp-open-token-img" /></a>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php
			}
		}
		
		public function agp_countdown_timer($expired_payment, $OrderTransactionHash) {
			if (!isset($_GET['success']) && empty($OrderTransactionHash)) { 
			?>
			<script>
				var countDownDate = new Date("<?php echo esc_attr(date('Y/m/d H:i:s', strtotime($expired_payment))); ?>");var x = setInterval(function() {var now = new Date().getTime();var distance = countDownDate - now;var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));var seconds = Math.floor((distance % (1000 * 60)) / 1000);if (hours < 10) hours = "0" + hours;if (minutes < 10) minutes = "0" + minutes;if (seconds < 10) seconds = "0" + seconds;document.getElementById("agp-countdown").innerHTML = hours + " : " + minutes + " : " + seconds;if (distance < 0) {clearInterval(x);document.getElementById("agp-countdown").innerHTML = "EXPIRED";}}, 1000);
			</script>
			<?php 
			}
		}
		
		public function agp_load_check_payment($order_id, $OrderTransactionHash) { 
			global $AGP_Tokens;
			
			$agp_tokens = $AGP_Tokens->filled_token();
			
			$order = wc_get_order($order_id);
		
			$memo = $order->get_meta('agp_transaction_memo');
			$issuer = '';
			$order_total = $order->get_meta('agp_choosen_payment_value');
			$order_currency = $order->get_meta('agp_choosen_payment_currency');
			$expired_payment = $order->get_meta('_expired_payment');
			
			$token = $this->stellar_address;
			$issuer = '';
			if ($order_currency != 'XLM') $issuer = $agp_tokens[$order_currency]['issuer_address'];
			?>
			<script>
				// function
				function load_check_payment(){jQuery.ajax({url:agp_ajax_script.ajax_url,type:"POST",data:{action:"check_payment",agp_nonce:agp_ajax_script.nonce,order_id:"<?php echo intval($order_id); ?>",token:"<?php echo esc_attr($token); ?>",issuer:"<?php echo esc_attr($issuer); ?>",memo:"<?php echo esc_attr($memo); ?>",order_total:"<?php echo floatval($order_total); ?>",order_currency:"<?php echo esc_attr($order_currency); ?>",expired_payment:"<?php echo esc_attr($expired_payment); ?>"},success:function(e){if("success"===e){var o=document.location.href+"&success=true";document.location=o}else console.log(e)},error:function(){console.log("call error")}})}

				<?php 
				if (!isset($_GET['success']) && empty($OrderTransactionHash)) { ?>
					setInterval(function(){load_check_payment()},10000);
				<?php } ?>
			</script>
			<?php
		}
    }
}

// add_action( 'woocommerce_check_cart_items', 'agp_check_applied_coupon' );
function agp_check_applied_coupon() {
    $applied_coupons = WC()->cart->get_applied_coupons();
    $coupon_applied  = false;

    if( sizeof($applied_coupons) > 0 ) {
        foreach( $applied_coupons as $coupon_code ) {
            $coupon = new WC_Coupon( $coupon_code );
			if ($coupon->get_discount_type() != 'percent') continue;
			
			return $coupon->get_amount();
        }
    }
	
	return false;
}

add_filter( 'woocommerce_payment_gateways', 'AGP_gateway_class' );
function AGP_gateway_class( $methods ) {
    $methods[] = 'WC_artemis_payment_gateway'; 
    return $methods;
}

add_action('woocommerce_checkout_process', 'AGP_process_custom_payment');
function AGP_process_custom_payment(){
    if($_POST['payment_method'] != 'artemis_payment_gateway')
        return;

    if( !isset($_POST['agp_choosen_payment']) || empty($_POST['agp_choosen_payment']) )
        wc_add_notice( __( 'Please choose the payment method', 'artemis-payment-gateway' ), 'error' );
}

add_action( 'woocommerce_checkout_update_order_meta', 'AGP_payment_update_order_meta' );
function AGP_payment_update_order_meta( $order_id ) {

    if($_POST['payment_method'] != 'artemis_payment_gateway')
        return;
	
	$post_agp_choosen_payment = sanitize_text_field($_POST['agp_choosen_payment']);
	$agp_choosen_payment = explode('|', $post_agp_choosen_payment);
	
    update_post_meta( $order_id, 'agp_choosen_payment', intval($post_agp_choosen_payment) );
    update_post_meta( $order_id, 'agp_choosen_payment_value', esc_attr($agp_choosen_payment[1]) );
    update_post_meta( $order_id, 'agp_choosen_payment_currency', esc_attr($agp_choosen_payment[0]) );
}

add_action( 'woocommerce_admin_order_data_after_billing_address', 'AGP_checkout_field_display_admin_order_meta', 10, 1 );
function AGP_checkout_field_display_admin_order_meta($order){
    $method = get_post_meta( $order->id, '_payment_method', true );
    if($method != 'artemis_payment_gateway')
        return;

    $agp_choosen_payment_value = get_post_meta( $order->id, 'agp_choosen_payment_value', true );
    $agp_choosen_payment_currency = get_post_meta( $order->id, 'agp_choosen_payment_currency', true );
	
	$allowed_html = array(
		'p' => array(),
		'strong' => array()
	);
	
    echo wp_kses('<p><strong>Choosen Payment :</strong> ' . $agp_choosen_payment_currency . ' ' . $agp_choosen_payment_value . '</p>', $allowed_html);
}