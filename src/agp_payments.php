<?php 
require_once dirname(dirname(__FILE__)). '/vendor/autoload.php';

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\ManageDataOperationBuilder;
use Soneso\StellarSDK\ManageSellOfferOperationBuilder;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PathPaymentStrictReceiveOperationBuilder;
use Soneso\StellarSDK\PathPaymentStrictSendOperationBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Operations\CreateAccountOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;

Class AGP_Payments {
	
	function getAccountDetails($accountID) {
		if (empty($accountID)) return;
		
		$sdk = StellarSDK::getPublicNetInstance();
		try {
			$account = $sdk->requestAccount($accountID);
			
			$wallet_balance = array();
			foreach ($account->getBalances() as $balance) {
				switch ($balance->getAssetType()) {
					case Asset::TYPE_NATIVE:
						$wallet_balance['wallet']['XLM'] = $balance->getBalance();
					break;
					default:	
						$wallet_balance['wallet'][$balance->getAssetCode()] = $balance->getBalance();
				}
			}
			
			$wallet_balance['sequence_number'] = $account->getSequenceNumber();
			 
			$i=0;
			foreach ($account->getSigners() as $signer) {
				$wallet_balance['signer'][$i] = $signer->getKey();
				
				$i++;
			}
			
			return $wallet_balance;
		} catch (\GuzzleHttp\Exception\ConnectException $e) {
			header("Refresh:0");
		}
	}
	
	function CheckPayment($accountID, $issuer = '', $memo, $amount, $code, $date) {
		$sdk = StellarSDK::getPublicNetInstance();
		
		$operationsPage = $sdk->payments()->forAccount($accountID)->limit(30)->order("desc")->cursor("now")->execute();
		
		foreach ($operationsPage->getOperations() as $payment) {
			if (empty($issuer)) {
				if ($payment->getAsset()->getType() != 'native') continue;
				
				if (
					$payment->getAmount() == $amount && 
					(date('Y-m-d H:i:s') < date('Y-m-d H:i:s', strtotime($date)))
				) {
					$payment_data = $this->get_api($payment->getLinks()->getTransaction()->getHref());
					if ($payment_data['memo'] == $memo) {
						return $payment_data['hash'];
					}
				}
				
			}
			else {
				if ($payment->getAsset()->getType() == 'native') continue;
				
				if (
					$payment->getAmount() == $amount && 
					$payment->getAsset()->getCode() == $code &&
					(date('Y-m-d H:i:s') < date('Y-m-d H:i:s', strtotime($date)))
				) {
					$payment_data = $this->get_api($payment->getLinks()->getTransaction()->getHref());
					if ($payment_data['memo'] == $memo) {
						return $payment_data['hash'];
					}
				}
			}
		}
		
		return;
	}
	
	function get_api($endpoint_api) {
		
		$response = wp_remote_get(esc_url($endpoint_api), array(
			'headers' => array(
				'Accept: application/json',
				'Content-Type: application/json',
			),
		));
		
		$result = json_decode(wp_remote_retrieve_body($response), true);
		
		return $result;
	}
}

global $AGP_Payments;
$AGP_Payments = new AGP_Payments();