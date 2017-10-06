<?php

defined('_JEXEC') or die();

/*		Класс для проверки csv-данных. Доступ к проверенным данным в форме итератора

	@ANNOTATION: Принимает массив строк, первую непустую считает заголовками, остальные - данными. Строки проходят проверку и доступны для последовательного чтения
	
	@HOW_TO_USE:
	* Передать в конструктор массив строк, данные для валидатора и конфиг с разделителями
	* В случае грубых ошибок конструктор выбросит исключение
	* Проверить есть ли еще строки методом hasMoreRows
	* Получить очередную строку методом getNextRow
	
	@PROBLEMS:
	* 
*/
class csvHelper {

	var $status;
	
	protected $headers_row_index = -1;
	protected $current_index = -1;
	protected $data_size = 0;
	
	protected $data;
	protected $headers;
	protected $rows;
	protected $errors;
	
	protected $config;
	
	public $validator;
	public $indexator;
	
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
		$index = -1;
		while ( ! $csv_data [++$index] ) {
			if ( $index == count ( $csv_data ) ) {	// Массив закончился, а данных все нет
				throw new Exception ( "Incorrect csv" );
			}
			continue;
		}
		
		$this -> headers_row_index = $index;	// Первую не пустую строку считаем заголовками
		$this -> headers = str_getcsv ( $csv_data [$index], $config ['delim'], $config ['encl'], $config ['esc'] );
		
		$this -> current_index = $index + 1;	// Следующую строку считаем данными
		//$this -> data_size = count ( $csv_data );	нет, возможно размер будет меньше
		
		while ( $csv_data [++$index] ) {
			if ( $index == count ( $csv_data ) ) {
				break;
			}
			if ( ! $csv_data [$index] ) {
				continue;
			}
			$this -> data [$index] = str_getcsv ( $csv_data [$index], $config ['delim'], $config ['encl'], $config ['esc'] );
		}
		
		$this -> validator = new CsvValidator ( $validator_data, $this -> headers );	
		//$this -> indexator = new CsvIndexator ( $validator_data, $this -> headers );

		$this -> rows = $this -> create_rows (  );
		$this -> errors = $this -> check_rows ( $this -> /*rows*/data );
		
		$this -> config = $config;
	}

	public function data () {	//raw 2x array
		return $this -> data;
	}
	
	public function headers () {	//headers array
		return $this -> headers;
	}
	
	public function errors () {
		return $this -> errors;
	}
	
	public function rows (  ) {
		return $this -> rows;
	}
	
	public function code_list () {} //code => index	
	
	// @TODO: может проверять как исходные данные (текущая версия), так и ассоциативные массивы. Наверно лучше ассоциативные
	// @TODO: можно не разбивать проверку и создание рядков на две функции ( и делать в каждой цикл ), а вынести общий цикл в конструктор, а влидатор будет только дополнять свой массив в соответствии с заголовками
	// @TODO: валидация данных и индексация заголовков - все-таки разные вещи. Можно попробовать разделить 
	protected function check_rows ( & $rows ) {
		
		$errors = array (  );
		
		foreach ( $rows as $index => $row ) {
			$error = $this -> validator -> check ( $row, $index ); //check may return boolean and than getError will return error object
			if ( ! empty ( $error ) ) {
				$errors [] = $error;
			}
		}
		
		return $errors;
	}
	
	protected function create_rows (  ) {
		
		$rows = array (  );
		
		foreach ( $this -> data as $index => $csv_row ) {
			$rows [] = $this -> validator -> createAssoc ( $csv_row );	//We assume createAssoc always return right value but it may not
		}
		
		return $rows;
	}
	
}


/*		Класс для валидации строки csv-данных

	@ANNOTATION: Идея в том чтобы проверять данные таблицы импорта регулярными выражениями и при этом не зависеть от позиций столбиков. Для этого мы создаём структуру с кодами столбиков (по которым и будет осуществляться доступ), РВ для проверки и кодами ошибок. В конструкторе в эту структуру мы записываем индексы в соответствии с входными данными
	
	@HOW_TO_USE:
	* Создать объект на основе специальной структуры данных и строки заголовков csv
	* Передать в конструктор класса csvHelper
	
	@PROBLEMS:
	* 
*/
class CsvValidator {

	protected $validator_data;

