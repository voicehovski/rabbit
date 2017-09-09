<?php

defined('_JEXEC') or die();

/*		Вспомогательный класс

	Чтобы создать экземпляр, например в модели или виде, выполняем следующее:
	if ( !class_exists ( 'csvHelper' ) ) require ( JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'csvh.php' );
	$csv_helper = csvHelper::getInstance (  );

	Дальше используем как обычный объект:
	echo $csv_helper -> hallo (  );
*/


class csvHelper {
	public $pu;
	protected $pr;
	var $v;
	
	$index = -1;
	
	public function __construct ( $csv_data ) {
		$status = $this -> check ( $csv_data );
	}
	
	protected function check ( $data ) {
		$status = 0;
		
		//Входные данные должны быть массивом строк
		if ( ! is_array ( $data ) ) {
			//$this -> errors = type of $csv_data, "input is not array";
			$status = 3;
		}
		
		//Массив должен содержать хоть что-то
		if ( ! count ( $data ) ) {
			//$this -> errors = type of $csv_data, "input is empty";
			$status = 3;
		}
		
		//Пропускаем пустые строки, если такие есть
		$index = -1
		while ( ! data [++$index] ) {
			continue;
		}
		$this -> index = $index;
	}
	
	function createHeaderIndexes 
}

$csv = new csvHelper ( $csv_data );
$
while ( $csv -> hasMoreRows (  ) ) {
	$row = $csv -> getNextRow (  );
	$row ['sku']
	
}