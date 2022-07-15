<?php

namespace GrandPack;

/* Check if Wordpress */
if (!defined('ABSPATH')) exit;


class Email
{
	
	/**
	 * Register hooks
	 */
	static function register_hooks()
	{
		add_action('elberos_user_recovery_password1_after', 
			'\\GrandPack\\Email::elberos_user_recovery_password1_after');
		add_action('elberos_user_register_after',
			'\\GrandPack\\Email::elberos_user_register_after');
	}
	
	
	
	/**
	 * Register routes
	 */
	function registerRoutes($site)
	{
		$site->add_route
		(
			"site:email:test", "/email/aaaaaa/",
			"pages/404.twig",
			[
				'render' => [$this, 'renderTest'],
			]
		);
	}
	
	
	
	/**
	 * Render email test
	 */
	function renderTest($site)
	{
		return null;
		
		//\App\EmailController::sendInvoiceCreated(100);
		
		//var_dump( \Elberos\tz_date(time(), "Y-m-d", "Asia/Almaty") . " 23:59:59" );
		
		return "Ok1";
	}
	
	
	
	
	/**
	 * Название сайта
	 */
	static function getSiteName()
	{
		return "GrandPack.kz";
	}
	
	
	
	
	/**
	 * Отправка кода восстановления пароля
	 */
	static function elberos_user_recovery_password1_after($client)
	{
		if ($client)
		{
			$title = "Ваш код восстановления пароля от сайта \"" . static::getSiteName() . "\"";
			
			/* Send email to user */
			\Elberos\send_email
			(
				"default",
				$client["email"],
				"@email/RecoveryPassword.twig",
				[
					"title" => $title,
					"client" => $client,
					"site_name" => static::getSiteName(),
				],
				[
					"is_delete" => 1,
				]
			);
		}
	}
	
	
	
	/**
	 * Получаем инвойс по его ID
	 */
	static function getInvoiceByID($invoice_id)
	{
		global $wpdb;
		
		/* Поиск инвойса */
		$table_invoice = $wpdb->base_prefix . "elberos_commerce_invoice";
		$sql = \Elberos\wpdb_prepare
		(
			"select * from `${table_invoice}` " .
			"where id = :id",
			[
				"id" => $invoice_id,
			]
		);
		$invoice = $wpdb->get_row($sql, ARRAY_A);
		return $invoice;
	}
	
	
	
	/**
	 * Получаем пользователя по его ID
	 */
	static function getUserByID($user_id)
	{
		global $wpdb;
		
		/* Поиск инвойса */
		$table_clients = $wpdb->base_prefix . "elberos_clients";
		$sql = \Elberos\wpdb_prepare
		(
			"select * from `${table_clients}` " .
			"where id = :id",
			[
				"id" => $user_id,
			]
		);
		$user = $wpdb->get_row($sql, ARRAY_A);
		return $user;
	}
	
	
	
	/**
	 * Получаем транзакцию по его ID
	 */
	static function getTransactionByID($transaction_id)
	{
		global $wpdb;
		
		/* Поиск инвойса */
		$table_invoice = $wpdb->base_prefix . "elberos_pay_alfabank_transactions";
		$sql = \Elberos\wpdb_prepare
		(
			"select * from `${table_invoice}` " .
			"where id = :id",
			[
				"id" => $transaction_id,
			]
		);
		$transaction = $wpdb->get_row($sql, ARRAY_A);
		return $transaction;
	}
	
	
	
	/**
	 * Получаем инвойс по его ID
	 */
	static function getEmailData($invoice_id)
	{
		global $wpdb;
		
		$invoice = static::getInvoiceByID($invoice_id);
		if (!$invoice)
		{
			return null;
		}
		
		$arr =
		[
			"name" => "Имя",
			"surname" => "Фамилия",
			"user_identifier" => "ИИН",
			"company_name" => "Название компании",
			"company_bin" => "БИН",
			"email" => "E-mail",
			"phone" => "Телефон",
		];
		$client_data_text = [];
		$form_data = json_decode($invoice["form_data"], true);
		$basket_data = json_decode($invoice["basket_data"], true);
		
		foreach ($arr as $key => $title)
		{
			$value = isset($form_data[$key]) ? $form_data[$key] : "";
			$client_data_text[] =
			[
				"key" => $key,
				"title" => $title,
				"value" => $value,
			];
		}
		
		$user_link = site_url("/ru/invoices/" . $invoice["id"] . "/" . $invoice["secret_code"] . "/");
		$admin_link = site_url("/wp-admin/admin.php?page=elberos-commerce-invoice&action=edit&id=" . $invoice["id"]);
		
		return
		[
			"title" => $title,
			"invoice" => $invoice,
			"form_data" => $form_data,
			"basket_data" => $basket_data,
			"client_data_text" => $client_data_text,
			"site_name" => static::getSiteName(),
			"user_link" => $user_link,
			"admin_link" => $admin_link,
		];
	}
	
	
	
	/**
	 * Отправляет сообщение о создании инвойса
	 */
	static function sendInvoiceCreated($invoice_id)
	{
		$data = static::getEmailData($invoice_id);
		if (!$data)
		{
			return;
		}
		
		/* Send email */
		$email_to = \Elberos\get_option("elberos_commerce_invoice_admin_email");
		$form_data = $data["form_data"];
		$data["title"] = "Новый заказ " . $invoice_id . " на сайте " . static::getSiteName();
		$data["site_name"] = static::getSiteName();
		
		//var_dump( $data );
		
		/* Send admin */
		\Elberos\send_email
		(
			"default",
			$email_to,
			"@email/NewInvoiceAdmin.twig",
			$data
		);
		
		/* Send user */
		/*
		\Elberos\send_email
		(
			"default",
			$form_data["email"],
			"@email/NewInvoiceUser.twig",
			$data
		);*/
	}
	
	
	
	/**
	 * Отправляет сообщение об Успешной регистрации
	 */
	static function sendUserRegistered($user_id, $password)
	{
		$user = static::getUserByID($user_id);
		if (!$user)
		{
			return;
		}
		
		$email_to = $user["email"];
		$title = "Регистрация на сайте " . static::getSiteName();
		$auth_url = site_url("/ru/cabinet/login/");
		
		/* Send user */
		\Elberos\send_email
		(
			"default",
			$email_to,
			"@email/UserRegistered.twig",
			[
				"site_name" => static::getSiteName(),
				"title" => $title,
				"user" => $user,
				"password" => $password,
				"auth_url" => $auth_url,
			],
			[
				"is_delete" => 1,
			]
		);
	}
	
	
	
	/**
	 * User register after
	 */
	static function elberos_user_register_after($params)
	{
		$user = $params["user"];
		$password = $params["password"];
		static::sendUserRegistered($user["id"], $password);
	}
	
}