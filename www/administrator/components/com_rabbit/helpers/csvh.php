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

	var $status;
	
	protected $headers_row_index = -1;
	protected $current_index = -1;
	protected $data_size = 0;
	protected $data;
	
	public $validator;
	
	public function __construct ( $csv_data, $validator_data, $config ) {
	
		$status = 0;
		
		//Входные данные должны быть массивом строк
		if ( ! is_array ( $csv_data ) ) {
			throw new Exception ( "Incorrect csv" );
		}
		
		//Массив должен содержать хоть что-то
		if ( ! count ( $csv_data ) ) {
			throw new Exception ( "Incorrect csv" );
		}
		
		//Пропускаем пустые строки, если такие есть
		$index = 0
		while ( ! csv_data [$index++] ) {
			if ( $index == count ( $csv_data ) ) {
				throw new Exception ( "Incorrect csv" );
			}
			continue;
		}
		
		$this -> headers_row_index = $ndex;
		$this -> current_index = $index + 1;
		$this -> data_size = count ( $csv_data );
		$this -> data = $csv_data;
		
		$table_headers = str_getcsv ( $csv_data [$index], $config ['delim'], $config ['encl'], $config ['esc'] );
		$this -> validator = new CsvValidator ( $validator_data, $table_headers );	
	}
	
	public hasMoreRows (  ) {
		return $this -> current_index < $this -> data_size;
	}
	
	public getNextRow (  ) {
		return $this -> validator -> check ( 
			str_getcsv (
				$this -> data [$this -> current_index++],
				$config ['delim'],
				$config ['encl'],
				$config ['esc']
			)
		);
	}
}

class CsvValidator {

	protected $validator_data;

	public __construct ( $validator_data, $headers ) {
		for ( $i = 0; $i < count ( $headers ); $i++ ) {
			//Используем здесь ссылку чтобы можно было изменять элементы массива
			foreach ( $validator_data as &$h ) {
				//! strcasecmp хуёво сравнивает мультибайтные строки без учета регистра, а именно - не сравнивает
				if ( strcasecmp ( $headers [$i], $h ['name'] ) == 0 ) {
					if ( $h ['index'] != -1 ) {
						//! Ошибка. Такой заголовок уже зарегистрирован. Надо что-то делать
					}
					$h ['index'] = $i;
					break;	//foreach
				}
			}
		}
		//! Проверить, все ли заголовки есть, нет ли лишних
		$this -> validator_data = $validator_data;
	}

	public function check ( $csv_row ) {
	
		$row = new CsvRow (  );
	
		foreach ( $this -> validator_data as $header_key => $header_data ) {
			
			if ( preg_match ( $header_data ['pattern'], $csv_row [$header_data ['index']], $matches ) ) {
				$row -> setValue ( $header_key, $csv_row [$header_data ['index']] );
				
				if ( $header_key == 'sku' ) {
					echo implode ( "::", $matches );
				}
			} else {
				//Данные в ячейке не корректны. Записываем в ошибки
				$row -> setErrorStatus ( $header_key, $header_data ['error_status'] );
			}	
		}
		
		return $row;
	}
}

/*		Что улучшить
	
	В конструктор можно передавать $validator_data для проверки допустимости ключей в сеттерах
	
	В конструктор можно передавать индекс текущей строки
	
	В геттеры можно добавить проверки существования
*/
class CsvRow {
	
	public __construct (  ) {
	
	}
	
	protected $row_data = array (  );
	protected $error_statuses = array (  );
	
	public function hasError (  ) {
		return count ( $error_statuses ) == 0;
	}
	
	public function setValue ( $key, $value ) {
		$row_data [$key] = $value;
	}
	
	public function setErrorStatus ( $key, $status ) {
		$error_statuses [$key] = $status;
	}
	
	public function getValue ( $key ) {
		return $row_data [$key];
	}
	
	public function getErrorStatus ( $key ) {
		return $error_statuses [$key];
	}
}

/*		Использование

	Выполняем это в модели check вместо кода который там сейчас
	
	Метод create_header_indexes можно удалить из RabbitHelper
	
	Данные валидатора можно хранить в RabbitHelper, как это делается в текущей версии и дополнить их данными проверки пользователей и заказов
	
	Передаём данные в вид check показываем что напроверяли. Если порядок, работаем дальше с product_tree
	
		План действий
		
	Проверяем работоспособность
	
	Реализуем Report и ProductTree
*/

try {
	$csv = new csvHelper (
		$csv_data,
		RabbitHelper::PRODUCT_TABLE_VALIDATOR,
		array ( 'delim' => RabbitHelper::$CSV_DELIMITER, 'encl' => RabbitHelper::$CSV_ENCLOSURE, 'esc' => RabbitHelper::$CSV_ESCAPE )
	);
 } catch ( Exception $e ) {
	//Do something
 }
 
 $report = new Report (  );
 $product_tree = new ProductTree (  );

while ( $csv -> hasMoreRows (  ) ) {
	$row = $csv -> getNextRow (  );

	// А дальше думаем...
	
	if ( $row -> hasError (  ) ) {
		$report -> add ( $row );
	}
	
	$product_tree -> add ( $row );

}