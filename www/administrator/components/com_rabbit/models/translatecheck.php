<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_orangeei
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');


class RabbitModelTranslateCheck extends JModelAdmin {
	
	const UNKNOWN_CONTENT_TYPE = '0';
	const AUTO_CONTENT_TYPE = '1';
	const CLOTHES_CONTENT_TYPE = '2';
	const FABRICS_CONTENT_TYPE = '3';
	const TEXTILE_CONTENT_TYPE = '4';
	const SOUVENIRS_CONTENT_TYPE = '5';
	const ACCESSORIES_CONTENT_TYPE = '6';
	
	public function getTranlateResult ( $param = array() )
	{
		//make import and return it result
		return array ( "Import result 1", "Import result 2" );
	}
	
	public function translate ( $data, $type, $lang, $meta ) {
		
		if ( ! class_exists ( 'DBHelper' ) ) require_once ( JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'dbh.php' );
		
		$exit_status = 0;
		$importData = $data;	//RabbitHelper::restore_variable ( 'import_data' );
		$translateType = $type;	// RabbitHelper::restore_variable ( 'translate_type' );
		
		$content_type = RabbitHelper::restore_variable ( 'content_type' );
		echo ( "Content type: $content_type" );
		
		// @TODO: Здесь выполняем запись в базу данных. Устанавливаем статус результата. Записываем информацию в отчеты
		try {
			switch ( $content_type ) {
				case self::CLOTHES_CONTENT_TYPE:
					DBHelper::translate ( $importData, $translateType, $lang, $meta );
					break;
				case self::FABRICS_CONTENT_TYPE:
					DBHelper::translate ( $importData, $translateType, $lang, $meta );
					break;
				case self::TEXTILE_CONTENT_TYPE:
					DBHelper::translate ( $importData, $translateType, $lang, $meta );
					break;
				case self::SOUVENIRS_CONTENT_TYPE:
					DBHelper::translate ( $importData, $translateType, $lang, $meta );
					break;
				case self::ACCESSORIES_CONTENT_TYPE:
					DBHelper::translate ( $importData, $translateType, $lang, $meta );
					break;
				default:
					break;
			}
		} catch ( Exception $e ) {
			// @TODO: перенаправление на страницу ошибки
			$this -> import_report ['error'] = $e -> getMessage (  );
			$exit_status = 1;
		}
		
		return $exit_status;
	}
	
	public function getTable($type = 'Rabbit', $prefix = 'RabbitTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}
	
	public function getForm ( $param = array(), $loadData = true )
	{
		$form = $this->loadForm(
			'com_rabbit.translatecheck',
			'translatecheck',
			array(
				'control' => 'jform',
				'load_data' => false
			)
		);
 
		if (empty($form))
		{
			return false;
		}
 
		return $form;
	}
}