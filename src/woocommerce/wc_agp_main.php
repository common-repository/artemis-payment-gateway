<?php 

Class WC_AGP_Main {
	
	public function __construct() {
		add_filter( 'woocommerce_currencies', array($this, 'custom_currency') );
		add_filter( 'woocommerce_currency_symbol', array($this, 'custom_currency_symbol'), 10, 2);
		add_filter( 'woocommerce_get_price_html', array($this, 'price_html'), 100, 2 );
		add_filter( 'woocommerce_variable_price_html', array($this, 'product_variation_price_html'), 100, 2 );
		add_action( 'woocommerce_product_options_general_product_data', array($this, 'product_data_fields') );
		add_action( 'woocommerce_new_product', array($this, 'sync_on_product_save'), 10, 3);
		add_action( 'woocommerce_update_product', array($this, 'sync_on_product_save'), 10, 3);
		add_action( 'woocommerce_variation_options_pricing', array($this, 'add_artg_price_field_to_variations'), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array($this, 'save_artg_price_field_variations'), 10, 2 );
		add_filter( 'woocommerce_available_variation', array($this, 'add_artg_price_field_variation_data') );
		add_filter( 'woocommerce_variation_option_name', array($this, 'display_price_in_variation_option_name') );
	}
	
	public function filled_token() {
		global $AGP_Tokens;
		
		return $AGP_Tokens->filled_token();
	}
	
	public function custom_currency( $currencies ) {
		$currencies['XLM'] = __( 'STELLAR', 'artemis-payment-gateway' );
		return $currencies;
	}

	public function custom_currency_symbol( $currency_symbol, $currency ) {
		switch( $currency ) {
			case 'XLM': $currency_symbol = 'XLM'; break;
		}
		
		return $currency_symbol;
	}
	
	public function clear_text($text) {
		global $AGP_Tokens;
		
		return $AGP_Tokens->clear_text('text');
	}
	
	public function regular_sale_price($id, $item_key) {
		$return = '';
		$regular_price = get_post_meta($id, $item_key, true);
		if (metadata_exists('post', $id, $item_key. '_sale_price')) {
			$sale_price = get_post_meta($id, $item_key. '_sale_price', true);
			if (!empty($sale_price)) {
				$sale_price_from = get_post_meta($id, $item_key. '_sale_price_dates_from', true);
				$sale_price_to = get_post_meta($id, $item_key. '_sale_price_dates_to', true);
				if (!empty($sale_price_from) || !empty($sale_price_to)) {
					$date_now = date("Y-m-d");
					if (!empty($sale_price_from)) {
						if ($date_now >= $sale_price_from)
							$return = '<del>'.$regular_price.'</del> ' . $sale_price;
						else if (!empty($sale_price_to)) {
							if ($date_now <= $sale_price_to)
								$return = '<del>'.$regular_price.'</del> ' . $sale_price;
							else 
								$return = $regular_price;
						}
					}
					else if (!empty($sale_price_to)) {
						if ($date_now <= $sale_price_to)
							$return = '<del>'.$regular_price.'</del> ' . $sale_price;
						else 
							$return = $regular_price;
					}
					
				}
				else 
					$return = '<del>'.$regular_price.'</del> ' . $sale_price;
			}
			else {
				$return = $regular_price;
			}
		}
		else {
			$return = $regular_price;
		}
		
		return $return;
	}
	
	public function price_html( $price, $product ) {
		$price_text = '';
		if ($product->get_type() != 'variation' && $product->get_type() != 'variable'):
			$price_text .= '<div class="agp-price-html">';
		
			$regular_sale_price = $product->get_regular_price();
			if( $product->is_on_sale() ) {
				$regular_sale_price = '<del>'.$product->get_regular_price().'</del> ' . $product->get_sale_price();
			}
			$price_text .= '<div class="main-currency">' . get_woocommerce_currency_symbol() . ' ' . $regular_sale_price . '</div>';
			$price_text .= '<ul class="price-html">';
			
			foreach($this->filled_token() as $key => $item) {
				$meta_key = $this->regular_sale_price($product->get_id(), $item['meta_key']);
				if (!empty($meta_key)) $price_text .= '<li><span class="token">' . $key . '</span><span class="token-value">' . $meta_key . '</span></li>';
			}
			
			$price_text .= '</ul></div>';
		endif;
		
		return $price_text;
	}
	
	function display_price_in_variation_option_name( $term ) {
		global $wpdb, $product;

		if ( empty( $term ) ) return $term;
		if ( empty( $product->id ) ) return $term;

		$id = $product->get_id();

		$result = $wpdb->get_col( "SELECT slug FROM {$wpdb->prefix}terms WHERE name = '$term'" );
		$term_slug = ( !empty( $result ) ) ? $result[0] : $term;

		$query = "SELECT postmeta.post_id AS product_id
                FROM {$wpdb->prefix}postmeta AS postmeta
                    LEFT JOIN {$wpdb->prefix}posts AS products ON ( products.ID = postmeta.post_id )
                WHERE postmeta.meta_key LIKE 'attribute_%'
                    AND postmeta.meta_value = '$term_slug'
                    AND products.post_parent = $id";

		$variation_id = $wpdb->get_col( $query );

		$parent = wp_get_post_parent_id( $variation_id[0] );

		if ( $parent > 0 ) {
			$_product = new WC_Product_Variation( $variation_id[0] );
			return $term . ' (' . wp_kses( woocommerce_price( $_product->get_price() ), array() ) . ')';
		}
		
		return $term;

	}

	public function product_variation_price_html( $price, $product ){	
		$agp_price_html_variation = '';
		
		$variation_price_array = array();
		$i = 0;
		$sale_price     =  $product->get_variation_sale_price( 'min', true );
        $regular_price  =  $product->get_variation_regular_price( 'max', true );
		
		foreach($product->get_available_variations() as $variation){
			foreach($this->filled_token() as $key => $item) {
				$token_id = '_variation' . $item['meta_key'];	
				if (!empty($variation[$token_id])) {
					$variation_price_array[$key][$i] = $variation[$token_id];
				}
			}
			$i++;
		}
		
		$agp_price_html_variation .= '<div class="agp-price-html-variation">';
		$agp_price_html_variation .= '<div class="main-currency">' . get_woocommerce_currency_symbol() . ' ' . $sale_price . ' - ' . $regular_price . '</div>';
		$agp_price_html_variation .= '<ul class="price-html">';
		
		foreach($variation_price_array as $key => $item) {
			$min_value = min($item);
			$max_value = max($item);
			$agp_price_html_variation .= '<li><span class="token">' . $key . '</span> <span class="token-value">' . $min_value . ' - ' . $max_value . '</span></li>';
		}
		
		$agp_price_html_variation .= '</ul>';
		$agp_price_html_variation .= '</div>';
		
		$allowed = array(
			'div' => array(
				'class' => array()
			), 
			'ul' => array(
				'class' => array()
			), 
			'li' => array(),
			'span' => array(
				'class' => array()
			)
		);
		echo wp_kses($agp_price_html_variation, $allowed);
	}
	
	public function product_data_fields() {
		global $wpdb; 
		
		$product_id = (isset($_GET['post']) && !empty($_GET['post'])) ? intval($_GET['post']) : 0;
		?>
		<div class="product_custom_field artg_price_product_data">
			<?php
			foreach($this->filled_token() as $key => $item) {
				?>
				
				<div class="options_group pricing show_if_simple show_if_external hidden">
				<?php 
					$schedule_sale_date_from = '';
					$schedule_sale_date_to = '';
					$the_class = '';
					if (!empty($product_id)) {
						$schedule_sale_date_from = get_post_meta( $product_id, $item['meta_key'] . '_sale_price_dates_from', true );
						$schedule_sale_date_to = get_post_meta( $product_id, $item['meta_key'] . '_sale_price_dates_to', true );
						
						if (!empty($schedule_sale_date_from) || !empty($schedule_sale_date_to)) $the_class = "hidden";
					}
					woocommerce_wp_text_input( array( 
						'id'            => $item['meta_key'], 
						'wrapper_class' => '_artg_currency', 
						'label'         => $key . ' Regular Price',
						'default'       => '',
					) );
					woocommerce_wp_text_input( array( 
						'id'            => $item['meta_key'] .'_sale_price', 
						'wrapper_class' => '_artg_currency_sale_price', 
						'label'         => $key . ' Sale Price',
						'description'	=> '<a href="#" data-id="'.$item['meta_key'] .'_sale_price_dates_fields" class="'.$item['meta_key'].'_schedule artg_schedule_price_show '.$the_class.'">Schedule</a>',
						'default'       => '',
					) );
				?>
					<p class="form-field <?php echo esc_attr($item['meta_key']); ?>_sale_price_dates_fields artg_schedule_price_wrapper <?php echo (empty($schedule_sale_date_from) && empty($schedule_sale_date_to)) ? 'hidden' : ''; ?>" style="">
						<label for="_<?php echo esc_attr($item['meta_key']); ?>_sale_price_dates_from"><?php echo str_replace('_currency_', '', esc_attr($item['meta_key'])); ?> Sale price dates</label>
						
						<input type="text" class="short artg_schedule_price_input_from artgdatepicker" name="<?php echo esc_attr($item['meta_key']); ?>_sale_price_dates_from" id="<?php echo esc_attr($item['meta_key']); ?>_sale_price_dates_from" value="<?php echo esc_attr($schedule_sale_date_from); ?>" placeholder="From… YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])">
						<input type="text" class="short artg_schedule_price_input_to artgdatepicker" name="<?php echo esc_attr($item['meta_key']); ?>_sale_price_dates_to" id="<?php echo $item['meta_key']; ?>_sale_price_dates_to" value="<?php echo esc_attr($schedule_sale_date_to); ?>" placeholder="To…  YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])">
						<a href="#" data-id="<?php echo esc_attr($item['meta_key']); ?>_sale_price_dates_fields" class="artg_schedule_price_cancel description cancel_<?php echo esc_attr($item['meta_key']); ?>_sale_schedule">Cancel</a><span class="woocommerce-help-tip"></span>
					</p>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}
	
	public function add_artg_price_field_to_variations( $loop, $variation_data, $variation ) {
		$x = 0;
		?>
		<div class="agp_variation_pricing">
		<?php
		foreach($this->filled_token() as $key => $item) {
			$token_id = '_variation' . $item['meta_key'];
			
			if ($x == 0) $agp_class = 'form-row form-row-first ' . $token_id;
			else $agp_class = 'form-row form-row-last';
			
			woocommerce_wp_text_input( array( 
				'id'            => $token_id . '[' . $loop . ']', 
				'wrapper_class' => '_artg_currency ' . $agp_class, 
				'label'         => $key,
				'value' 		=> get_post_meta( $variation->ID, $token_id, true ),
				'default'       => '',
			) );
			
			$x = 1-$x;
		}
		?>
		</div>
		<?php
	}
 
	public function save_artg_price_field_variations( $variation_id, $i ) {
		
		foreach($this->filled_token() as $key => $item) {
			$meta_key = $item['meta_key'];
			
			$token_id = '_variation' . $meta_key;
			
			$currency_value = intval($_POST[$token_id][$i]);
			
			if ( !empty( $currency_value ) ) {
				if(metadata_exists('post', $variation_id, $token_id))
					update_post_meta( $variation_id, $token_id, $currency_value );
				else
					add_post_meta($variation_id, $token_id, $currency_value);
			}
				
		}
	}
 
	public function add_artg_price_field_variation_data( $variations ) {
		foreach($this->filled_token() as $key => $item) {
			$token_id = '_variation' . $item['meta_key'];
			
			$variations[$token_id] = get_post_meta( $variations['variation_id'], esc_attr($token_id), true );
		}
		
		return $variations;
	}
	
	public function sync_on_product_save( $product_id ) {
		foreach($this->filled_token() as $key => $item) {
			
			$post_meta_key_sale_price_dates_from = '';
			$post_meta_key_sale_price_dates_to = '';
			$post_meta_key_sale_price = '';
			$post_meta_key = '';
			
			if (!empty($_POST[$item['meta_key']]))
				$post_meta_key = intval($_POST[$item['meta_key']]);
			
			if (!empty($_POST[$item['meta_key'] . '_sale_price'])) 
				$post_meta_key_sale_price = intval($_POST[$item['meta_key'] . '_sale_price']);
			
			if (!empty($_POST[$item['meta_key'] . '_sale_price_dates_from'])) 
				$post_meta_key_sale_price_dates_from = sanitize_text_field($_POST[$item['meta_key'] . '_sale_price_dates_from']);
			
			if (!empty($_POST[$item['meta_key'] . '_sale_price_dates_to'])) 
				$post_meta_key_sale_price_dates_to = sanitize_text_field($_POST[$item['meta_key'] . '_sale_price_dates_to']);
			
			$meta_key = $item['meta_key'];
			$meta_key_sale_price = $meta_key . '_sale_price';
			$meta_key_sale_price_dates_from = $meta_key . '_sale_price_dates_from';
			$meta_key_sale_price_dates_to = $meta_key . '_sale_price_dates_to';
			
			
			if(metadata_exists('post', $product_id, $meta_key)) {
				update_post_meta($product_id, $meta_key, $post_meta_key);
				update_post_meta($product_id, $meta_key_sale_price, $post_meta_key_sale_price);
				update_post_meta($product_id, $meta_key_sale_price_dates_from, $post_meta_key_sale_price_dates_from);
				update_post_meta($product_id, $meta_key_sale_price_dates_to, $post_meta_key_sale_price_dates_to);
			}
			else {
				add_post_meta($product_id, $meta_key, $post_meta_key);
				add_post_meta($product_id, $meta_key_sale_price, $post_meta_key_sale_price);
				update_post_meta($product_id, $meta_key_sale_price_dates_from, $post_meta_key_sale_price_dates_from);
				update_post_meta($product_id, $meta_key_sale_price_dates_to, $post_meta_key_sale_price_dates_to);
			}
		}
		
	}
	
}

new WC_AGP_Main();