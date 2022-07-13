<?php

namespace GrandPack;


/* Check if Wordpress */
if (!defined('ABSPATH')) exit;


if ( !class_exists( City_Table::class ) && class_exists( \Elberos\Table::class ) ) 
{

class City_Table extends \Elberos\Table 
{
	
	/**
	 * Table name
	 */
	function get_table_name()
	{
		global $wpdb;
		return $wpdb->base_prefix . 'app_city';
	}
	
	
	
	/**
	 * Page name
	 */
	function get_page_name()
	{
		return "site-cities";
	}
	
	
	
	/**
	 * Create struct
	 */
	static function createStruct()
	{
		$struct = \GrandPack\City::create
		(
			"admin_app_city",
			function ($struct)
			{
				global $wpdb;
				
				$struct->table_fields =
				[
					"name_ru",
					"price_type_id",
					"pos",
				];
				
				$struct->form_fields =
				[
					"name_ru",
					"price_type_id",
					"pos",
				];
				
				$price_types = $wpdb->get_results
				(
					"select * from " . $wpdb->base_prefix . "elberos_commerce_price_types as price_types " .
					"where is_deleted=0",
					ARRAY_A
				);
				$price_types = array_map
				(
					function ($item)
					{
						return ["id"=>$item["id"],"value"=>$item["name"]];
					},
					$price_types
				);
				
				$struct->editField("price_type_id", [
					"options"=>$price_types,
				]);
				
				return $struct;
			}
		);
		
		return $struct;
	}
	
	
	
	/**
	 * Init struct
	 */
	function initStruct()
	{
		parent::initStruct();
	}
	
	
	
	// Действия
	function get_bulk_actions()
    {
		$is_deleted = isset($_REQUEST['is_deleted']) ? $_REQUEST['is_deleted'] : "";
		if ($is_deleted != 'true')
		{
			$actions = array(
				'trash' => 'Переместить в корзину',
			);
		}
		else
		{
			$actions = array(
				'notrash' => 'Восстановить из корзины',
				'delete' => 'Удалить навсегда',
			);
		}
        return $actions;
    }
	
	
	
	/**
	 * Process bulk action
	 */
	function process_bulk_action()
	{
		$action = $this->current_action();
		
		/* Edit items */
		if (in_array($action, ['add', 'edit']))
		{
			parent::process_bulk_action();
		}
		
		/* Trash */
		else if (in_array($action, ['trash', 'notrash', 'delete']))
		{
			parent::process_bulk_action();
		}
	}
	
	
	
	/**
	 * Get item
	 */
	function do_get_item()
	{
		parent::do_get_item();
	}
	
	
	
	/**
	 * Process item
	 */
	function process_item($item, $old_item)
	{
		return $item;
	}
	
	
	
	/**
	 * Item validate
	 */
	function item_validate($item)
	{
		return "";
	}
	
	
	
	/**
	 * Prepare table items
	 */
	function prepare_table_items()
	{
		$args = [];
		$where = [];
		
		$is_deleted = isset($_REQUEST['is_deleted']) ? $_REQUEST['is_deleted'] : "";
		if ($is_deleted == "true") $where[] = "is_deleted = 1";
		else $where[] = "is_deleted = 0";
		
		$per_page = $this->per_page();
		list($items, $total_items, $pages, $page) = \Elberos\wpdb_query
		([
			"table_name" => $this->get_table_name(),
			"where" => implode(" and ", $where),
			"args" => $args,
			"per_page" => $per_page,
			"order_by" => "pos desc, name_ru asc",
			//"log" => true
		]);
		
		$this->items = $items;
		$this->set_pagination_args(array(
			'total_items' => $total_items, 
			'per_page' => $per_page,
			'total_pages' => ceil($total_items / $per_page) 
		));
	}
	
	
	
	/**
	 * CSS
	 */
	function display_css()
	{
		parent::display_css();
		wp_enqueue_media();
		?>
		<style>
		.subsub_table, .subsub_table .subsubsub{
			font-size: 16px;
		}
		.subsub_table_left{
			font-weight: bold;
			padding-right: 5px;
			text-align: right;
		}
		.subsub_table_right{
			padding-left: 5px;
		}
		</style>
		<?php
	}
	
	
	
	/**
	 * Display table sub
	 */
	function display_table_sub()
	{
		$page_name = $this->get_page_name();
		$is_deleted = isset($_REQUEST['is_deleted']) ? $_REQUEST['is_deleted'] : "";
		?>		
		<ul class="subsubsub">
			<li>
				<a href="admin.php?page=<?= $page_name ?>"
					class="<?= ($is_deleted != "true" ? "current" : "")?>"  >Все</a> |
			</li>
			<li>
				<a href="admin.php?page=<?= $page_name ?>&is_deleted=true"
					class="<?= ($is_deleted == "true" ? "current" : "")?>" >В корзине</a>
			</li>
		</ul>
		<?php
	}
	
	
	
	/**
	 * Display form sub
	 */
	function display_form_sub()
	{
		$page_name = $this->get_page_name();
		?>
		<br/>
		<br/>
		<a type="button" class='button-primary' href='?page=<?= $page_name ?>'> Back </a>
		<br/>
		<?php
	}
	
	
	
	/**
	 * Returns form title
	 */
	function get_form_title($item)
	{
		return _e($item['id'] > 0 ? 'Редактировать город' : 'Добавить город', 'app');
	}
	
	
	
	/**
	 * Display action
	 */
	function display_action()
	{
		$action = $this->current_action();
		parent::display_action();
	}
	
	
}

}