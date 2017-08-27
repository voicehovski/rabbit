<?php

defined('_JEXEC') or die();

/*	
	Чтобы создать экземпляр, например в модели, выполняем следующее:
	
	if ( !class_exists ( 'csvHelper' ) ) require ( JPATH_COMPONENT_ADMINISTRATOR . DS . 'helpers' . DS . 'csvh.php' );
	$csv_helper = csvHelper::getInstance (  );
*/

class csvHelper {
	public $pu;
	protected $pr;
	var $v;
	
	static $_instance;
	
	private function __construct (  ) {
		$this -> pr = "Hallo from csv helper!";
	}
	
	static public function getInstance (  ) {
		if (!is_object(self::$_instance)) {
			self::$_instance = new csvHelper();
		}
		return self::$_instance;
	}
	
	public function hallo (  ) {
		return $this -> pr;
	}
}