	public function __construct ( $validator_data, $headers ) {
		for ( $i = 0; $i < count ( $headers ); $i++ ) {
			//Используем здесь ссылку чтобы можно было изменять элементы массива
			foreach ( $validator_data as &$h ) {
				//! strcasecmp хуёво сравнивает мультибайтные строки без учета регистра, а именно - не сравнивает
				if ( strcasecmp ( $headers [$i], $h ['name'] ) == 0 ) {
					if ( $h ['index'] != -1 ) {
						//! Ошибка. Такой заголовок уже зарегистрирован. Надо что-то делать
						echo "Such header has already registred in validator: $i, " . $h['name'] . "<br/>";
					}
					$h ['index'] = $i;
					break;	//foreach
				}
			}
		}
		//! Проверить, все ли заголовки есть, нет ли лишних
		$this -> validator_data = $validator_data;
	}

	
/*		Проверяет корректность данных csv

	@HOW_TO_USE: Передать массив строк. Порядок значений должен соответствовать строке заголовков
	
	@ACTIONS: 
	* Выполняет цикл по элементам валидатора
	* Выбирает соотвествующий элемент входных данных
	* Проверяет соответствие регулярному выражению
	* Заполняет выходной объект проверенными данными и статусом ошибки
	
	@RETURN: Объект с проверенными данными и статусами ошибок
	
	@PROBLEMS:
	* 
	
	@IDEAS:
	* Добавить к результату комментарий ошибки?
*/
	public function check ( $csv_row, $index ) {
	
		$error = null;
	
		foreach ( $this -> validator_data as $header_key => $header_data ) {
			
			if ( ! preg_match ( $header_data ['pattern'], $csv_row [$header_data ['index']], $matches ) ) {
				
				//Данные в ячейке не корректны. Записываем в ошибки
				$error = new CellCsvError ( $index, $header_data ['index'], $csv_row [$header_data ['index']], $header_data ['pattern'] );
			}
		}
		
		return $error;
	}
	
	public function createAssoc ( $csv_row ) {
		
		$assoc = array (  );
		
		foreach ( $this -> validator_data as $header_key => $header_data ) {
			$assoc [$header_key] = $csv_row [$header_data ['index']] );
		}
		
		return $assoc;
	}
}


/*		Класс, представляющий строку данных csv, проверенную объектом CsvValidator

	@ANNOTATION: Содержит данные информацию о найденных ощибках в виде хеш-таблиц. Ключи соответствуют ключам структур валидатора. Статус показывает критичность ошибки: "можно двигаться дальше, но не забывать" или "дальнейшая обработка будет некорректной"
	
	@HOW_TO_USE:
	* 
	
	@PROBLEMS:
	* 

	@IDEAS:
	* В конструктор можно передавать $validator_data для проверки допустимости ключей в сеттерах
	* В конструктор можно передавать индекс текущей строки
	* В геттеры можно добавить проверки существования
*/
class CsvRow {
	
	public function __construct ( $index = 0 ) {
		$this -> index = $index;
	}
	
	protected $index;
	protected $row_data = array (  );
	protected $error_statuses = array (  );
	
	public function getIndex (  ) {
		return $this -> index;
	}
	
	public function hasError (  ) {
		// @NOTE: count возвращает количество фактически существующих элементов, независимо от индексов. Для объектов - количество нестатических свойств, для не коллекций - 1, для null - 0. Второй аргумент позволяет считать рекурсивно
		return count ( $error_statuses ) == 0;
	}
	
	public function getWorstErrorStatus ( $status ) {
		$current_status = $status;
		
		foreach ( $this -> error_statuses as $es ) {
			if ( $es > $current_status )
				$current_status = $es;
		}
		
		return $current_status;
	}
	
	public function setValue ( $key, $value ) {
		$this -> row_data [$key] = $value;
	}
	
	public function setErrorStatus ( $key, $status ) {
		$this -> error_statuses [$key] = $status;
	}
	
	public function getValue ( $key ) {
		return $this -> row_data [$key];	// @NOTE: Если такого ключа нет, будет возвращен null
	}
	
	public function getErrorStatus ( $key ) {
		return $this -> error_statuses [$key];
	}

	
	public function getErrorStatuses (  ) {
		return $this -> error_statuses;
	}

	public function getRowData (  ) {
		return $this -> row_data;
	}
}


class Report {
	
