<?php

defined('_JEXEC') or die();

class csvHelper {
}

/*		Класс для нормализации csv-данных и доступа к ним

	@ANNOTATION: Принимает массив строк, первую непустую считает заголовками, остальные - данными. Предоставляет доступ к исходному массиву, к  данным 
	
	@HOW_TO_USE:
	* Передать в конструктор массив строк и конфиг с разделителями
	* В случае грубых ошибок конструктор выбросит исключение
	* Для работы с исходным массивом - методы data и headers
	
	@IDEAS:
	* Централизованный доступ к исходным именам заголовков, шаблонам проверки, статусам ошибок через класс метаданных по коду
	
	@PROBLEMS:
	* 
*/
class Csv {

	var $status;
	
	protected $headers_row_index = -1;
	protected $current_index = -1;
	protected $data_size = 0;
	
	protected $data;
	protected $headers;
	
	protected $config;
	
	public function __construct ( $csv_data, $config ) {
	
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
		
		$this -> config = $config;
	}

	public function data () {	//raw 2x array
		return $this -> data;
	}
	
	public function headers () {	//headers array
		return $this -> headers;
	}
}

/*		Класс для индексации и проверки csv-данных

	@ANNOTATION: Смысл этого класса - организовать доступ к данным csv по заранее заданным кодам, а не по номерам колонок, которые могут меняться. Для этого мы записываем соответствия кодов номерам колонок в массив. Туда же записываем РВ для проверки и коды ошибок.
	
	@HOW_TO_USE:
	* Создать объект на основе специальной структуры данных и строки заголовков csv
	* Для проверки csv-строки передём её в checkCells
	* Для создания ассоциативного массива на основе csv-строки передаём её в createAssoc
	
	@PROBLEMS:
	* 
*/
class CsvMetadata {

	protected $metadata;

