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

class RabbitViewCheck extends JViewLegacy
{
	// @PROBLEM: Эти константы нужно синхронизировать по значениям с xml-описателем формы
	// Отвечают за тип таблицы импота (одежда, ткани, домашний текстиль...)
	const UNKNOWN_CONTENT_TYPE = '0';
	const AUTO_CONTENT_TYPE = '1';
	const CLOTHES_CONTENT_TYPE = '2';
	const FABRICS_CONTENT_TYPE = '3';
	const TEXTILE_CONTENT_TYPE = '4';
	const TEST1_CONTENT_TYPE = '101';
	const TEST2_CONTENT_TYPE = '102';
	const SALES_CONTENT_TYPE = '253';
	const ORDERS_CONTENT_TYPE = '254';
	const USERS_CONTENT_TYPE = '255';
	
	const UNKNOWN_PRODUCT_VARIANT_DEF = '0';
	const DEFAULT_PRODUCT_VARIANT_DEF = '255';
	const AUTO_PRODUCT_VARIANT_DEF = '1';
	const SKUPARSING_1_PRODUCT_VARIANT_DEF = '2';
	const SKUPARSING_2_PRODUCT_VARIANT_DEF = '3';
	const SKUPARSING_3_PRODUCT_VARIANT_DEF = '4';
	const SKULIST_PRODUCT_VARIANT_DEF = '11';
	const CATEGOEYLIST_PRODUCT_VARIANT_DEF = '12';
	const ANOTHERFIELDS_PRODUCT_VARIANT_DEF = '13';
	const ADDITIONALFIELDS_PRODUCT_VARIANT_DEF = '14';
	
