<?php

defined('_JEXEC') or die();

/*		Вспомогательный класс

	Чтобы создать экземпляр, например в модели или виде, выполняем следующее:
	if ( !class_exists ( 'csvHelper' ) ) require ( JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'csvh.php' );
	$csv_helper = csvHelper::getInstance (  );

	Дальше используем как обычный объект:
	echo $csv_helper -> hallo (  );
*/


class csvHelper_example {
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