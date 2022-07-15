<?php

namespace GrandPack;


/* Check if Wordpress */
if (!defined('ABSPATH')) exit;


if ( !class_exists( City::class ) && class_exists( \Elberos\StructBuilder::class ) ) 
{

class City extends \Elberos\StructBuilder
{
	
	/**
	 * Get entity name
	 */
	public static function getEntityName()
	{
		return "app_city";
	}
	
	
	
	/**
	 * Init struct
	 */
	public function init()
	{
		$this
			
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
				"api_name" => "location",
				"label" => "Центр города",
				"description" => "Метка с сайта https://yandex.ru/map-constructor/location-tool/ \n" .
				"вида: [55.75399399999374,37.62209300000001]",
				"type" => "input",
			])
			
			->addField
			([
				"api_name" => "zoom",
				"label" => "Масштаб",
				"default" => 12,
				"description" => "12 по умолчанию",
				"type" => "input",
			])
		;
	}
	
}

}