	//protected $form = null;
	protected $check_status = null;
	protected $cellErrors = array (  );
	protected $structuralErrors = array (  );
	protected $importData = null;
	protected $csv = null;

	
/*		Проверяет введенные данные и приводит их к форме пригодной для импорта

	@ACTIONS: 
	* Собирает параметры и данные, введенные на предыдущем шаге
	* При необходимости обрабатывает/распаковывает загруженные изображения
	* Проверяет корректность/комплектность изображений
	* Нормализует данные csv-файла
	* Проверяет их на ошибки ячеек (синтаксические)
	* Определяет вариации товаров (раскладывает артикул)
	* Создаёт структуру для импорта
	* Проверяет ошибки структуры (логические)
	* Определяет статус результата
	* Если все нормально, сохраняет структуру импорта в сессии
	
	@TODO:
	* Обработка изображений на сервере, распаковка архива
	* Копирование изображений в соответствующий каталоги
	* Разделение по типу импортируемой продукции (имя таблицы, поле, категория в табилце)
*/
	public function display($tpl = null)
	{
		
		$TMP = JPATH_SITE . '/tmp/';	// Путь загрузки файлов. Аналогичная переменная в контроллере
		
		$this->form = $this->get('Form');
		
		$model = $this -> getModel ( 'check' );
		if ( ! class_exists ( 'csvHelper' ) ) require ( JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'csvh.php' );
		
		// Получаем имена загруженных файлов
		$table_filename = RabbitHelper::restore_variable ( 'uploaded_table' );
		$images = RabbitHelper::restore_variable ( 'uploaded_images' );
		$medi_images = RabbitHelper::restore_variable ( 'uploaded_medi' );
		$mini_images = RabbitHelper::restore_variable ( 'uploaded_mini' );
		
		// Получаем остальные параметры импорта
		$content_type = RabbitHelper::restore_variable ( 'content_type' );
		$product_variant_def = RabbitHelper::restore_variable ( 'product_variant_def' );
		$convert_images = RabbitHelper::restore_variable ( 'convert_images' );	//null or 1
		$images_packed = RabbitHelper::restore_variable ( 'images_packed' );
		$import_type = RabbitHelper::restore_variable ( 'import_type' );
		
		// Через поле полноразмерных изображений загрузка обязательна. Множество изображений или архив
		// @TODO: Способы возврата ошибок и прерывания работы скрипта (как выход из функции). Например метка на скобки
		try {
			if ( empty ( $images ) || ! is_array ( $images ) ) {
				goto end_of_image_processing;
			}
			
			// @TODO: Распаковка архива. Содержимое архива может не соответствовать, так что нужно проверять
			if ( $images_packed ) {
				// Распаковать во временный каталог. Структура каталогов внутри архива должна соответствовать full, medi, mini
				// Если невозможно распаковать, выход
				// Если в архиве не то, выход
				throw new Exception ( "Archive error" );
			}
			
			// @TODO: Конвертация изображений на сервере
			if ( $convert_images ) {
				// Создать стандарт и миниатюру для каждого изображения
				// Если не получилось, выход
				// Сохранить в соответствующие каталоги категорий
				// Остальные загрузки игнорируем
				throw new Exception ( "Convertation error" );
			} else {
				// Должны быть загружены все поля. Проверяем наличие и соответствие имен
				$medi_diff = array (  );
				if ( ! $this -> checkImages ( $images, $medi_images, $medi_diff ) ) {
					throw new Exception ( "Medi images missmatch: " . implode ( ', ', $medi_diff ) );
				}
				
				$mini_diff = array (  );
				if ( ! $this -> checkImages ( $images, $mini_images, $mini_diff ) ) {
					throw new Exception ( "Mini images missmatch: " . implode ( ', ', $mini_diff ) );
				}
				
				
				// @TODO: Копируем файлы в соответствующие каталоги категорий
				// JFile::copy ( $src, $dest );
				// @SEE: https://docs.joomla.org/How_to_use_the_filesystem_package, https://api.joomla.org/cms-3/classes/JFolder.html
				
			}
			
			end_of_image_processing:
		} catch ( Exception $e ) {
			echo "TROUBLES WHILE IMAGE PROCESSING<br/>{$e -> getMessage (  )}<br/>";
		}
		
		try {
			if ( $table_filename ) {
				// @NOTE: Функция file_get_contents читает файл в одну строку, file - в массив строк
				//Читаем данные из таблицы импорта и выполняем проверку.
				$rawCsv = file ( $TMP . $table_filename );

				// Нормализуем данные загруженные из файла и формируем метаданные - индексы колонок и т.д.
				$csv = new Csv ( $rawCsv, array ( 'delim'=>';','encl'=>'','esc'=>'' ) );
				
				// @TODO: Пользователей и заказы, видимо лучше импортировать в другом виде
				// @TODO: Идентификаторы типов продукции хранить в одном месте - xml-описателе или базе данных, но не константами которые нужно синхронизировать с xml
				// @QUESTION: Какими способами можно связать то что будет выведено в форме с функциями которые будут это обрабатывать? Одноименные константы, указать функции в описателе. Как еще? Какие преимущества и недостатки?
				// Теперь можно создавать ассоциативные формы строк csv и обращаться к данным в них по заданным кодам, а не по исходным индексам
				if ( $content_type == self::AUTO_CONTENT_TYPE ) {
					$content_type = self::fetchProductionType ( $csv );
				}
				
				$elements = array (  );
				switch ( $content_type ) {
					case self::CLOTHES_CONTENT_TYPE:
						$this -> next_step = 'rabbit.import';
						$csvMeta = CsvMetadata::createClothesMetadata ( $csv -> headers (  ) );	//clothes
						$elements = $this -> does_production ( $csvMeta, $product_variant_def, $csv );
						// Струкрурные ошибки можно найти только в завершенном списке продукции
						$this -> structuralErrors = array_merge ( $this -> structuralErrors, $csvMeta -> checkStructural ( $elements ) );
						$this -> check_status = max ( CellCsvError::worstErrorStatus ( $this -> cellErrors ), StructuralError::worstErrorStatus ( $this -> structuralErrors ) );
						break;
					case self::FABRICS_CONTENT_TYPE:
						$this -> next_step = 'rabbit.import';
						$csvMeta = CsvMetadata::createFabricsMetadata ( $csv -> headers (  ) );
						break;
					case self::TEXTILE_CONTENT_TYPE:
						$this -> next_step = 'rabbit.import';
						$csvMeta = CsvMetadata::createTextileMetadata ( $csv -> headers (  ) );
						break;
					case self::TEST1_CONTENT_TYPE:
						$this -> next_step = 'rabbit.import';
						$csvMeta = CsvMetadata::createTest1Metadata ( $csv -> headers (  ) );
						break;
					case self::TEST2_CONTENT_TYPE:
						$this -> next_step = 'rabbit.import';
						$csvMeta = CsvMetadata::createTest2Metadata ( $csv -> headers (  ) );
						break;
					case self::SALES_CONTENT_TYPE:
						$this -> next_step = 'rabbit.importsales';
						$csvMeta = CsvMetadata::createSalesMetadata ( $csv -> headers (  ) );
						$elements = $this -> does_sales ( $csvMeta, $csv );
						$this -> check_status = max ( CellCsvError::worstErrorStatus ( $this -> cellErrors ), StructuralError::worstErrorStatus ( $this -> structuralErrors ) );
						break;
					case self::ORDERS_CONTENT_TYPE:
						$this -> next_step = 'rabbit.importorders';
						break;
					case self::USERS_CONTENT_TYPE:
						$this -> next_step = 'rabbit.importusers';
						break;
					default:
						throw new Exception ( "Unknown production type: " . $content_type );
				}
				
				$this -> importData ['data'] = $elements;
				$this -> importData ['meta'] = $csvMeta -> getMeta (  );
				$this -> importData ['content_type'] = $content_type;
				$this -> csv = $csv;
				
			} else {
				$this -> check_status = 3;
			}
		} catch (Exception $e) {
			echo "{$e -> getMessage (  )} <br/>";
			echo $e -> getTraceAsString (  );
		}
		
		// В зависимости от результатов проверки устанавливаем лайот и передаём в него ошибки/данные импорта
		switch ( $this -> check_status ) {
			case 3:
				//$this -> message = "No input file, bleat!";
				$this -> setLayout ( "error" );
				break;
			case 2:
				$this -> setLayout ( "error" );
				break;
			case 1:
				$this -> setLayout ( "warning" );
				//break;
			case 0:
				// @QUESTION: Нужно ли сохранять в сессию?
				RabbitHelper::save_variable ( 'import_data', $this -> importData );
				//$testImportData = RabbitHelper::restore_variable ( 'import_data' ); Для отладки - слишком большие данные не сохраняются в сессии
				break;
			default:
				JError::raiseError ( 500, "Unknown import check_status: " . $this -> check_status );
				return false;
		}
 
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode('<br />', $errors));
 
