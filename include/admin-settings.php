<?php

/*!
 *  Elberos Framework
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


namespace Elberos\QIWI\P2P;

if ( !class_exists( Settings::class ) ) 
{

class Settings
{
	
	public static function update_key($key, $value)
	{
		if (!add_option($key, $value, "", "no"))
		{
			update_option($key, $value);
		}
	}
	
	
	public static function show()
	{
		
		if ( isset($_POST["nonce"]) && (int)wp_verify_nonce($_POST["nonce"], basename(__FILE__)) > 0 )
		{
			$qiwi_p2p_public_key = isset($_POST['qiwi_p2p_public_key']) ? $_POST['qiwi_p2p_public_key'] : '';
			$qiwi_p2p_secret_key = isset($_POST['qiwi_p2p_secret_key']) ? $_POST['qiwi_p2p_secret_key'] : '';
			
			static::update_key("qiwi_p2p_public_key", $qiwi_p2p_public_key);
			static::update_key("qiwi_p2p_secret_key", $qiwi_p2p_secret_key);
		}
				
		$item = 
		[
			'qiwi_p2p_public_key' => get_option( 'qiwi_p2p_public_key', '' ),
			'qiwi_p2p_secret_key' => get_option( 'qiwi_p2p_secret_key', '' ),
		];
		
		?>
		<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
		<h2><?php _e('QIWI P2P Settings', 'pay-qiwi-p2p')?></h2>
		<div class="wrap">			
			<form id="form" method="POST">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
				<div class="metabox-holder" id="poststuff">
					<div id="post-body">
						<div id="post-body-content">
							<div class="add_or_edit_form" style="width: 60%">
								<? static::display_form($item) ?>
							</div>
							<input type="submit" id="submit" class="button-primary" name="submit"
								value="<?php _e('Save', 'pay-qiwi-p2p')?>" >
						</div>
					</div>
				</div>
			</form>
		</div>
		<?php
	}
	
	
	
	public static function display_form($item)
	{
		?>
		
		<!-- Public key -->
		<p>
		    <label for="qiwi_p2p_public_key"><?php _e('Public key:', 'pay-qiwi-p2p')?></label>
		<br>
            <input id="qiwi_p2p_public_key" name="qiwi_p2p_public_key" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['qiwi_p2p_public_key'])?>" >
		</p>
		
		<!-- Secret key -->
		<p>
		    <label for="qiwi_p2p_secret_key"><?php _e('Secret key:', 'pay-qiwi-p2p')?></label>
		<br>
            <input id="qiwi_p2p_secret_key" name="qiwi_p2p_secret_key" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['qiwi_p2p_secret_key'])?>" >
		</p>
		
		<?php
	}
	
}

}