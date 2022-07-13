<?php

namespace GrandPack;

use Elberos\Commerce\_1C\Controller as _1C_Controller;


/* Check if Wordpress */
if (!defined('ABSPATH')) exit;


if (!class_exists(Hooks::class))
{


class Hooks
{
	
	/**
	 * Register hooks
	 */
	static function register_hooks()
	{
		/* 1C */
		add_action('elberos_commerce_1c_import_product',
			'\\Grandpack\\Hooks::elberos_commerce_1c_import_product');
		add_action('elberos_commerce_1c_make_ivoice_document',
			'\\Grandpack\\Hooks::elberos_commerce_1c_make_ivoice_document');
		add_action('elberos_commerce_find_client_by_email', 
			'\\Grandpack\\Hooks::elberos_commerce_find_client_by_email');
		add_action('elberos_commerce_find_client_by_code_1c', 
			'\\Grandpack\\Hooks::elberos_commerce_find_client_by_code_1c');
		
		/* Struct */
		add_filter('elberos_struct_builder', '\\Grandpack\\Hooks::elberos_struct_builder');
		add_action('elberos_table_display_css_after_' . \Elberos\Commerce\Category_Table::class,
			'\\Grandpack\\Hooks::elberos_table_display_css_after_category_table');
		
		/* User register validation */
		add_action('elberos_user_register_validation', '\\Grandpack\\Hooks::elberos_user_register_validation');
		
		/* Basket hooks */
		add_action('elberos_commerce_basket_validation', '\\Grandpack\\Hooks::elberos_commerce_basket_validation');
		add_action('elberos_commerce_basket_find_client', '\\Grandpack\\Hooks::elberos_commerce_basket_find_client');
		add_action('elberos_commerce_basket_find_client_by_code_1c', 
			'\\Grandpack\\Hooks::elberos_commerce_basket_find_client_by_code_1c');
		add_action('elberos_commerce_basket_before', '\\Grandpack\\Hooks::elberos_commerce_basket_before');
		add_action('elberos_commerce_basket_after', '\\Grandpack\\Hooks::elberos_commerce_basket_after');
	}
	
	
	
	/**
	 * Импорт товара через 1C
	 */
	public static function elberos_commerce_1c_import_product($res)
	{
		$xml = $res["xml"];
		$product = $res["product"];
		$product_update = $res["product_update"];
		
		$text = $product["text"];
		$text = json_decode($text, true);
		if (!$text) $text = [];
		
		$items = $xml->ЗначенияРеквизитов;
		if ($items != null && $items->getName() == 'ЗначенияРеквизитов')
		{
			foreach ($items->children() as $item)
			{
				if ($item->getName() == 'ЗначениеРеквизита')
				{
					$props_name = \Elberos\mb_trim((string)$item->Наименование);
					$props_value = \Elberos\mb_trim((string)$item->Значение);
					
					/* НаименованиеДляСайта */
					if ($props_name == "НаименованиеДляСайта")
					{
						$product_update["name"] = $props_value;
						$product_update["slug"] = sanitize_title($props_value);
						$text["ru_RU"]["name"] = $props_value;
						$product_update["text"] = json_encode($text);
					}
				}
			}
		}
		
		/* Kod */
		$product_update["kod"] = (string)$xml->Код;
		
		/* Показывать в каталоге */
		$product_update["just_show_in_catalog"] = 1;
		
		$res["product_update"] = $product_update;
		return $res;
	}
	
	
	
	/**
	 * Поиск клиента по коду 1С
	 */
	public static function elberos_commerce_find_client_by_code_1c($params)
	{
		global $wpdb;
		
		$client_code_1c = $params["client_code_1c"];
		
		$table_clients = $wpdb->base_prefix . 'elberos_clients';
		$sql = $wpdb->prepare
		(
			"SELECT * FROM $table_clients WHERE code_1c = %s limit 1", $client_code_1c
		);
		$row = $wpdb->get_row($sql, ARRAY_A);
		
		if ($row)
		{
			$params["client_id"] = $row["id"];
		}
		else
		{
			$params["client_id"] = null;
		}
		//var_dump($params);
		return $params;
	}
	
	
	
	/**
	 * Поиск клиента по email
	 */
	public static function elberos_commerce_find_client_by_email($params)
	{
		global $wpdb;
		
		$client_email = $params["client_email"];
		
		$table_clients = $wpdb->base_prefix . 'elberos_clients';
		$sql = $wpdb->prepare
		(
			"SELECT * FROM $table_clients WHERE email = %s limit 1", $client_email
		);
		$row = $wpdb->get_row($sql, ARRAY_A);
		
		if ($row)
		{
			$params["client_id"] = $row["id"];
		}
		else
		{
			$params["client_id"] = null;
		}
		
		return $params;
	}
	
	
	
	/**
	 * Выгрузка инвойсов
	 */
	public static function elberos_commerce_1c_make_ivoice_document($params)
	{
		$xml = $params["xml"];
		$invoice = $params["invoice"];
		$form_data = $invoice['form_data'];
		
		$xml->addChild('СрокПлатежа', '0001-01-01');
		
		$values = $xml->addChild('ЗначенияРеквизитов');
		
		// Оплата заказа
		if (mb_strtolower($invoice['status_pay']) == 'paid')
		{
			_1C_Controller::addXmlPropKeyValue($values, 'Оплачен', 'true');
			$dt = \Elberos\create_date_from_string($invoice['gmtime_pay']);
			_1C_Controller::addXmlPropKeyValue($values, 'Дата оплаты', $dt ? $dt->format('c') : '' );
			_1C_Controller::addXmlPropKeyValue($values, 'Тип оплаты', $invoice['method_pay_text']);
			
			// Метод оплаты
			if ($invoice['method_pay_text'] == '')
				_1C_Controller::addXmlPropKeyValue($values, 'Метод оплаты', '');
			else
				_1C_Controller::addXmlPropKeyValue($values, 'Метод оплаты', $invoice['method_pay_text']);
		}
		else _1C_Controller::addXmlPropKeyValue($values, 'Оплачен', 'false');
		
		// Дата по 1С
		$dt = \Elberos\create_date_from_string($invoice['gmtime_add']);
		_1C_Controller::addXmlPropKeyValue($values, 'Дата по 1С', $dt ? $dt->format('c') : '');
		
		// Дата оплаты по 1С
		$dt = \Elberos\create_date_from_string($invoice['gmtime_pay']);
		_1C_Controller::addXmlPropKeyValue($values, 'Дата оплаты по 1С', $dt ? $dt->format('c') : 'T');
		
		// Параметры инвойса
		_1C_Controller::addXmlPropKeyValue($values, 'Номер по 1С', "Z-" . $invoice['id']);
		_1C_Controller::addXmlPropKeyValue($values, 'ПометкаУдаления', 'false');
		_1C_Controller::addXmlPropKeyValue($values, 'Проведен', 'false');
		_1C_Controller::addXmlPropKeyValue($values, 'Отгружен', 'false');
		
		
		$arr = $xml->Контрагенты;
		if ($arr != null && $arr->getName() == 'Контрагенты')
		{
			foreach ($arr->children() as $contragent)
			{
				if ($contragent->getName() != 'Контрагент')
				{
					continue;
				}
				if ($contragent->РеквизитыФизЛица != null &&
					$contragent->РеквизитыФизЛица->getName() == 'РеквизитыФизЛица')
				{
					unset($contragent->РеквизитыФизЛица);
					//$contragent->removeChild($contragent->РеквизитыФизЛица);
				}
				if ($contragent->РеквизитыЮрЛица != null &&
					$contragent->РеквизитыЮрЛица->getName() == 'РеквизитыЮрЛица')
				{
					unset($contragent->РеквизитыЮрЛица);
					//$contragent->removeChild($contragent->РеквизитыЮрЛица);
				}
				if ($contragent->Контакты != null &&
					$contragent->Контакты->getName() == 'Контакты')
				{
					unset($contragent->Контакты);
					//$contragent->removeChild($contragent->Контакты);
				}
				if ($contragent->ИНН != null &&
					$contragent->ИНН->getName() == 'ИНН')
				{
					unset($contragent->ИНН);
				}
				if ($contragent->БИН != null &&
					$contragent->БИН->getName() == 'БИН')
				{
					unset($contragent->БИН);
				}
				if ($contragent->ОфициальноеНаименование != null &&
					$contragent->ОфициальноеНаименование->getName() == 'ОфициальноеНаименование')
				{
					unset($contragent->ОфициальноеНаименование);
				}
				if ($contragent->ЮридическийАдрес != null &&
					$contragent->ЮридическийАдрес->getName() == 'ЮридическийАдрес')
				{
					unset($contragent->ЮридическийАдрес);
				}
				
				$form_agent_type = isset($form_data['type']) ? $form_data['type'] : 1;
				
				/* Добавить ИИН */
				/*
				if ($form_agent_type == 1 && isset($form_data['user_identifier']))
				{
					$user_identifier = preg_replace("/[^0-9]/", '', $form_data['user_identifier']);
					$contragent->addChild('ИНН', \Elberos\mb_trim($form_data['user_identifier']));
				}
				*/
				/* Добавить БИН */
				/*
				if ($form_agent_type == 2 && isset($form_data['company_bin']))
				{
					$company_bin = preg_replace("/[^0-9]/", '', $form_data['company_bin']);
					$contragent->addChild('ИНН', \Elberos\mb_trim($company_bin));
				}
				*/
				
				/* Добавить контакты */
				/*
				$contacts = $contragent->addChild('Контакты');
				if (isset($form_data['phone']) && $form_data['phone'] != '')
				{
					$contact = $contacts->addChild('КонтактнаяИнформация');
					$contact->addChild('КонтактВид', 'Телефон мобильный');
					$contact->addChild('Значение', $form_data['phone']);
					$contact->addChild('Комментарий');
				}
				if (isset($form_data['email']) && $form_data['email'] != '')
				{
					$contact = $contacts->addChild('КонтактнаяИнформация');
					$contact->addChild('КонтактВид', 'Почта');
					$contact->addChild('Значение', $form_data['email']);
					$contact->addChild('Комментарий');
				}*/
			}
		}
		
	}
	
	
	
	/**
	 * Struct builder
	 */
	public static function elberos_struct_builder($struct)
	{
		/* Регистрация, профиль, админка клиентов */
		if (
			$struct->entity_name == "elberos_user" and
			in_array($struct->action, ["register", "profile", "admin_clients"])
		)
		{
			$struct->addField
			([
				"api_name" => "user_identifier",
				"label" => "ИИН",
				"type" => "input",
				"php_style" => function ($struct, $field, $item)
				{
					$type = $struct->getValue($item, "type");
					return
					[
						"row" =>
						[
							"display" => ($type == 1) ? "block" : "none",
						],
					];
				},
				"js_change" => function ($struct, $field, $item)
				{
					return
						'var value = $form.find("select[data-name=type]").val();' . "\n" .
						'if (value == 1) jQuery(".web_form_row[data-name=user_identifier]").show();' . "\n" .
						'else jQuery(".web_form_row[data-name=user_identifier]").hide();'
					;
				}
			]);
			
			$struct->addField
			([
				"api_name" => "company_bin",
				"label" => "БИН компании",
				"type" => "input",
				"php_style" => function ($struct, $field, $item)
				{
					$type = $struct->getValue($item, "type");
					return
					[
						"row" =>
						[
							"display" => ($type == 2) ? "block" : "none",
						],
					];
				},
				"js_change" => function ($struct, $field, $item)
				{
					return
						'var value = $form.find("select[data-name=type]").val();' . "\n" .
						'if (value == 2) jQuery(".web_form_row[data-name=company_bin]").show();' . "\n" .
						'else jQuery(".web_form_row[data-name=company_bin]").hide();'
					;
				}
			]);
			
			$struct->addFormField("user_identifier");
			$struct->addFormField("company_bin");
			
			
			if ($struct->action == "register")
			{
				$struct->setFormFields
				([
					"type",
					"name",
					"surname",
					"company_name",
					"email",
					"user_identifier",
					"company_bin",
					"search_name",
					"phone",
					"password1",
					"password2",
					"captcha",
				]);
			}
			if ($struct->action == "profile")
			{
				$struct->setFormFields
				([
					"type",
					"name",
					"surname",
					"company_name",
					"email",
					"user_identifier",
					"company_bin",
					"search_name",
					"phone",
				]);
			}
			if ($struct->action == "admin_clients")
			{
				$struct->setFormFields
				([
					"type",
					"name",
					"surname",
					"company_name",
					"email",
					"user_identifier",
					"company_bin",
					"search_name",
					"phone",
				]);
			}
		}
		
		/* Категории товаров в админке */
		if (
			$struct->entity_name == "elberos_commerce_categories" and
			in_array($struct->action, ["admin_table"])
		)
		{
			$struct->addField([
				"api_name" => "icon_id",
				"type" => "input",
				"label" => "Иконка (размер 32x32 png)",
				//"form_show" => false,
				"form_render" => function($struct, $field, $item)
				{
					$icon_file_id = isset($item['icon_id']) ? $item['icon_id'] : '';
					$icon_file_path = \Elberos\get_image_url($icon_file_id, "thumbnail");
					?>
					<div class='image_file_path_wrap'>
						<input type='button' class='button image_file_path_add_icon' value='Добавить иконку'><br/>
						<input type='hidden' class='icon_file_id web_form_value'
							name='icon_id' data-name='icon_id'
							value='<?= esc_attr($icon_file_id) ?>' readonly>
						<input type='hidden' class='icon_file_path web_form_value'
							name='icon_file_path' data-name='icon_file_path'
							value='<?= esc_attr($icon_file_path) ?>' readonly>
						<img class='icon_file_path_image'
							src='<?= esc_attr($icon_file_path) ?>' style="height: 32px;">
					</div>
					
					<script>
					jQuery(document).on('click', '.image_file_path_add_icon', function(){
						var $wrap = $(this).parents('.image_file_path_wrap');
						var uploader = wp.media
						({
							title: "Файлы",
							button: {
								text: "Выбрать файл"
							},
							multiple: false
						})
						.on('select',
							(function($wrap) {
								return function()
								{
									var attachments = uploader.state().get('selection').toJSON();
									
									for (var i=0; i<attachments.length; i++)
									{
										var photo = attachments[i];
										var photo_time = photo.date;
										if (photo_time.getTime != undefined) photo_time = photo_time.getTime();
										
										//jQuery($wrap).find('.icon_file_path').val(photo.url);
										//jQuery($wrap).find('.icon_file_path_image').attr('src', photo.url);
										jQuery($wrap).find('.icon_file_id').val(photo.id);
										jQuery($wrap).find('.icon_file_path').val(photo.url);
										jQuery($wrap).find('.icon_file_path_image').attr('src', photo.url);
									}
								}
							})($wrap)
						)
						.open();
					});
					</script>
					
					<?php
				},
			]);
			
			
			$struct->addField
			([
				"api_name" => "icon_file_path",
				"label" => "Картинка",
				"form_show" => false,
			]);
			
			$struct->addFormField("icon_id");
			$struct->addFormField("icon_file_path");
			//var_dump($struct->form_fields);
		}
		
		return $struct;
	}
	
	
	
	/**
	 * Конвертация в корректный номер телефона
	 */
	static function convert_to_correct_phone_number($phone)
	{
		$phone_orig = sanitize_user(trim($phone));
		$phone = preg_replace("/[^0-9]/", '', $phone_orig);
		
		if ($phone != "")
		{
			if ($phone_orig[0] == "+") $phone = "+" . $phone;
			else if ($phone_orig[0] == "8") $phone = "+7" . substr($phone, 1);
			else if ($phone_orig[0] != "+") $phone = "+" . $phone;
		}
		
		return $phone;
	}
	
	
	
	/**
	 * Проверка данных пользователя при регистрации
	 */
	public static function elberos_user_register_validation($params)
	{
		$form_data = $params["form_data"];
		$validation = $params["validation"];
		
		$form_data["email"] = trim(isset($form_data["email"]) ? $form_data["email"] : "");
		$form_data["phone"] = trim(isset($form_data["phone"]) ? $form_data["phone"] : "");
		$form_data["comment"] = trim(isset($form_data["comment"]) ? $form_data["comment"] : "");
		$form_data["name"] = trim(isset($form_data["name"]) ? $form_data["name"] : "");
		$form_data["surname"] = trim(isset($form_data["surname"]) ? $form_data["surname"] : "");
		$form_data["company_name"] = trim(isset($form_data["company_name"]) ? $form_data["company_name"] : "");
		$form_data["company_bin"] = trim(isset($form_data["company_bin"]) ? $form_data["company_bin"] : "");
		
		if ($form_data["type"] == 1)
		{
			if (\Elberos\attr($form_data, "name") == "")
			{
				\Elberos\add($validation, ["fields", "name"], "Укажите ваше имя");
				\Elberos\add($validation, ["fields", "firstname"], "Укажите ваше имя");
			}
			if (\Elberos\attr($form_data, "surname") == "")
			{
				\Elberos\add($validation, ["fields", "surname"], "Укажите вашу фамилию");
			}
			if (isset($form_data["company_name"])) unset($form_data["company_name"]);
			if (isset($form_data["company_bin"])) unset($form_data["company_bin"]);
		}
		else if ($form_data["type"] == 2)
		{
			if (\Elberos\attr($form_data, "company_name") == "")
			{
				\Elberos\add($validation, ["fields", "company_name"], "Укажите название компании");
			}
			if (\Elberos\attr($form_data, "company_bin") == "")
			{
				\Elberos\add($validation, ["fields", "company_bin"], "Укажите БИН компании");
			}
		}
		else
		{
			$validation["error"] = "Укажите тип клиента";
			$validation["fields"]["agent_type"][] = "Укажите тип клиента";
		}
		
		/* ИИН */
		$user_identifier = strtolower(isset($form_data["user_identifier"]) ? $form_data["user_identifier"] : "");
		$user_identifier = sanitize_user(trim($user_identifier));
		$user_identifier = preg_replace("/[^0-9]/", '', $user_identifier);
		$form_data["user_identifier"] = $user_identifier;
		
		/* БИН компании */
		$company_bin = strtolower(isset($form_data["company_bin"]) ? $form_data["company_bin"] : "");
		$company_bin = sanitize_user(trim($company_bin));
		$company_bin = preg_replace("/[^0-9]/", '', $company_bin);
		$form_data["company_bin"] = $company_bin;
		
		/* Проверка email */
		$form_data["email"] = sanitize_user(trim(isset($form_data["email"]) ? $form_data["email"] : ""));
		$form_data["email"] = strtolower($form_data["email"]);
		if ($form_data["email"] == "" || !filter_var($form_data["email"], FILTER_VALIDATE_EMAIL))
		{
			$validation["fields"]["email"][] = "E-mail не верен";
		}
		
		/* Проверка телефона */
		$form_data["phone"] = static::convert_to_correct_phone_number
		(
			isset($form_data["phone"]) ? $form_data["phone"] : ""
		);
		if ($form_data["phone"] == "")
		{
			$validation["fields"]["phone"][] = "Укажите телефон";
		}
		
		/* Удаляем лишние данные */
		if ($form_data["type"] == 1)
		{
			if (isset($form_data["company_name"])) unset($form_data["company_name"]);
			if (isset($form_data["company_bin"])) unset($form_data["company_bin"]);
		}
		else if ($form_data["type"] == 2)
		{
			if (isset($form_data["name"])) unset($form_data["name"]);
			if (isset($form_data["firstname"])) unset($form_data["firstname"]);
			if (isset($form_data["surname"])) unset($form_data["surname"]);
			if (isset($form_data["user_identifier"])) unset($form_data["user_identifier"]);
		}
		
		$params["form_data"] = $form_data;
		$params["validation"] = $validation;
		
		return $params;
	}
	
	
	
	/**
	 * Basket validation
	 */
	static function elberos_commerce_basket_validation($params)
	{
		$params = static::elberos_user_register_validation($params);
		
		$form_data = $params["form_data"];
		$validation = $params["validation"];
		
		/* Проверка параметров */
		
		$params["form_data"] = $form_data;
		$params["validation"] = $validation;
		
		/* Для отладки */
		/*
		$params["code"] = -1;
		$params["message"] = "Test";
		*/
		return $params;
	}
	
	
	
	/**
	 * Basket before
	 */
	static function elberos_commerce_basket_before($params)
	{
		return $params;
	}
	
	
	
	/**
	 * Basket after
	 */
	static function elberos_commerce_basket_after($params)
	{
		$invoice = $params["invoice"];
		if ($invoice)
		{
			\GrandPack\Email::sendInvoiceCreated($invoice["id"]);
		}
		
		/* Auth client if need */
		$find_client_res = $params["find_client_res"];
		$client_register = isset($find_client_res['register']) ? $find_client_res['register'] : false;
		
		/*
		if ($client_register == true && isset($find_client_res['item']))
		{
			\Elberos\UserCabinet\Api::create_session($find_client_res['item']);
		}
		*/
	}
	
	
	
	/**
	 * Find client
	 */
	public static function elberos_commerce_basket_find_client($params)
	{
		global $wpdb;
		
		if ($params['client'] != null) return $params;
		
		$form_data = isset($params['form_data']) ? $params['form_data'] : [];
		$email = isset($form_data['email']) ? $form_data['email'] : '';
		
		/* Find client */
		$table_clients = $wpdb->base_prefix . 'elberos_clients';
		$sql = $wpdb->prepare
		(
			"SELECT * FROM $table_clients WHERE email = %s", $email
		);
		$row = $wpdb->get_row($sql, ARRAY_A);
		if ($row)
		{
			$params['register'] = false;
			$params['client'] = $row;
		}
		
		/* Register client */
		else
		{
			$password = wp_generate_password();
			$res = \Elberos\UserCabinet\Api::user_register($form_data, $password);
			$params['code'] = $res['code'];
			$params['message'] = $res['message'];
			$params['validation'] = isset($res["validation"]) ? $res["validation"] : null;
			
			if ($res['code'] == 1)
			{
				$params['register'] = true;
				$params['client'] = $res['item'];
			}
		}
		
		/* Client not found */
		if ($params['client'] == null)
		{
			$params["message"] = "Клиент не найден";
			$params["code"] = -1;
		}
		
		return $params;
	}
	
	
	
	/**
	 * Find client by 1c code
	 */
	public static function elberos_commerce_basket_find_client_by_code_1c($params)
	{
		global $wpdb;
		
		$client_code_1c = $params["client_code_1c"];
		
		$table_clients = $wpdb->base_prefix . 'elberos_clients';
		$sql = $wpdb->prepare
		(
			"SELECT * FROM $table_clients WHERE code_1c = %s limit 1", $client_code_1c
		);
		$row = $wpdb->get_row($sql, ARRAY_A);
		
		if ($row)
		{
			$params["client_id"] = $row["id"];
		}
		else
		{
			$params["client_id"] = null;
		}
		
		return $params;
	}
	
	
	
	/**
	 * CSS
	 */
	public static function elberos_table_display_css_after_category_table($table)
	{
		?>
		<script>
		jQuery(document).on('setFormData', '.elberos_form_edit_category', function(e, obj){
			
			var data = obj.data;
			var $form = $(this);
			
			if (data != null)
			{
				$form.find('.icon_file_path_image').attr('src', data.icon_file_path);
			}
			else
			{
				$form.find('.icon_file_path_image').attr('src', '');
			}
			
		});
		</script>
		<?php
	}
}

}