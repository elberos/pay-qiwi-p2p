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

if ( !class_exists( 'PAY_P2P_QIWI_Plugin' ) ) 
{

class PAY_P2P_QIWI_Plugin
{
	static $QIWI_PUBLIC_KEY = "";
	static $QIWI_SECRET_KEY = "";
	
	
	/**
	 * Init Plugin
	 */
	public static function init()
	{
	}
	
	
	
	/**
	 * Create transaction
	 */
	public static function create_transaction($invoice_id, $amount, $currency, $comment)
	{
		global $wpdb;
		
		pay_p2p_qiwi_load();
		
		$gmtime_expire = gmdate('Y-m-d H:i:s', time());
		$table_site_transactions = $wpdb->prefix . 'pay_p2p_qiwi_transactions';
		$sql = $wpdb->prepare
		(
			"select * from $table_site_transactions where invoice_id=%d and gmtime_expire>%s and status='WAITING'",
			$invoice_id, $gmtime_expire
		);
		$qiwi_transaction = $wpdb->get_row
		(	
			$sql,
			ARRAY_A
		);
		
		if ($qiwi_transaction) return $qiwi_transaction;
		
		/* Setup fields */
		$expire = time() + 30*24*60*60;
		$gmtime_add = gmdate('Y-m-d H:i:s', time());
		$expiration_dbtime = gmdate('Y-m-d H:i:s', $expire);
		$expirationDateTime = gmdate(DATE_W3C, $expire);
		$billId = wp_generate_uuid4();
		$fields =
		[
			'amount' => $amount,
			'currency' => $currency,
			'comment' => $comment,
			'expirationDateTime' => $expirationDateTime,
		];
		
		/* Create qiwi form */
		$billPayments = new \Qiwi\Api\BillPayments(static::$QIWI_SECRET_KEY);
		$response = $billPayments->createBill($billId, $fields);
		
		if ($response != null)
		{
			$status = "";
			$pay_url = "";
			
			if (isset($response['payUrl'])) $pay_url = $response['payUrl'];
			if (isset($response['status']) and isset($response['status']['value'])) $status = $response['status']['value'];
			
			/* Create transaction */
			$q = $wpdb->prepare
			(
				"INSERT INTO $table_site_transactions
				(
					uid, status, invoice_id, pay_url, gmtime_add, gmtime_expire, fields, answer
				) 
				VALUES( %s, %s, %d, %s, %s, %s, %s, %s)",
				[
					$billId, $status, $invoice_id, $pay_url, $gmtime_add, $expiration_dbtime,
					json_encode($fields),
					json_encode($response),
				]
			);
			$wpdb->query($q);
			
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


PAY_P2P_QIWI_Plugin::init();

function pay_p2p_qiwi_load()
{
	if (!class_exists(\Qiwi\Api\BillPayments::class))
	{
		require_once __DIR__ . "/vendor/autoload.php";
	}
}

}