	public function __construct (  ) {
		$this -> error_data = array (  );
	}
	
	public $error_data;
	
	public function add ( $validCsvRow ) {
		$this -> error_data [] = $validCsvRow -> getErrorStatuses (  );
	}
}

/*		Структура для логической проверки данных о товарах и организации доступа к ним.

	@ANNOTATION: Содержит данные информацию о найденных ощибках в виде хеш-таблиц. Ключи соответствуют ключам структур валидатора 
	
	@HOW_TO_USE:
	* Создаётся путем последовательного вызова метода add с валидной строкой таблицы csv
	* Далее можно выполнить проверку - метод check
	* Далее извлекаем  и используем нужные нам подмножества товаров
	
	@PROBLEMS:
	* 

	@IDEAS:
	* Возможно лучше вместо комплексной проверки сделать ВОЗМОЖНОСТЬ проверки, например для каждой группы и для всех товаров разнообразные is- и has- методы?
*/
class ProductTree {
	
	public function __construct (  ) {
		$this -> data = array (  );
	}
	
	protected $data;
	
	protected $logicalErrors = array (  );
	public function getLogicalErrors (  ) {
		return $this -> logicalErrors;
	}

/*		Добавляет в структуру новый продукт

	@HOW_TO_USE: Передать CsvRow - результат работы csvHelper::getNextRow ()
	
	@ACTIONS: 
	* Извлекает артикул
	* Определяет по артикулу код товара, цвет и размер, если не удается, завершается с ошибкой
	* Проверяет наличие такого артикула в дереве. Если есть, заменяет
	* Сохраняет новый продукт в дереве
	
	@RETURN:
	
	@PROBLEMS:
	* 
*/	
	public function add ( $validCsvRow ) {
		
		$sku = $validCsvRow -> getValue ( 'sku' );
		$mainAttrs = $this -> getProductMainAttrs ( $sku );
		
		if ( ! $mainAttrs ) {
			
			// Error, can`t recognize main attrs
			return;
		}
		
		list ( $code, $size, $color ) = $mainAttrs;
		
		if ( ! isset ( $this -> data [ $code ] ) ) {
		
			$this -> data [ $code ] = new ProductGroup (  );
		}
		
		// Проверяем есть ли такой продукт. Поскольку мы решили заменять существующий продукт в любом случае, сразу делаем удаление
		// @PROBLEMS: При вызове этой функции происходит зависание. Цикл внутри remove каждый раз получает на один больше элементов. Возможно дело в unset
		/*
		$removedElements = $this -> data [ $code ] -> remove (
			function ( $p ) use ( $sku ) {
				return strcmp ( $p -> getProperty ( 'sku' ), $sku ) == 0;
			}
		);
		if ( ! empty ( $removedElements ) ) {
			// @PROBLEMS: Выходит что здесь осуществляется часть логической проверки
			// @IDEA: Возможно правильнее вынести логическую проверку во внешнюю функцию, а этот класс пусть организует удобный доступ к данным
			// Warning, such product already exists. Replace it by newer. It`s nice to compare them and show differencies
			$this -> logicalErrors [] = array ( 'csvLine' => $validCsvRow -> getIndex (  ), 'comment' => "Same sku: $sku" );
		}
		*/
		
		$this -> data [ $code ] -> add ( new ProductData ( $color, $size, $validCsvRow ) );
	}

/*		Возвращает код продукта, код цвета и код размера на основе артикула (или другой информации)

	@HOW_TO_USE: 
	
	@ACTIONS: 
	* 
	
	@RETURN:
	
	@PROBLEMS:
	* Работает только на основе артикула
*/		
	function getProductMainAttrs ( $sku ) {
		return explode ( '/', $sku );
	}
	
/*		Проверяет логическую корректность данных дерева

	@HOW_TO_USE: 
	
	@ACTIONS: 
	* 
	
	@RETURN:
	
	@PROBLEMS:
	* 
	
	@QUESTIONS:
	* Должен ли он что-то возвращать или все будет делаться внутре?
*/		
	public function check (  ) {
		
		foreach ( $this -> data as $groupId => $productGroup ) {
			$mainProducts = $productGroup -> where (
				function ( $product ) {
					$m = $product -> getProperty ( 'main' );
					// print_r ( $product );
					// @NOTE: Возвращает ЛОЖЪ если переменная (! НЕ ВЫРАЖЕНИЕ) не существует или == ЛОЖЪ ("", "0", 0 или пустой массив или собственно ЛОЖЪ)
					return ! empty ( $m );
				}
			);
			
			switch ( count ( $mainProducts ) ) {
				case 1:
					// Clear
					break;
				case 0:
					// Warning, no main product marker. Use any one as the main
					// @QUESTION: Правильно ли будет запихать идентификатор группы в саму группу? А номера строк?
					$this -> logicalErrors [] = array ( 'csvLine' => "multi", 'comment' => "Warning, no main product marker in product group: $groupId" );
					break;
				default:
					// Warning, more than one marker. Use last with marker as the main
					$this -> logicalErrors [] = array ( 'csvLine' => "multi", 'comment' => "Warning, more than one marker in product group: $groupId" );
				
			}
			
			//foreach ( $productGroup -> getAll (  ) as $product ) {
			
		}
	}

