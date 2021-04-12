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


if ( !class_exists( Transactions::class ) ) 
{

class Transactions
{
	public static function show()
	{
		$table = new Transactions_Table();
		$table->display();		
	}
}


class Transactions_Table extends \WP_List_Table 
{
	public $forms_settings = [];
	
	function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'pay-qiwi-p2p',
            'plural' => 'pay-qiwi-p2p',
        ));
    }
	
	function get_table_name()
	{
		global $wpdb;
		return $wpdb->prefix . 'pay_qiwi_p2p_transactions';
	}
		
	// Вывод значений по умолчанию
	function get_default()
	{
		return array(
			'id' => 0,
			'uid' => '',
			'status' => '',
			'invoice_type' => '',
			'invoice_id' => '',
			'gmtime_add' => '',
			'gmtime_expire' => '',
			'gmtime_pay' => '',
		);
	}
	
	// Валидация значений
	function item_validate($item)
	{
		return true;
	}
	
	// Колонки таблицы
	function get_columns()
    {
        $columns = array(
            'uid' => __('UID', 'pay-qiwi-p2p'),
            'status' => __('Статус', 'pay-qiwi-p2p'),
            'sum' => __('Сумма', 'pay-qiwi-p2p'),
            'comment' => __('Комментарий', 'pay-qiwi-p2p'),
            'invoice_type' => __('Тип', 'pay-qiwi-p2p'),
            'invoice_id' => __('Инвойс', 'pay-qiwi-p2p'),
            'gmtime_add' => __('Дата', 'pay-qiwi-p2p'),
            'gmtime_expire' => __('Оплатить до', 'pay-qiwi-p2p'),
            'gmtime_pay' => __('Дата оплаты', 'pay-qiwi-p2p'),
            'buttons' => __('', 'pay-qiwi-p2p'),
        );
        return $columns;
    }
	
	// Сортируемые колонки
    function get_sortable_columns()
    {
        $sortable_columns = array(
            'gmtime_add' => array('gmtime_add', true),
            'gmtime_expire' => array('gmtime_expire', true),
            'gmtime_pay' => array('gmtime_pay', true),
        );
        return $sortable_columns;
    }
	
	// Действия
	function get_bulk_actions()
    {
		return null;
    }
	
	// Вывод каждой ячейки таблицы
	function column_default($item, $column_name)
    {
        return isset($item[$column_name])?$item[$column_name]:'';
    }
	
	// Заполнение колонки cb
	function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }
	
	// Комментарий
	function column_comment($item)
	{
		return esc_html($item['comment']);
	}
	
	// Сумма
	function column_sum($item)
	{
		return esc_html($item['amount'] . ' ' . $item['currency']);
	}
	
	// Дата создания
	function column_gmtime_add($item)
	{
		return esc_html(\Elberos\wp_from_gmtime($item['gmtime_add']));
	}
	
	// Дата истечения
	function column_gmtime_expire($item)
	{
		return esc_html(\Elberos\wp_from_gmtime($item['gmtime_expire']));
	}
	
	// Дата оплаты
	function column_gmtime_pay($item)
	{
		return esc_html(\Elberos\wp_from_gmtime($item['gmtime_pay']));
	}
	
	// Колонка name
	function column_buttons($item)
	{
		$actions = array(
			'show' => sprintf(
				'<a href="?page=pay-qiwi-p2p&action=show&id=%s">%s</a>',
				$item['id'], 
				__('Show item', 'pay-qiwi-p2p')
			),
		);
		
		return $this->row_actions($actions, true);
	}
	
	// Фильтр
	function extra_tablenav( $which )
	{
		if ( $which == "top" )
		{
			$uid = isset($_GET["uid"]) ? $_GET["uid"] : "";
			$status = isset($_GET["status"]) ? $_GET["status"] : "";
			$invoice_id = isset($_GET["invoice_id"]) ? $_GET["invoice_id"] : "";
			$order = isset($_GET["order"]) ? $_GET["order"] : "";
			$orderby = isset($_GET["orderby"]) ? $_GET["orderby"] : "";
			$is_deleted = isset($_GET["is_deleted"]) ? $_GET["is_deleted"] : "";
			?>
			<style>
				.pay_qiwi_p2p_filter input, .pay_qiwi_p2p_filter select{
					display: inline-block;
					vertical-align: middle;
				}
			</style>
			<span class="pay_qiwi_p2p_filter">
				<input name="uid" type="text" class="web_form_value" placeholder="UID"
					value="<?= esc_attr($uid) ?>" />
				<input name="invoice_id" type="text" class="web_form_value" placeholder="ID инвойса"
					value="<?= esc_attr($invoice_id) ?>" />
				<select name="status" class="web_form_value">
					<option value="">Статус</option>
					<option value="WAITING" <?= $status==="WAITING"?"selected":"" ?> >WAITING</option>
				</select>
				<input type="button" class="button dosearch" value="Поиск">
			</span>
			<script>
			
			function pay_qiwi_p2p_filter_submit()
			{
				var filter = [];
				<?= $is_deleted == "true" ? "filter.push('is_deleted=true');" : "" ?>
				<?= $order != "" ? "filter.push('order='+" . json_encode($order) . ");" : "" ?>
				<?= $orderby != "" ? "filter.push('orderby='+" . json_encode($orderby) . ");" : "" ?>
				jQuery(".pay_qiwi_p2p_filter .web_form_value").each(function(){
					var name = jQuery(this).attr("name");
					var value = jQuery(this).val();
					if (value != "") filter.push(name + "=" + encodeURIComponent(value));
				});
				filter = filter.join("&");
				if (filter != "") filter = "&" + filter;
				document.location.href = 'admin.php?page=pay-qiwi-p2p'+filter;
			}
			jQuery(".pay_qiwi_p2p_filter input").keydown(function(e){
				if (e.keyCode == 13)
				{
					e.preventDefault();
					pay_qiwi_p2p_filter_submit();
				}
			});
			jQuery(".dosearch").click(function(){
				pay_qiwi_p2p_filter_submit();
			});
			
			</script>
			<?php
		}
	}
	
	// Создает элементы таблицы
    function prepare_items()
    {
        global $wpdb;
        $table_name = $this->get_table_name();
		
        $per_page = 10; 

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
       
        $this->process_bulk_action();

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
		
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'id';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';
		
		$where = [];
		$attrs = [];
		
		$uid = isset($_GET["uid"]) ? $_GET["uid"] : "";
		$status = isset($_GET["status"]) ? $_GET["status"] : "";
		$invoice_id = isset($_GET["invoice_id"]) ? $_GET["invoice_id"] : "";
		if ($status != "")
		{
			if ($where != "") $where[] = "status=%s";
			$attrs[] = $status;
		}
		if ($uid != "")
		{
			if ($where != "") $where[] = "uid like %s";
			$attrs[] = "%" . $uid . "%";
		}
		if ($invoice_id != "")
		{
			if ($where != "") $where[] = "invoice_id=%d";
			$attrs[] = $invoice_id;
		}
		
		$where = join(" and ", $where);
		if ($where != "") $where = "where " . $where;
		
		$attrs[] = $per_page;
		$attrs[] = $paged * $per_page;
		
		$sql = $wpdb->prepare
		(
			"SELECT t.* FROM $table_name as t $where
			ORDER BY $orderby $order LIMIT %d OFFSET %d",
			$attrs
		);
        $this->items = $wpdb->get_results($sql, ARRAY_A);
		//var_dump($sql);
		
        $this->set_pagination_args(array(
            'total_items' => $total_items, 
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page) 
        ));
    }
	
    function process_bulk_action()
    {
    }
	
	function display_item()
	{
		global $wpdb;
		
		$action = $this->current_action();
		$table_name = $this->get_table_name();
		$message = "";
		$notice = "";
		$nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : false;
		$item = [];
		$default = $this->get_default();
		
		if (isset($_REQUEST['id']))
		{
			$item_id = (int) (isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
			$item = $wpdb->get_row(
				$wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $item_id), ARRAY_A
			);
			if (!$item)
			{
				$item = $default;
				$notice = __('Item not found', 'pay-qiwi-p2p');
			}
		}
		else
		{
			$item = $default;
		}
		
		?>
		<style>
			.forms_data_display_item td{
				padding: 5px;
			}
			.forms_data_item_key{
				text-align: right;
				font-weight: bold;
			}
		</style>
		<div class="wrap">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<h2><?php _e('Forms Data Item', 'pay-qiwi-p2p')?></h2>
			<button type="button" class='button-primary' onclick="history.back();"> Back </button>
			<div class="metabox-holder" id="poststuff">
				<div id="post-body">
					<div id="post-body-content">
						<table class="forms_data_display_item">
							<tr class='forms_data_item'>
								<td class='forms_data_item_key'>UID</td>
								<td class='forms_data_item_value'><?= esc_html($item['uid']); ?></td>
							</tr>
							<tr class='forms_data_item'>
								<td class='forms_data_item_key'>Статус</td>
								<td class='forms_data_item_value'><?= esc_html($item['status']); ?></td>
							</tr>
							<tr class='forms_data_item'>
								<td class='forms_data_item_key'>Сумма</td>
								<td class='forms_data_item_value'>
									<?= esc_html($item['amount']); ?>
									<?= esc_html($item['currency']); ?>
								</td>
							</tr>
							<tr class='forms_data_item'>
								<td class='forms_data_item_key'>Комментарий</td>
								<td class='forms_data_item_value'><?= esc_html($item['comment']); ?></td>
							</tr>
							<tr class='forms_data_item'>
								<td class='forms_data_item_key'>Тип инвойса</td>
								<td class='forms_data_item_value'><?= esc_html($item['invoice_type']); ?></td>
							</tr>
							<tr class='forms_data_item'>
								<td class='forms_data_item_key'>ID инвойса</td>
								<td class='forms_data_item_value'><?= esc_html($item['invoice_id']); ?></td>
							</tr>
							<tr class='forms_data_item'>
								<td class='forms_data_item_key'>Дата создания</td>
								<td class='forms_data_item_value'>
									<?= esc_html($this->column_gmtime_add($item)); ?>
								</td>
							</tr>
							<tr class='forms_data_item'>
								<td class='forms_data_item_key'>Оплатить до</td>
								<td class='forms_data_item_value'>
									<?= esc_html($this->column_gmtime_expire($item)); ?>
								</td>
							</tr>
							<tr class='forms_data_item'>
								<td class='forms_data_item_key'>Дата оплаты</td>
								<td class='forms_data_item_value'>
									<?= esc_html($this->column_gmtime_pay($item)); ?>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
			
		</div>
		
		<?php
	}
	
	function display_table()
	{
		$this->prepare_items();
		$message = "";
		?>
		<div class="wrap">
			<h2><?php echo get_admin_page_title() ?></h2>

			<?php
			// выводим таблицу на экран где нужно
			echo '<form action="" method="POST">';
			parent::display();
			echo '</form>';
			?>

		</div>
		<?php
	}
	
	function display()
	{
		$action = $this->current_action();
		if ($action == 'show')
		{
			$this->display_item();
		}
		else
		{
			$this->display_table();
		}
	}
	
}

}