	public function __construct ( $metadata_template, $headers ) {
		for ( $i = 0; $i < count ( $headers ); $i++ ) {
			//Используем здесь ссылку чтобы можно было изменять элементы массива
			foreach ( $metadata_template as &$h ) {
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
		$this -> metadata = $metadata_template;
	}

	public function errors (  ) {}	// Errors, occured while parsing headers
	public function getMeta ( $propertyName ) {}	//Should return array or assoc of headers, indexes etc.
	public function codes (  ) {} //Can be got by array_keys ( $metadata ); 
	
	
/*		Проверяет корректность строки данных csv

	@HOW_TO_USE: Передать массив строк. Порядок значений должен соответствовать строке заголовков, переданной в конструктор
	
	@ACTIONS: 
	* Выполняет цикл по элементам валидатора
	* Выбирает соотвествующий элемент входных данных
	* Проверяет соответствие регулярному выражению
	* Вносит найденные ошибки в список
	
	@RETURN: Список ошибок
	
	@PROBLEMS:
	* Как фиксировать ошибки типа: количество ячеек не соответствует количеству заголовков? В принципе можно здесь отмечать как лишние или отсутствующие
	
	@IDEAS:
	* Добавить к результату комментарий ошибки?
*/
	public function checkCells ( $csv_row, $index ) {
	
		$errors = array ();
	
		foreach ( $this -> metadata as $header_key => $header_data ) {
			if ( ! preg_match ( $header_data ['pattern'], $csv_row [$header_data ['index']], $matches ) ) {
				
				//Данные в ячейке не корректны. Записываем в ошибки
				$errors [] = new CellCsvError ( $index, $header_data ['index'], $csv_row [$header_data ['index']], $header_data ['pattern'] );
			}
		}
		
		return $errors;
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
	public function checkStructural ( $productGroup ) {
		
		$errors = array (  );
		
		$codeGroups = $productGroup -> groupBy ( 'code' );
		foreach ( $codeGroups as $code => $group ) {
			
			$mainProducts = $group -> where (
				function ( $p ) {
					$m = $p -> get ( 'main' );
					return ! empty ( $m );
				} 
			);
			
			switch ( count ( $mainProducts ) ) {
				case 1:
					// Clear
					break;
				case 0:
					// Warning, no main product marker. Use anyone as the main
					// @QUESTION: Правильно ли будет запихать идентификатор группы в саму группу? А номера строк?
					// @TODO: Put into product line number and make method for column getting in group
					$erors [] = new StructuralError ( $group -> properties ( 'line' ), $code, "Warning, no main product marker in product group: $code" );
					break;
				default:
					// Warning, more than one marker. Use last with marker as the main
					$errors [] = new StructuralError ( $mainProducts -> properties ( 'line' ), $code, "Warning, more than one marker in product group: $code" );
				
			}
		}
		unset ( $codeGroups );
		unset ( $group );
		
		$skuGroups = $productGroups -> groupBy ( 'sku' );
		foreach ( $skuGroups as $sku => $group ) {
			
			if ( count ( $group -> getAll (  ) ) > 1 ) {
				$errors [] = new StructuralError ( $group -> properties ( 'line' ), $sku, "Warning, same sku: $sku" );
			}
		}
		unset ( $skuGroups );
		unset ( $group );
		
		return $errors;
	}
	
/*		Возвращает код продукта, код цвета и код размера на основе артикула (или другой информации)

	@HOW_TO_USE: 
	
	@ACTIONS: 
	* 
	
	@RETURN:
	
	@PROBLEMS:
	* Работает только на основе артикула
*/			
	public function getProductVariantProperties ( $property ) {
		return explode ( '/', $property );
	}
	
/*		Создаёт ассоциативный массив для доступа к данным csv по кодам
*/
	public function createAssoc ( $csv_row ) {
		
		$assoc = array (  );
		
		foreach ( $this -> metadata as $header_key => $header_data ) {
			$assoc [$header_key] = $csv_row [$header_data ['index']];
		}
		
		return $assoc;
	}
}


class CellCsvError {
	
	public function __construct ( $row, $column, $value, $comment ) {
		$this -> row = $row;
		$this -> column = $column;
		$this -> value = $value;
		$this -> comment = $comment;
	}
	
	protected $row;
	protected $column;
	protected $value;
	protected $comment;
	
	public function row (  ) {
		return $this -> row;
	}
	public function column (  ) {
		return $this -> column;
	}
	public function value (  ) {
		return $this -> value;
	}
	public function comment (  ) {
		return $this -> comment;
	}
}

class StructuralError {
	
	public function __construct ( $rows, $value, $comment ) {
		$this -> rowIndexes = is_array ( $rows ) ? $rows : array ( $rows );
		$this -> value = $value;
		$this -> comment = $comment;
	}
	
	protected $rowIndexes;
	protected $value;
	protected $comment;

	public function rowIndexes (  ) {
		return $this -> rowIndexes;
	}
	public function value (  ) {
		return $this -> value;
	}
	public function comment (  ) {
		return $this -> comment;
	}
	
	public function isRange (  ) {
		
		if ( count ( $this -> rowIndexes ) <= 1 )
			return true;
		
		for ( $i = 0; $i < (count ( $this -> rowIndexes ) - 1); $i++ ) {
			if ( ($this -> rowIndexes [$i] - $this -> rowIndexes [$i + 1]) > 1 )
				return false;
		}
		
		return true;
	}
}


class ProductData {
	
	protected $row;
	
	public function __construct ( $assocCsvRow ) {
		
		$this -> row = $validCsvRow;
	}

/*		Возвращает свойство продукта

	@HOW_TO_USE: Передавать в качестве аргумента коды в соответствии с csv-metadata, а также color и size

	@RETURN: Возвращает свойство продукта или null если такого свойства нетъ
	
	@IDEAS:
	* Сделать проверку аргумента на допустимые коды колонок
	* Сделать все свойства одним однородным массвом +
	* Можно хранить данные в Group в одном двумерном массиве с числовыми индексами - тогда можно использовать функции массивов, например для извлечения столбца, а ProductData возвращать из группы чем-то вроде getProduct или where
*/			
	public function get ( $name = null ) {

		if ( isset ( $name ) ) 
			return $this -> row [$name];
		
		return $this -> row;
	}
}

/*		Структура для логической проверки данных о товарах и организации доступа к ним.

	@ANNOTATION:  
	
	@HOW_TO_USE:
	* Создаётся путем последовательного вызова метода add с ассоциативным массивом данных (CsvMetadata -> createAssoc) или конструктором
	* Далее можно извлекать различные подмножества и группировать элементы
	
	@PROBLEMS:
	* Нужна проверкаа корректности данных и кодов колонок

	@IDEAS:
	* Комплексная проверка вне или  разнообразные is- и has- методы здесь?
	* Для большей скорости можно при создании/добавлении индексировать элементы
*/
class ProductGroup {
	
	public function __construct ( $productDataArray = null ) {
		if ( isset ( $productDataArray ) ) {
			$this -> products = is_array ( $productDataArray ) ? $productDataArray : array ( $productDataArray );
		}
	}
	
	protected $products = array (  );	//ProductData []
	
/*
	
		@IDEAS:
		* return array_filter ( $this -> getAll (  ), $isRelevant ) 
	*/
	public function where ( $isRelevant/* = function ( $product ) { return true; }*/ ) {
		
		$relevantProducts = array (  );
		
		foreach ( $this -> getAll (  ) as $p ) {
			if ( $isRelevant ( $p ) ) {
				$relevantProducts [] = $p;
			}
		}
		
		return empty ( $relevantProducts ) ? null : new ProductGroup ( $relevantProducts );
	}
	
	public function getWhere ( $pName, $pValue ) {
		
		// @QUESTION: do we need property name check here?
		// @IDEA: we can register property names while adding product
		
		return $this -> where ( 
			function ( $p ) use ( $pName, $pValue ) {
				return strcmp ( $p -> get ( $pName ), $pValue ) == 0;
			}
		);
	}
	
	public function groupBy ( $propertyName ) {
		
		$groups = array (  );
		
		// @TODO: make a check
		//if ( ! $p -> contains ( $propertyName ) ) {
			
		//	return $groups;
		//}
		
		foreach ( $this -> getAll (  ) as $p ) {
			
			if ( array_key_exists ( $p -> get ( $propertyName ), $groups ) ) {
				
				$groups [$p -> get ( $propertyName )] -> add ( $p );
			} else {
				
				$groups [$p -> get ( $propertyName )] = new ProductGroup ( array ( $p ) );
			}
		}
		
		return $groups;
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
		
		foreach ( $this -> getAll (  ) as $i => $p ) {
			
			if ( $isRelevant ( $p ) ) {
				$relevantProducts [] = $p;
				unset ( $this -> products [$i] );	// @CHECK: Не уверен что будет работать корректно
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