	public function get (  ) {
		return $this -> data;
	}
	
	public function where ( $isRelevant ) {
		
		$relevantProducts = array (  );
		
		foreach ( $this -> data as $group ) {
			$relevantProducts = array_merge ( $relevantProducts, $group -> where ( $isRelevant ) );
		}
		
		return $relevantProducts;
	}
	
}

class ProductData {
	
	protected $color;
	protected $size;
	protected $row;
	
	public function __construct ( $color, $size, $validCsvRow ) {
		$this -> color = $color;
		$this -> size = $size;
		$this -> row = $validCsvRow;
	}

/*		Возвращает свойство продукта

	@HOW_TO_USE: Передавать в качестве аргумента коды в соответствии с валидатором, а также color и size

	@RETURN: Возвращает свойство продукта или null если такого свойства нетъ
	
	@IDEAS:
	* Сделать проверку аргумента на допустимые коды колонок
	* Сделать все свойства одним однородным массвом
	* Можно хранить данные в Group в одном двумерном массиве с числовыми индексами - тогда можно использовать функции массивов, например для извлечения столбца, а ProductData возвращать из группы чем-то вроде getProduct или where
*/			
	public function getProperty ( $name ) {
		if ( strcmp ( $name, 'color' ) == 0 ) {
			return $this -> color;
		}
		
		if ( strcmp ( $name, 'size' ) == 0 ) {
			return $this -> size;
		}
		
		return $this -> row -> getValue ( $name );
	}

	public function get (  ) {
		return array_merge ( array ( 'color' => $this -> color, 'size' => $this -> size ), $this -> row -> getRowData (  ) ) ;
	}
}

class ProductGroup {
	
	protected $products = array (  );	//ProductData []
	
	public function where ( $isRelevant/* = function ( $product ) { return true; }*/ ) {
		
		$relevantProducts = array (  );
		
		foreach ( $this -> getAll (  ) as $p ) {
			if ( $isRelevant ( $p ) ) {
				$relevantProducts [] = $p;
			}
		}
		
		return $relevantProducts;
	}
	
/*		Удаляет продукты из группы (НЕ ИЗ БАЗОВОГО СПИСКА)

	@HOW_TO_USE: Передать функцию-фильтр
	
	@ACTIONS: 
	* Применяет к каждому элементу группы функцию-фильтр
	* Удаляет подходящие элементы из массива
	
	@RETURN: список удаленных из группы элементов
	
	@PROBLEMS:
	* Функция введена для упрощения проверки повторяющихся артикулов - чтобы не нужно было сначала выполнять поиск для проверки наличия, а потом повторять поиск для удаления
	* Чо с ссылками?
	
	@IDEAS:
	* Может принимать список элементов для удаления. Тогда решается проблема повторного поиска и имеем возможность что-то сделать между проверкой наличия и удалением
*/		
	public function remove ( $isRelevant/* = function ( $product ) { return false; }*/ ) {
		
		$relevantProducts = array (  );
		
		foreach ( $this -> getAll (  ) as $p ) {
			
			if ( $isRelevant ( $p ) ) {
				$relevantProducts [] = $p;
				unset ( $p );	// @CHECK: Где тут ссылки и как их удолять?
			}
		}
		
		return $relevantProducts;
	}
	
	public function add ( $productData ) {
		
		$this -> products [] = $productData;
	}
	
	public function getAll (  ) {
		
		return $this -> products;
	}
}