			return false;
		}
 
		$this->addToolBar();

		parent::display($tpl);
	}

	protected function addToolBar()
	{
		JToolBarHelper::title($title, 'check');
		
		switch ( $this -> check_status ) {
			case 3:
			case 2:
				JToolBarHelper::custom('rabbit', null, null, "CANCEL [new import]", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT[finish import]", false);
				break;
			case 1:
				JToolBarHelper::custom ( $this -> next_step, null, null, "IGNORE & CONTINUE [import]", false);
				JToolBarHelper::custom('rabbit', null, null, "CANCEL [new import]", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT [finish import]", false);
				break;
			case 0:
				JToolBarHelper::custom ( $this -> next_step, null, null, "CONTINUE [import]", false);
				JToolBarHelper::custom('rabbit', null, null, "CANCEL [new import]", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT [finish import]", false);
				break;
			default:
				return false;
		}
	}

	protected function checkImages ( $base, $resized, & $diff ) {
			
		if ( empty ( $resized ) || ! is_array ( $resized ) ) {
			return false;
		}	
		
		$diff = array_diff ( $base, $resized );
		if ( ! empty ( $diff ) ) {
			return false;
		}
		
		return true;
	}
	
	/*		Определяет тип продукции в таблице, то есть перечень ожидаемых полей
	
		Используется в случае если тип не указан в форме ввода. Определять, видимо, следует по именам категорий
	*/
	protected static function fetchProductionType ( $csv ) {
		
		return self::CLOTHES_CONTENT_TYPE;
	}

	protected static function fetchProductVariantProperties ( $csv ) {
		
		return self::SKUPARSING_1_PRODUCT_VARIANT_DEF;
	}
	
	/*		Функции для определения вариаций товара
	
		Следует использовать если стандартный парсинг артикула не применим
		Например: список артикулов/шаблон - функция, список категорий/шаблон - функция, все - функция, не определять
	*/
	protected static function create_product_variant_getter ( $def ) {
		
		if ( $def == self::AUTO_PRODUCT_VARIANT_DEF ) {		// Автоматическое определение способа парсинга
			$def = self::fetchProductVariantProperties ( $csv );
		}
		
		switch ( $def ) {
			case self::SKUPARSING_1_PRODUCT_VARIANT_DEF:	// Парсинг артикула
				$getter = function ( $row ) {
					$property = $row [ 'sku' ];
					$parts =  explode ( '/', $property );
					
					if ( count ( $parts ) != 3 ) {
						throw new Exception ( 'Sku parsing error' . " [$property]" );
					}
					
					return array ( '(code)' => $parts [0], '(size)' => $parts [1], '(color)' => $parts [2] );					
				};
				break;
				
			case self::SKUPARSING_2_PRODUCT_VARIANT_DEF:	// Парсинг артикула
				$getter = function ( $row ) {
					
				};
				break;
			case self::SKUPARSING_3_PRODUCT_VARIANT_DEF:	// Парсинг артикула
				$getter = function ( $row ) {
					
				};
				break;
			case self::SKULIST_PRODUCT_VARIANT_DEF:	// Определение по артикулу
				$getter = function ( $row ) {
					
				};
				break;
			case self::CATEGOEYLIST_PRODUCT_VARIANT_DEF:	// Определение по категории
				$getter = function ( $row ) {
					
				};
				break;
			case self::ANOTHERFIELDS_PRODUCT_VARIANT_DEF:	// Определение по другим полям 
				$getter = function ( $row ) {
					
				};
				break;
			case self::ADDITIONALFIELDS_PRODUCT_VARIANT_DEF:	// Явно указанные варианты в дополнительных полях
				$getter = function ( $row ) {
					return array (  );
				};
				break;
			default:
				throw new Exception ( "Unknown product varian def: " . $def );
		}
		
		return $getter;
		
	}
	
	/*
	
		@PROBLEM: Выдрано из кода. Большое количество аргументов обусловлено именно этим. Коряво и уёбищно.
	*/
	protected function does_production ( $csvMeta, $product_variant_def, $csv ) {
		
		$elements = new ProductGroup (  );
		
		/*		Устанавливаем функцию вариаций для продукции

			Функция вариаций возвращает набор свойств товара (товар - это то что пользователь видит в категории) определяющих варианты товара (варианты - то что пользователь должен выбрать при покупке). На текущий момент это цвет и размер
		
			По умолчанию функции используют артикул для определения вариаций. Формат артикула отличается для разных категорий, поэтому и функции отличаются и определены для различных типов товаров в статических фабричных методах CsvMetadata. Для некоторых товаров могут потребоваться специальные способы определения вариаций. Соответствующие параметры предусмотрены в первичной форме импорта.
			
			@TODO: Сделать вменяемый и безопасный выбор специальных функций вариаций и списков продукции. Шаблон РВ? Имя Поля? Передавать функцию? Список предустановленных? Функции create_product_variant_getter, fetchProductVariantProperties, языковые константы и первичная форма.
		*/
		// По умолчанию используем функции вариаций, определенные в метаданных. 
		if ( $product_variant_def == self::DEFAULT_PRODUCT_VARIANT_DEF ) {
			$getProductVariantProperties =
			function ( $normalizedAssocRow ) use ( $csvMeta ) {
				return $csvMeta -> getProductVariantProperties ( $normalizedAssocRow );
			};
		// Явное указание использовать дополнительные функции вариаций
		} else { 
			$getProductVariantProperties = self::create_product_variant_getter ( $product_variant_def );
		}
		
		foreach ( $csv -> data (  ) as $rowIndex => $row ) {	// Каждая строка исходных данных
			
			// Проверяем каждую ячейку регулярным выражением
			// checkCells возвращает массив, поскольку в строке может быть несколько ошибок. Сливаем его с существующим
			$errors = $csvMeta -> checkCells ( $row, $rowIndex );
			if ( ! empty ( $errors ) ) {
				$this -> cellErrors = array_merge ( $this -> cellErrors, $errors );	
				// @PROBLEM: We should catch critical errors like empty sku ... getWorst if >= CRITICAL
			}

			// Формируем ассоциативный массив и фиксируем ошибку если не удалось
			$assocRow = $csvMeta -> createAssoc ( $row );
			if ( empty ( $assocRow ) ) {
				$this -> structuralErrors [] = new StructuralError ( array ( $rowIndex ), '', "Couldn`t create assoc row from csv", 2 );
				continue;
			}
			
			
			// @QUESTION: where should we catch errors like 'missing sku'? As critical error in cellErrors?
			// Получаем свойства, определяющие разновидности одного товара
			$productVariantProperties = $getProductVariantProperties ( $assocRow );//code=> color=> size=>
			
			if ( ! $productVariantProperties ) {
				$this -> cellErrors [] = new CellCsvError ( $rowIndex, 'sku', $assocRow ['sku'], 'Couldn`t parse sku', 2 );
				continue;
			}

			// @QUESTION: Нужно ли сохранять ассоциативные массивы или они больше не понадобятся?
			// @ATTENTION: В array_merge при совпадающих строковых ключах важна последовательность аргументов
			// Формируем структуру данных для дальнейшего импорта
			$elements -> add ( new ProductData ( $rowIndex, array_merge ( $assocRow, $productVariantProperties ) ) );
			
		}
		
		return $elements;
	}

	protected function does_sales ( $csvMeta, $csv ) {
		
		$sales = array (  );
		
		foreach ( $csv -> data (  ) as $rowIndex => $row ) {

			// Проверяем каждую ячейку регулярным выражением
			// checkCells возвращает массив, поскольку в строке может быть несколько ошибок. Сливаем его с существующим
			$errors = $csvMeta -> checkCells ( $row, $rowIndex );
			if ( ! empty ( $errors ) ) {
				$this -> cellErrors = array_merge ( $this -> cellErrors, $errors );	
				// @PROBLEM: We should catch critical errors like empty sku ... getWorst if >= CRITICAL
			}

			// Формируем ассоциативный массив и фиксируем ошибку если не удалось
			$assocRow = $csvMeta -> createAssoc ( $row );
			if ( empty ( $assocRow ) ) {
				$this -> structuralErrors [] = new StructuralError ( array ( $rowIndex ), '', "Couldn`t create assoc row from csv", 2 );
				continue;
			}
			if ( empty ( $assocRow ['sku'] ) && empty ( $assocRow ['category'] ) ) {
				$this -> structuralErrors [] = new StructuralError ( array ( $rowIndex ), '', "Both sku and category are empty while sales checking", 2 );
				continue;
			}
			
			$sales [] = $assocRow;
			//$sales -> add ( new ProductData ( $rowIndex, $assocRow ) );
		}
		
		return $sales;
	}
}