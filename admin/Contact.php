<?php

namespace GrandPack;


/* Check if Wordpress */
if (!defined('ABSPATH')) exit;


if ( !class_exists( Contact::class ) && class_exists( \Elberos\StructBuilder::class ) ) 
{

class Contact extends \Elberos\StructBuilder
{
	
	/**
	 * Get entity name
	 */
	public static function getEntityName()
	{
		return "app_contacts";
	}
	
	
	
	/**
	 * Init struct
	 */
	public function init()
	{
		$this
			
			->addField
			([
				"api_name" => "city_name_ru",
				"label" => "Город",
				"type" => "input",
				"virtual" => true,
			])
			
			->addField
			([
				"api_name" => "city_id",
				"label" => "Город",
				"type" => "select",
			])
			
			->addField
			([
				"api_name" => "name_ru",
				"label" => "Название города на русском",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "price_type_id",
				"label" => "Тип цены",
				"type" => "select",
			])
			
			->addField
			([
				"api_name" => "pos",
				"label" => "Позиция",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "address",
				"label" => "Адрес",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "phone",
				"label" => "Телефон",
				"type" => "textarea",
			])
			
			->addField
			([
				"api_name" => "email",
				"label" => "E-mail",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "location",
				"label" => "Месторасположение",
				"description" => "Метка с сайта https://yandex.ru/map-constructor/location-tool/ \n" .
				"вида: [55.75399399999374,37.62209300000001]",
				"type" => "input",
			])
		;
	}
	
}

}