<?php 

Class AGP_Tokens {
	
	public $token_address;
	public $currency_prefix;
	
	public function __construct() {
		$this->currency_prefix = '_currency_';
		
		$this->token_address = get_option('woocommerce_agp_settings');
	}
	
	public function filled_token() {
		$new_tokens = array();
		
		foreach($this->get_tokens() as $key => $token) {
			$key = $this->clear_text($key);
			$new_tokens[$key]['meta_key'] = $this->currency_prefix . $key;
			$new_tokens[$key]['domain_name'] = $token['domain_name'];
			$new_tokens[$key]['issuer_address'] = $token['issuer_address'];
		}
		
		return $new_tokens;
	}
	
	public function get_issuer_address($key) {
		$tokens = $this->get_tokens();
		
		return $tokens[$key]['issuer_address'];
	}
	
	public function get_domain_name($key) {
		$tokens = $this->get_tokens();
		
		return $tokens[$key]['domain_name'];
	}
	
	public function clear_text($text) {
		return str_replace(' ', '', $text);
	}
	
	public function get_tokens() {
		
		if (!$this->option_exists('agp_token_api') && !$this->option_exists('agp_token_api_date')) {
			$get_tokens_api = $this->get_tokens_api();
			
			add_option('agp_token_api', $get_tokens_api);
			add_option('agp_token_api_date', date('Y-m-d H:i:s', strtotime('+6 hours')));
			
			return $get_tokens_api;
		} else {
			$agp_token_api_date = strtotime(get_option('agp_token_api_date'));
			if (time() >= $agp_token_api_date) {
				$get_tokens_api = $this->get_tokens_api();
				
				update_option('agp_token_api', $get_tokens_api);
				
				update_option('agp_token_api_date', date('Y-m-d H:i:s', strtotime('+6 hours')));
			}
		}
		
		return get_option('agp_token_api');
	}
	
	public function get_tokens_api() {
		$xlm_array = array();
		$xlm_array['XLM'] = array('issuer_address' => 'XLM', 'domain_name' => 'stellar.org');
		$endpoint_api = 'https://artemis-gateway.com/wp-json/artgateway/v1/get_tokens';
		
		$response = wp_remote_get(esc_url_raw($endpoint_api), array(
			'headers' => array(
				'Accept: application/json',
				'Content-Type: application/json',
			),
		));
		
		$result = json_decode(wp_remote_retrieve_body($response), true);
		
		$return = array_merge($xlm_array, $result);
		
		return $return;
		
	}
	
	public function option_exists($name){
		global $wpdb; 
		
		return $wpdb->query($wpdb->prepare("SELECT * FROM ". $wpdb->prefix. "options WHERE option_name = %s LIMIT 1", $name));
	}

}

global $AGP_Tokens;
$AGP_Tokens = new AGP_Tokens();
