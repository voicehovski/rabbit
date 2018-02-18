<?php

defined('_JEXEC') or die();

define ( "CLOTHE_SKU_RE_TEMPLATE", "(\\d+)/(\\d+)/(\\d+)" );
define ( "CATEGORY_RE_TEMPLATE", "/?[\w\s]+(/[\w\s]+)*/?" );
define ( "_CATEGORY_RE_TEMPLATE_", "^" . CATEGORY_RE_TEMPLATE . "$" );

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

	public static $CSV_DELIMITER = ';';
	public static $CSV_ENCLOSURE = '';
	public static $CSV_ESCAPE = '\\';

	var $status;
	
	protected $headers_row_index = -1;
	protected $current_index = -1;
	protected $data_size = 0;
	
	protected $data;
	protected $headers;
	
	protected $config;
	
	public function __construct (
		$csv_data,
		//$config = array ( 'delim' => self::$CSV_DELIMITER, 'encl' => self::$CSV_ENCLOSURE, 'esc' => self::$CSV_ESCAPE )
		$config = array ( 'delim' => ';', 'encl' => '', 'esc' => '' )
	) {
	
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
	* Функции проверки ошибок и парсинга артикула прилеплены здесь, что называется, "не пришей к пизде рукав". Можно сделать их статическими или вообще, выделить отдельный класс
*/
class CsvMetadata {

	// @NOTE: все конфигурационные данные можно вынести в отдельный файл, но использовать их не где и как попало, а в определенных местах типа такого. То есть использовать такие данные через строго определенный интерфейс
	
	/*		Метаданные таблиц импорта (МТИ)
	
		error_status	уровень "серьезности" ошибки (несовпадения поля таблицы с шаблоном): 2 - критичная, 1 - можно пропустить
		type	способ хранения поля в базе: 0 - стандартные свойства, 1 - пользовательские свойства, 2 - локализуемые пользовательские свойства
	*/
	static $PRODUCT_CSV_META_TEMPLATE = array (
		
		'(code)' => array ( 'index' => -1, 'name' => "(код)", 'pattern' => "^.*$", 'error_status' => 2, 'type' => -1 ),
		'(color)' => array ( 'index' => -1, 'name' => "(цвет)", 'pattern' => "^.*$", 'error_status' => 2, 'type' => -1 ),
		'(size)' => array ( 'index' => -1, 'name' => "(размер)", 'pattern' => "^.*$", 'error_status' => 2, 'type' => -1 ),
		
		'name' => array ( 'index' => -1, 'name' => "Название", 'pattern' => "^.+$", 'error_status' => 2, 'type' => 0 ),
		'category' => array ( 'index' => -1, 'name' => "Категория", 'pattern' => _CATEGORY_RE_TEMPLATE_, 'error_status' => 2, 'type' => 0 ),
		'desc' => array ( 'index' => -1, 'name' => "Описание", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 0 ),
		'price' => array ( 'index' => -1, 'name' => "Цена", 'pattern' => '^\d*$', 'error_status' => 2, 'type' => 0 ),
		'images' => array ( 'index' => -1, 'name' => "Изображение", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 0 )
		
	);
	
	static $PRODUCT_CSV_META_TEMPLATE_UA = array (
		
		'(code)' => array ( 'index' => -1, 'name' => "(код)", 'pattern' => "^.*$", 'error_status' => 2, 'type' => -1 ),
		'(color)' => array ( 'index' => -1, 'name' => "(колір)", 'pattern' => "^.*$", 'error_status' => 2, 'type' => -1 ),
		'(size)' => array ( 'index' => -1, 'name' => "(розмір)", 'pattern' => "^.*$", 'error_status' => 2, 'type' => -1 ),
		
		'name' => array ( 'index' => -1, 'name' => "Назва", 'pattern' => "^.+$", 'error_status' => 2, 'type' => 0 ),
		'category' => array ( 'index' => -1, 'name' => "Категорія", 'pattern' => "^.+$", 'error_status' => 2, 'type' => 0 ),
		'desc' => array ( 'index' => -1, 'name' => "Опис", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 0 ),
		'price' => array ( 'index' => -1, 'name' => "Ціна", 'pattern' => '^\d*$', 'error_status' => 2, 'type' => 0 ),
		'images' => array ( 'index' => -1, 'name' => "Зображення", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 0 )
		
	);

	public static function createOldClothesMetadata ( $headers ) {
		
		$CLOTHES_CSV_META_TEMPLATE = array_merge (
			self::$PRODUCT_CSV_META_TEMPLATE,
			array (
				'sku' => array ( 'index' => -1, 'name' => "Артикул", 'pattern' => "^".CLOTHE_SKU_RE_TEMPLATE."$", 'error_status' => 2, 'type' => 0 ),
				
				'group' => array ( 'index' => -1, 'name' => "Группа", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				'theme' => array ( 'index' => -1, 'name' => "Тема", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				'collection' => array ( 'index' => -1, 'name' => "Колекція", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				
				'main' => array ( 'index' => -1, 'name' => "Основной цвет", 'pattern' => ".*", 'error_status' => 1, 'type' => 0 )
			)
		);
		
		return new CsvMetadata ( $CLOTHES_CSV_META_TEMPLATE, $headers );
	}
	
	public static function createClothesMetadata ( $headers ) {
		
		$CLOTHES_CSV_META_TEMPLATE = array_merge (
			self::$PRODUCT_CSV_META_TEMPLATE_UA,
			array (
				'sku' => array ( 'index' => -1, 'name' => "Артикул", 'pattern' => "^".CLOTHE_SKU_RE_TEMPLATE."$", 'error_status' => 2, 'type' => 0 ),
				
				'group' => array ( 'index' => -1, 'name' => "Група", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				'theme' => array ( 'index' => -1, 'name' => "Тема", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				'collection' => array ( 'index' => -1, 'name' => "Колекція", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				
				'style' => array ( 'index' => -1, 'name' => "Стиль", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				'decor' => array ( 'index' => -1, 'name' => "Елементи обробки та декору", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				'siluet' => array ( 'index' => -1, 'name' => "Силует", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				'length' => array ( 'index' => -1, 'name' => "Довжина", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				'sleeve' => array ( 'index' => -1, 'name' => "Рукав", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				'consist' => array ( 'index' => -1, 'name' => "Матеріал", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				//'size' => array ( 'index' => -1, 'name' => "Розмір", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				//'color' => array ( 'index' => -1, 'name' => "Колір", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				
				'main' => array ( 'index' => -1, 'name' => "Основной цвет", 'pattern' => ".*", 'error_status' => 1, 'type' => 0 )
			)
		);
		
		return new CsvMetadata ( $CLOTHES_CSV_META_TEMPLATE, $headers );
	}
	
	public static function createTest1Metadata ( $headers ) {
		
		$TEST_CSV_META_TEMPLATE = array_merge (
			self::$PRODUCT_CSV_META_TEMPLATE,
			array (
				'sku' => array ( 'index' => -1, 'name' => "Артикул", 'pattern' => "^\w\w\w-\d{4}-\d{2,5}$", 'error_status' => 2, 'type' => 0 ),
			
				'text_test' => array ( 'index' => -1, 'name' => "текст", 'pattern' => "^.+$", 'error_status' => 1, 'type' => 1 ),
				'boolean_test' => array ( 'index' => -1, 'name' => "флаг", 'pattern' => "^1|0$", 'error_status' => 1, 'type' => 1 ),
				
				'ext_code' => array ( 'index' => -1, 'name' => "+Код", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 0 ),
				'ext_color' => array ( 'index' => -1, 'name' => "+Цвет", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 0 ),
				'ext_size' => array ( 'index' => -1, 'name' => "+Размер", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 0 ),
				
				'main' => array ( 'index' => -1, 'name' => "Основной цвет", 'pattern' => ".*", 'error_status' => 1, 'type' => 0 )
			)
		);
		
		return new CsvMetadata ( $TEST_CSV_META_TEMPLATE, $headers, function ( $normalizedAssocRow ) {
			return array (
				'(code)' => $normalizedAssocRow ['ext_code'],
				'(size)' => $normalizedAssocRow ['ext_size'],
				'(color)' => $normalizedAssocRow ['ext_color']
			);
		} );
	}
	
	// Для тестовой демонстрации. Урезанная таблица одежда 2017
	public static function createTest2Metadata ( $headers ) {
		//Артикул;Название;Категория;Описание;Состав;Цена;Изображение;Основной цвет;Коллекция;Группа;Тема
		$TEST_CSV_META_TEMPLATE = array_merge (
			self::$PRODUCT_CSV_META_TEMPLATE,
			array (
				'sku' => array ( 'index' => -1, 'name' => "Артикул", 'pattern' => "^\d+/\d+/.+$", 'error_status' => 2, 'type' => 0 ),
			
				'consist' => array ( 'index' => -1, 'name' => "Состав", 'pattern' => "^.+$", 'error_status' => 1, 'type' => 2 ),
				
				'group' => array ( 'index' => -1, 'name' => "Группа", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				'theme' => array ( 'index' => -1, 'name' => "Тема", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				'collection' => array ( 'index' => -1, 'name' => "Колекция", 'pattern' => "^.*$", 'error_status' => 1, 'type' => 2 ),
				
				'main' => array ( 'index' => -1, 'name' => "Основной цвет", 'pattern' => ".*", 'error_status' => 1, 'type' => 0 )
			)
		);
		
		return new CsvMetadata ( $TEST_CSV_META_TEMPLATE, $headers );
	}
	
	public static function createFabricsMetadata ( $headers ) {
		
		$FABRICS_CSV_META_TEMPLATE = array_merge (
			self::$PRODUCT_CSV_META_TEMPLATE,
			array (
				'sku' => array ( 'index' => -1, 'name' => "Артикул", 'pattern' => "^.+$", 'error_status' => 2, 'type' => 0 ),
				
				'main' => array ( 'index' => -1, 'name' => "Основной цвет", 'pattern' => ".*", 'error_status' => 1, 'type' => 0 )
			)
		);
		
		return new CsvMetadata ( $FABRICS_CSV_META_TEMPLATE, $headers, function ( $normalizedAssocRow ) {
			$property = $normalizedAssocRow ['sku'];
		} );
	}
	
	public static function createTextileMetadata ( $headers ) {
		
		$TEXTILE_CSV_META_TEMPLATE = array_merge (
			self::$PRODUCT_CSV_META_TEMPLATE,
			array (
				'sku' => array ( 'index' => -1, 'name' => "Артикул", 'pattern' => "^.+$", 'error_status' => 2, 'type' => 0 )
			)
		);
		
		return new CsvMetadata ( $TEXTILE_CSV_META_TEMPLATE, $headers, function ( $normalizedAssocRow ) {
			$property = $normalizedAssocRow ['sku'];
		} );
	}
	
	public static function createSalesMetadata ( $headers ) {

		$SALES_CSV_META_TEMPLATE = array (
			'sku' => array ( 'index' => -1, 'name' => "Артикул", 'pattern' => "^.*$", 'error_status' => 2, 'type' => 0 ),
			'category' => array ( 'index' => -1, 'name' => "Категория", 'pattern' => "^(".CATEGORY_RE_TEMPLATE.")*$", 'error_status' => 2, 'type' => 0 ),
			'price' => array ( 'index' => -1, 'name' => "Цена", 'pattern' => "^\d+([\.,](\d{1,2})|(\d+%))*$", 'error_status' => 2, 'type' => 0 )
		);
		
		return new CsvMetadata ( $SALES_CSV_META_TEMPLATE, $headers );
	}
	
	public static function createUserMetadata (  ) {}
	
	public static function createOrdersMetadata (  ) {}
	
	protected $metadata;
	
	protected $getPVP;

/*
	@TODO:
	* Нужно более надежное сравнение чем просто strcasecmp. Например посредством mb_strtolower
	* Ошибку лучше выбрасывать в виде исключения и обрабатывать выше
*/
	public function __construct ( $metadata_template, $headers, $getPVP = null ) {

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
		
		if ( is_callable ( $getPVP ) ) {
			$this -> getPVP = $getPVP;
		} else {
			$this -> getPVP = function ( $normalizedAssocRow ) {
			
				$property = $normalizedAssocRow ['sku'];
			
				$parts =  explode ( '/', $property );
				
				if ( count ( $parts ) != 3 ) {
					throw new Exception ( 'Sku parsing error' . " [$property]" );
				}
				
				return array ( '(code)' => $parts [0], '(size)' => $parts [1], '(color)' => $parts [2] );
			};
		}
	}

	public function errors (  ) {}	// Errors, occured while parsing headers
	
	// @IDEA: realize with khawai array functions
	public function getMeta ( $propertyName = null ) {
		
		if ( ! $propertyName ) {
			return $this -> metadata;
		}
		
		$column = array (  );
		foreach ( $this -> metadata as $k => $v ) {
			$column [$k] = isset ( $v [ $propertyName ] ) ? $v [ $propertyName ] : null;
		}
		
		return $column;
	}
	public function codes (  ) {} //Can be got by array_keys ( $metadata ); 
	
	
/*		Проверяет корректность строки данных csv

	@HOW_TO_USE: Передать массив строк. Порядок значений должен соответствовать строке заголовков, переданной в конструктор
	
	@ACTIONS: 
	* Выполняет цикл по элементам валидатора
	* Выбирает соотвествующий элемент входных данных
	* Проверяет соответствие регулярному выражению
	* Вносит найденные ошибки в список
	
	@RETURN: Список ошибок или пустой массив. Выбрасывает исключение если не удалось установить кодировку
	
	@PROBLEMS:
	* Как фиксировать ошибки типа: количество ячеек не соответствует количеству заголовков? В принципе можно здесь отмечать как лишние или отсутствующие. Или лучше в вызывающем коде?
	
	@TODO:
	* Вынести установку кодировки повыше
	* Сделать обработку исключений. Тоже повыше
	
	@IDEAS:
	* Добавить к результату описание ошибки
*/
	public function checkCells ( $csv_row, $index ) {
	
		$errors = array ();
		
		if ( ! mb_regex_encoding ( "UTF-8" ) ) {
			
			echo "Couldn`t set regex encoding at " . __FUNCTION__;
			throw new Exception ( "Couldn`t set regex encoding" );
		}
		
		foreach ( $this -> metadata as $header_key => $header_data ) {
			if ( ! mb_ereg ( $header_data ['pattern'], $csv_row [$header_data ['index']], $matches ) ) {
				
				//Данные в ячейке не корректны. Записываем в ошибки
				$errors [] = new CellCsvError ( $index, $header_data ['index'], $csv_row [$header_data ['index']], $header_data ['pattern'], $header_data ['error_status'] );
			}
		}
		
		return $errors;
	}

/*		Проверяет логическую корректность данных дерева

	@HOW_TO_USE: передать объект типа ProductGroup
	
	@ACTIONS: 
	* группирует продукцию по коду продукта
	* в каждой пдгруппе счиатет маркеры главного продукта
	* записвывает ошибки маркеров
	* группирует продукцию по артикулам
	* считает где больше одного
	* записывает ошибки дублирования артикулов
	
	@RETURN: массив ошибок или пустой массив
	
	@PROBLEMS:
	* слишком разноплановая. Можно разбить на две
*/		
	public function checkStructural ( $productGroup ) {
		
		$errors = array (  );
		
		$codeGroups = $productGroup -> groupBy ( '(code)' );
		foreach ( $codeGroups as $code => $group ) {

			$mainProducts = $group -> where (
				function ( $p ) {
					$m = $p -> get ( 'main' );
					return ! empty ( $m );
				} 
			);
			
			switch ( $mainProducts -> size (  ) ) {
				case 1:
					// Clear
					break;
				case 0:
					// Warning, no main product marker. Use anyone as the main
					// @QUESTION: Правильно ли будет запихать идентификатор группы в саму группу? А номера строк?
					$errors [] = new StructuralError ( $group -> rowIndexes (  ), $code, "Warning, no main product marker in product group: $code", 1 );
					break;
				default:
					// Warning, more than one marker. Use last with marker as the main
					$errors [] = new StructuralError ( $mainProducts -> rowIndexes (  ), $code, "Warning, more than one marker in product group: $code", 1 );
				
			}
		}
		
		unset ( $codeGroups );
		unset ( $group );
		
		$skuGroups = $productGroup -> groupBy ( 'sku' );
		foreach ( $skuGroups as $sku => $group ) {
			
			if ( $group -> size (  ) > 1 ) {
				$errors [] = new StructuralError ( $group -> rowIndexes (  ), $sku, "Warning, same sku: $sku" );
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
	* Захардкоженные имена колонок
	* По-моему она не на своём месте
	
	@TODO:
	* Учесть разные форматы артикулов. Какг это распределить по таблицам?
	
	@IDEAS:
	* Может лучше передавать не свойство, а весь продукт?
*/			
	public function getProductVariantProperties ( $property ) {
		$f = $this -> getPVP;
		return $f( $property );
	}
	
/*		Создаёт ассоциативный массив для доступа к данным csv по кодам

	@ACTIONS:
	* Перечень ключей создаётся по заголовкам
	* Если в данных отсутствует индекс, соответствующий ключу, в АМ будет записана пустая строка
	* Если в данных лишний индекс, он будет проигнорирован

	@PROBLEMS:
	* Что делать если соответствующего индекса нет? На данный момент по соответствующему ключу будет записана пустая строка
*/
	public function createAssoc ( $csv_row ) {
		
		$assoc = array (  );
		
		foreach ( $this -> metadata as $header_key => $header_data ) {
			// Обращение по несуществующему индексу вернет null
			$assoc [$header_key] = $csv_row [$header_data ['index']] === null ? "" : $csv_row [$header_data ['index']];
		}
		
		return $assoc;
	}
}



class CellCsvError {
	
	public static function worstErrorStatus ( $errors ) {
		
		if ( ! is_array ( $errors ) || empty ( $errors ) ) {
			return 0;
		}
		
		$worst = 0;
		
		foreach ( $errors as $e ) {
			$worst = max ( $e -> status (  ), $worst );
		}
		
		return $worst;
	}
	
	public function __construct ( $row, $column, $value, $comment, $status ) {
		$this -> row = $row;
		$this -> column = $column;
		$this -> value = $value;
		$this -> comment = $comment;
		$this -> status = $status;
	}
	
	protected $row;
	protected $column;
	protected $value;
	protected $comment;
	protected $status;
	
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
	public function status (  ) {
		return $this -> status;
	}
}

class StructuralError {

	public static function worstErrorStatus ( $errors ) {
		
		if ( ! is_array ( $errors ) || empty ( $errors ) ) {
			return null;
		}
		
		$worst = 0;
		
		foreach ( $errors as $e ) {
			$worst = max ( $e -> status (  ), $worst );
		}
		
		return $worst;
	}

	public function __construct ( $rows, $value, $comment, $status ) {
		$this -> rowIndexes = is_array ( $rows ) ? $rows : array ( $rows );
		$this -> value = $value;
		$this -> comment = $comment;
		$this -> status = $status;
	}
	
	protected $rowIndexes;
	protected $value;
	protected $comment;
	protected $status;

	public function rowIndexes (  ) {
		return $this -> rowIndexes;
	}
	public function value (  ) {
		return $this -> value;
	}
	public function comment (  ) {
		return $this -> comment;
	}
	public function status (  ) {
		return $this -> status;
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


/*		Оболочка для строки данных csv

	@ANNOTATION: Содержит строку csv в виде АМ с ключами в соответствии с CsvMetadata. Реализует методы доступа по ключу, проверки существования свойстава и получения индекса строки в исходной таблице
	
	@HOW_TO_USE:
	* Служит основой ProductGroup
	
	@PROBLEMS:
	* Доступ по волшебным кодам
	* Метод get не отличает null-свойства от отсутствующих свойств. Нужно вызывать containsProperty
*/
class ProductData {
	
	protected $row;
	protected $index;
	
	public function __construct ( $rowIndex, $assocCsvRow ) {
		
		$this -> row = $assocCsvRow;
		$this -> index = $rowIndex;
	}

/*		Возвращает свойство продукта

	@HOW_TO_USE: Передавать в качестве аргумента коды в соответствии с csv-metadata, а также code, color и size

	@RETURN: Возвращает свойство продукта или null если такого свойства нетъ. При вызове без параметров возвращает все свойства
	
	@IDEAS:
	* Сделать проверку аргумента на допустимые коды колонок
	* Сделать все свойства одним однородным массвом +
	* Можно хранить данные в Group в одном двумерном массиве с числовыми индексами - тогда можно использовать функции массивов, например для извлечения столбца, а ProductData возвращать из группы чем-то вроде getProduct или where
	
	@PROBLEMS:
	* Как различать отсутствие свойства и свойство с пустым значением - Можно проверять результат на null строго
*/			
	public function get ( $name = null ) {

		if ( isset ( $name ) ) 
			return $this -> row [$name];
		
		return $this -> row;
	}

	//@NOTE: Добавлено 23:20 5.01.2018
	public function set ( $name, $value ) {
		$this -> row [$name] = $value;
	}
	
	public function containsProperty ( $propertyName ) {
		return array_key_exists ( $propertyName, $this -> row );
	}
	
	public function getRowIndex (  ) {
		return $this -> index;
	}
	
	public function __toString (  ) {
		$strep = "{$this->index}: ";
		foreach ( $this -> row as $key => $value ) {
			$strep .= "$key = $value, ";
		}
		return $strep;
	}
}

/*		Структура для фильтрации, группировки и доступа к данным csv.

	@ANNOTATION: Основана на объектах ProductData. Фильтрующие методы возвращают ProductGroup
	
	@HOW_TO_USE:
	* Создаётся путем последовательного вызова метода add с ассоциативным массивом данных (CsvMetadata -> createAssoc) или конструктором
	* Далее можно извлекать различные подмножества и группировать элементы
	
	@PROBLEMS:
	* Нужна проверкаа корректности данных и кодов колонок
	* При создании и добавлении данные никак не проверяются. То есть можно пихать туда объекты с разными свойствами

	@IDEAS:
	* Комплексная проверка вне или  разнообразные is- и has- методы здесь?
	* Для большей скорости можно при создании/добавлении индексировать элементы
*/
class ProductGroup {
	
	public function __construct ( $productDataArray = null ) {
		
		/* @TODO:
			Если аргумент пустой, создаём пустое множество
			if ( empty ( $productDataArray ) )
				return;
			
			Если аргумент содержит хотябы один элемент, создаём на его основе список свойств и сохраняем данные
			if ( is_array ( $productDataArray ) && is_set ( $productDataArray [0] ) )
				$keys = $productDataArray [0];
			
			Если есть еще элементы, проверяем совпадение множеств свойств. Функция array_diff_key принимает несколько массивов и взвращает элементы первого, ключи которых отсутствуют в остальных.
			for ( $i = 1; $i < count ( $productDataArray ); %i++ ) {
				
				$pData = $productDataArray [$i];
				
				// returns array like index => key, it is not what we need
				$currentKeys = array_keys ( $pData );
				
				$diff = array_diff_key ( $keys, $pData );
				if ( ! empty ( $diff ) ) {
					...
				}
				$diff = array_diff_key ( $pData, $keys );
				if ( ! empty ( $diff ) ) {
					...
				}	
				// We can collect new keys if different keysets are allowed
				$keys = array_merge ( $keys, $pData );
			}
			
			$this -> keys = $keys;	//For chec in add method
		*/
		if ( isset ( $productDataArray ) ) {
			$this -> products = is_array ( $productDataArray ) ? $productDataArray : array ( $productDataArray );
		}
	}
	
	protected $products = array (  );	//ProductData []
	
/*		Возвращает одно свойство всех элементов группы в массиве

	@HOW_TO_USE: Передать имя свойства в соответствии с метаданными csv
	
	@ACTIONS: 
	* Проверяет существует ли свойство в в элементах группы
	* В цикле формирует массив свойств
	
	@RETURN: массив со свойствами или null если такого свойства на существует в группе
	
	@PROBLEMS:
	* Получается не эффективненько. Нужно помнить что метод следует использовать именно если нужна тупо одна колонка. Иначе лучше where  и цикл
	* Проверка на существование ключа основана на проверке первого продукта. Все элементы должны содержать один набор ключей или допустимо отсутствие некоторых ключей у некоторых элементов (и тогда в соответствующих позициях возвращать null)? Нужно или проверять ключи при добавлении в группу или уже при извлечении?
	
	@IDEAS:
	*
*/			
	public function properties ( $propertyName ) {
		
		if ( ! $this -> contains ( $propertyName ) ) {
			return null;
		}
		
		$properties = array (  );
		foreach ( $this -> getAll (  ) as  $p ) {
			$properties [] = $p -> get ( $propertyName );
		}
		
		return $properties;
	}
	
	/*		Возвращает номера строк элементов группы в соответствии с исходными данными csv

	@HOW_TO_USE:
	
	@ACTIONS:
	
	@RETURN: массив номерами строк в соответствии с исходными данными csv
	
	@PROBLEMS:
	* Нужно ли проверять индексы на совпадение?
	
	@IDEAS:
	*
*/			
	public function rowIndexes (  ) {
		
		$indexes = array (  );
		foreach ( $this -> getAll (  ) as  $p ) {
			$indexes [] = $p -> getRowIndex (  );
		}
		
		return $indexes;
	}
	
/*		Проверяет, есть ли в элементах группы свойство

	@HOW_TO_USE: Передать имя свойства в соответствии с метаданными csv
	
	@ACTIONS: 
	*
	
	@RETURN:
	
	@PROBLEMS:
	* Проверяет наличие свойства только в первом элементе
	* Как проверять? Есть ли хоть в одном элементе? Есть ли во всех? Тогда, наверно, лучше вести список свойств.
	
	@TODO:
	* Изменить имя в соответствии с тем что будет делать фнукция
*/
	public function contains ( $propertyName ) {
		if ( ! isset ( $products [0] ) ) {
			return false;
		}
		return $products [0] -> containsProperty ( $propertyName );
	}
	
/*		Возвращает гуппу продукции в соответствии с фильр-функцией или пустую группу

	@QUESTIONS:
	* Как лучше формировать пустую гурппу? Явно вызывать конструктор без параметров или просто передавать что на фильтровали и пустой массив сам сделает группу пустой?
	
	@PROBLEMS:
	* Нужно внимательно следить чтобы в фильтрующей функции не происходило изменение аргумента, поскольку это ссылка, то есть может быть изменен базовый объект

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
		
		//return empty ( $relevantProducts ) ? null : new ProductGroup ( $relevantProducts );
		return empty ( $relevantProducts ) ? new ProductGroup (  ) : new ProductGroup ( $relevantProducts );
	}
	
	
/*		Возвращает продукцию в которой $pName = $pValue, упакованную в ProductGroup

*/
	public function getWhere ( $pName, $pValue ) {
		
		// @QUESTION: do we need property name check here?
		// @IDEA: we can register property names while adding product
		
		return $this -> where ( 
			function ( $p ) use ( $pName, $pValue ) {
				return strcmp ( $p -> get ( $pName ), $pValue ) == 0;
			}
		);
	}
	
/*		Группирует продукцию по свойству. Если у продукта свойства нет, он не будет включен в результат

	@HOW_TO_USE: Передать имя свойства в соответствии с метаданными csv
	
	@ACTIONS: 
	* 
	
	@RETURN: АМ имя_свойства => соответствующая_группа_товаров или пустой массив (если такого свойства нет)
	
	@PROBLEMS:
	* Проверяет наличие свойства на каждой итерации, что не есть эффективненько
	* Если наборы свойств элементов группы различаются, то в результат может попасть только часть группы
	
	@IDEAS:
	* Можно элементы без свойства выделить в null-группу
*/
	public function groupBy ( $propertyName ) {
		
		$groups = array (  );
		
		foreach ( $this -> getAll (  ) as $p ) {
			
			// @NOTE: Возможно лучше делать регистрировать свойства при добавлении продукции (отдельный случай - создание группы из группы - не нужно проверять) и как-то обозначать ситуацию разных наборов свойств. Тогда здесь можно проверять не для каждого товара, а только один раз.
			if ( ! $p -> containsProperty ( $propertyName ) ) {
				continue;
			}
			
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

	public function size (  ) {
		
		return count ( $this -> getAll (  ) );
	}
	
	public function isEmpty (  ) {
		
		return $this -> size (  ) == 0;
	}
	
}

