<?php
/**
 * Plugin Name: Pay P2P QIWI
 * Description: Pay P2P QIWI
 * Version:     0.1.0
 * Author:      Elberos team <support@elberos.org>
 * License:     Apache License 2.0
 *
 *  (c) Copyright 2019-2021 "Ildar Bikmamatov" <support@elberos.org>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

if ( !class_exists( 'PAY_QIWI_P2P_Plugin' ) ) 
{

class PAY_QIWI_P2P_Plugin
{
	
	/**
	 * Init Plugin
	 */
	public static function init()
	{
		add_action
		(
			'admin_init', 
			function()
			{
				require_once __DIR__ . "/include/admin-settings.php";
				require_once __DIR__ . "/include/admin-transactions.php";
			}
		);
		add_action('admin_menu', 'PAY_QIWI_P2P_Plugin::register_admin_menu');
		
		/* Remove plugin updates */
		add_filter( 'site_transient_update_plugins', 'PAY_QIWI_P2P_Plugin::filter_plugin_updates' );
		
		/* Add qiwi entry point */
		add_action('elberos_register_routes', 'PAY_QIWI_P2P_Plugin::register_routes');
	}
	
	
	
	/**
	 * CIDR Match
	 */
	function cidr_match ($IP, $CIDR)
	{
		list ($net, $mask) = explode ("/", $CIDR);

		$ip_net = ip2long ($net);
		$ip_mask = ~((1 << (32 - $mask)) - 1);

		$ip_ip = ip2long ($IP);

		$ip_ip_net = $ip_ip & $ip_mask;

		return ($ip_ip_net == $ip_net);
	}
  
  
  
	/**
	 * Register routes
	 */
	public static function register_routes($site)
	{
		$site->add_route
		(
			"pay_qiwi_p2p:entry_point", "/wp-json/pay_qiwi_p2p",
			null,
			[
				'render' => function($site)
				{
					global $wpdb;
					
					/**
					 * https://developer.qiwi.com/ru/p2p-sdk-guide/#step7
					 */
					
					if ($_SERVER['REQUEST_METHOD'] != 'POST')
					{
						return "";
					}
					
					// Check ip
					$remote_ip = \Elberos\get_client_ip();
					$check_ip_flag =
						static::cidr_match($remote_ip, "79.142.16.0/20") ||
						static::cidr_match($remote_ip, "195.189.100.0/22") ||
						static::cidr_match($remote_ip, "91.232.230.0/23") ||
						static::cidr_match($remote_ip, "91.213.51.0/24")
					;
					/*
					if (!$check_ip_flag)
					{
						return "";
					}
					*/
					
					$table_log = $wpdb->prefix . 'pay_qiwi_p2p_log';
					$table_transactions = $wpdb->prefix . 'pay_qiwi_p2p_transactions';
					$gmtime_add = gmdate('Y-m-d H:i:s', time());
					$gmtime_pay = null;
					$uid = "";
					$status_value = "";
					
					$text = file_get_contents("php://input");
					$data = @json_decode($text, true);
					
					if ($data != null and isset($data['status']) and isset($data['status']['value']))
					{
						$status_value = $data['status']['value'];
					}
					if ($status_value == 'PAID' and $data != null and isset($data['status']) and
						isset($data['status']['datetime']))
					{
						$dt = strtotime($data['status']['datetime']);
						$gmtime_pay = gmdate('Y-m-d H:i:s', $dt);
					}
					if ($data != null and isset($data['billId']))
					{
						$uid = $data['billId'];
					}
					
					// Insert log
					$wpdb->insert
					(
						$table_log,
						[
							'remote_ip' => $remote_ip,
							'uid' => $uid,
							'status' => 0,
							'status_value' => $status_value,
							'gmtime_add' => $gmtime_add,
							'gmtime_pay' => $gmtime_pay,
							'text' => $text,
							'check_ip_flag' => $check_ip_flag ? "1" : "0",
						]
					);
					
					if (!$check_ip_flag)
					{
						return "";
					}
					
					// Update transaction
					$sql = $wpdb->prepare
					(
						"UPDATE `$table_transactions` set `status`=%s, `gmtime_pay`=%s where `uid`=%s",
						$status_value, $gmtime_pay, $uid
					);
					$wpdb->query($sql);
					
					// Get transaction
					$sql = $wpdb->prepare
					(
						"select * from $table_transactions where uid=%s",
						$uid
					);
					$qiwi_transaction = $wpdb->get_row($sql, ARRAY_A);
					
					// Do action
					do_action('pay_qiwi_p2p_change_status', $qiwi_transaction);
					
					return "";
				},
			]
		);
	}
	
	
	
	/**
	 * Remove plugin updates
	 */
	public static function filter_plugin_updates($value)
	{
		$name = plugin_basename(__FILE__);
		if (isset($value->response[$name]))
		{
			unset($value->response[$name]);
		}
		return $value;
	}
	
	
	
	/**
	 * Register Admin Menu
	 */
	public static function register_admin_menu()
	{
		add_menu_page(
			'pay-qiwi-p2p', 'QIWI P2P',
			'manage_options', 'pay-qiwi-p2p',
			function ()
			{
				\Elberos\QIWI\P2P\Transactions::show();
			},
			'/wp-content/plugins/pay-qiwi-p2p/images/qiwi.png',
			100
		);
		
		add_submenu_page(
			'pay-qiwi-p2p',
			'Настройки', 'Настройки',
			'manage_options', 'pay-qiwi-p2p-settings',
			function()
			{
				\Elberos\QIWI\P2P\Settings::show();
			}
		);
		
	}
	
	
	
	/**
	 * Create transaction
	 */
	public static function create_transaction($invoice_type, $invoice_id, $amount, $currency, $comment, $gmtime_expire)
	{
		global $wpdb;
		
		pay_p2p_qiwi_load();
		
		$expiration_dbtime = gmdate('Y-m-d H:i:s', time());
		$table_site_transactions = $wpdb->prefix . 'pay_qiwi_p2p_transactions';
		$sql = $wpdb->prepare
		(
			"select * from $table_site_transactions where invoice_id=%d and gmtime_expire>%s and status='WAITING'",
			$invoice_id, $expiration_dbtime
		);
		$qiwi_transaction = $wpdb->get_row($sql, ARRAY_A);
		
		if ($qiwi_transaction) return $qiwi_transaction;
		
		/* Setup fields */
		$expire = time() + 30*24*60*60;
		$expire_dt = null;
		if ($gmtime_expire != null) $expire_dt = \Elberos\create_date_from_string($gmtime_expire);
		if ($expire_dt) $expire = $expire_dt->getTimestamp();
		$gmtime_add = gmdate('Y-m-d H:i:s', time());
		$expirationDateTime = gmdate(DATE_W3C, $expire);
		$expire_db_date_time = gmdate('Y-m-d H:i:s', $expire);
		$billId = wp_generate_uuid4();
		$fields =
		[
			'amount' => $amount,
			'currency' => $currency,
			'comment' => $comment,
			'expirationDateTime' => $expirationDateTime,
		];
		
		/* Create qiwi form */
		try
		{
			$secret_key = get_option( 'qiwi_p2p_secret_key', '' );
			$billPayments = new \Qiwi\Api\BillPayments($secret_key);
			$response = $billPayments->createBill($billId, $fields);
		}
		catch (\Exception $ex)
		{
			return null;
		}
		
		if ($response != null)
		{
			$status = "";
			$pay_url = "";
			
			if (isset($response['payUrl'])) $pay_url = $response['payUrl'];
			if (isset($response['status']) and isset($response['status']['value'])) $status = $response['status']['value'];
			
			/* Create transaction */
			$wpdb->insert
			(
				$table_site_transactions,
				[
					'uid' => $billId,
					'status' => $status,
					'invoice_type' => $invoice_type,
					'invoice_id' => $invoice_id,
					'pay_url' => $pay_url,
					'gmtime_add' => $gmtime_add,
					'gmtime_expire' => $expire_db_date_time,
					'fields' => json_encode($fields),
					'answer' => json_encode($response),
					'amount' => $amount,
					'currency' => $currency,
					'comment' => $comment,
				]
			);
			
			/* Get created transaction id */
			$transaction_id = $wpdb->insert_id;
			
			/* Get transaction */
			$sql = $wpdb->prepare
			(
				"select * from $table_site_transactions where id=%d",
				$transaction_id
			);
			$qiwi_transaction = $wpdb->get_row($sql, ARRAY_A);
			if ($qiwi_transaction) return $qiwi_transaction;
		}
		
		return null;
	}
	
}


PAY_QIWI_P2P_Plugin::init();

function pay_p2p_qiwi_load()
{
	if (!class_exists(\Qiwi\Api\BillPayments::class))
	{
		require_once __DIR__ . "/vendor/autoload.php";
	}
}

}