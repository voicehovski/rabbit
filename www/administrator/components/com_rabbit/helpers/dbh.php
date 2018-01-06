<?php

defined('_JEXEC') or die('Restricted access');


class DBHelper {
	
	//@TODO: Сделать преффиксы посложнее - на случай нестандартных размеров/цветов
	static $config = array (
		'localized-slug' => true,
		'default-locale' => 'uk_ua',
		'default-charset' => 'UTF-8',
		
		'product-image-path' => 'images/virtuemart/product/',
		
		'multiproduct-cf-name' => 'multi',
		'multiproduct-cf-paramcf-name' => 'CF-1',
		'multiproduct-cf-paramcf-value_color' => 'CF_COLOR',
		'multiproduct-cf-paramcf-value_size' => 'CF_SIZE',
		
		'size-cfvalue-preffix' => 's_',
		'color-cfvalue-preffix' => 'c_'
	);
	
	protected static $multiproduct_cf_id = null;
	protected static $multiproduct_cf_paramcf_id = null;
	
	//@TESTED 20:00 5.01.2018, RENAMED
	protected static function get_multiproduct_cf_id (  ) {
		if ( self::$multiproduct_cf_id === null ) {
			self::$multiproduct_cf_id = self::getPropertyId ( self::$config ['multiproduct-cf-name'] );
		}
		
		if ( self::$multiproduct_cf_id === null ) {
			throw new Exception ( "Customfield " . self::$config ['multiproduct-cf-name'] . " is missing<br/>" );
		}
		
		return self::$multiproduct_cf_id;
	}
	//@TESTED 20:00 5.01.2018, RENAMED
	protected static function get_multiproduct_cf_paramcf_id (  ) {
		if ( self::$multiproduct_cf_paramcf_id === null ) {
			self::$multiproduct_cf_paramcf_id = self::getPropertyId ( self::$config ['multiproduct-cf-paramcf-name'] );
		}
		
		if ( self::$multiproduct_cf_paramcf_id === null ) {
			throw new Exception ( "Customfield " . self::$config ['multiproduct-cf-paramcf-name'] . " is missing<br/>" );
		}
		
		return self::$multiproduct_cf_paramcf_id;
	}
	
	public static function import ( $import_data ) {
		
		//		==	Test block	==
			//self::test ( $import_data );
			//return;
		//		==	End of test block	==
		
		// $product = new Product ( $raw_product, $import_data ['meta'] );
		
		foreach ( $import_data ['data'] -> groupBy ( 'code' ) as $product_group ) {
			
			if ( $product_group -> isEmpty (  ) ) {
				throw new Exception ( "Empty result of groupBy ( 'code' ) method" );
			}
			
			// Извлекаем все продукты группы, помеченные маркером главного
			$main_product_group = $product_group -> where ( function ( $p ) { $t = $p -> get ( 'main' ); return ! empty ( $t ); } );
			
			/*		Нормализация главного продукта
				Отметка может быть на любом продукте группы - мы все равно делаем отдельный основной продукт без размера
				Отметка может быть любой непустой строкой, в том числе - несколько пробелов, что не совсем удобно
				@TODO: исключить распознавание нескольких прбелов как отметки
				Если главный продукт не отмечен, выбираем первый в группе, если отмечено несколько, выбираем первыйи из отмеченных
			*/
			if ( $main_product_group -> size (  ) == 1 ) {
				// @NOTE: Разыменование результата функции ($x = $main_product_group -> getAll (  ) [0];) Работает в php 5.4 <=. В предыдущих версиях нужно использовать промежуточную переменную или list
				list ( $main_product_raw ) = $main_product_group -> getAll (  );
			} else if ( $main_product_group -> isEmpty (  ) ) {
				list ( $main_product_raw ) = $product_group -> getAll (  );
			} else {
				// @TODO: Make warning
				list ( $main_product_raw ) = $main_product_group -> getAll (  );
			}
			
			// Создаём / обновляем продукт, категории, изображения и значения строковых КФ и ККФ. Также можно создать пустое значение мульти-КФ
			// Это "служебный" продукт с суррогатным артикулом. Все продукты которые реально можно заказать будут в дочерних
			$main_product = new Product ( $main_product_raw, $import_data ['meta'] );
			$main_product -> identifier (  $main_product_raw -> get ( 'code' ) );
			$main_product_id = self::process_product ( $main_product );
			self::process_images ( $main_product, $main_product_id );
			self::process_multiproduct_cf_params ( $main_product_id );
			self::process_properties ( $main_product, $main_product_id );
			// self::process_localized_properties ( $main_product, $main_product_id );
			self::process_categories ( $main_product, $main_product_id );
			
			// Создаём / обновляем дочерние товары, изображения (существующие только подвязываем к товару, не пересоздаём), КФ и ККФ (определяющие варианты КФ берем из конфигурации). Категории не нужны
			// Все товары, включая одно-цветно-размерные будут иметь дочерние: условный основной и реальные дочерние
			foreach ( $product_group -> getAll (  ) as $product_variant_raw ) {
				$product_variant = new Product ( $product_variant_raw, $import_data ['meta'] );
				$product_variant_id = self::process_product ( $product_variant, $main_product_id );
				self::process_images ( $product_variant, $product_variant_id );
				// @TODO: Здесь нужно, в том числе, записать в КФ дочернего продукта вспомогательные текстовые поля для мульти. ИД поля - в конфиге. Значения можно брать прямо из таблицы, только маркировать, например "size[42], color[486]".
				self::process_properties ( $product_variant, $product_variant_id );
				self::process_specific_product_variant_cf_values ( $product_variant, $product_variant_id );
			}
			
			// Делаем дочерние продукты, созданные выше, мультипродуктами основного, а именно модифицируем поле customfield_params таблицы virtuemart_product_customfields. Значения берем из базы - так намного проще чем сохранять в коде
			self::process_main_product_variants ( $main_product, $main_product_id );
			
		}
		
	}
	
	//@TESTED 20:20 5.01.2018, RENAMED
	protected static function process_multiproduct_cf_params ( $product_id ) {
		
		$property_id = self::get_multiproduct_cf_id (  );
		
		// Такая запись сохраняется и после сохранения товара через админпанель. Видимо заполнять поля списками допустимых значений не обязательно
		$p1 = 'selectoptions=[{"voption":"clabels","clabel":"'.self::$config ['multiproduct-cf-paramcf-value_color'].'","values":""},{"voption":"clabels","clabel":"'.self::$config ['multiproduct-cf-paramcf-value_size'].'","values":""}]|';
		
		$p2 = 'options={"11":["0","\u0421\u0443\u043a\u043d\u044f \u201c\u041e\u043a\u0441\u0430\u043c\u0438\u0442\u201d \u0442\u0435\u043c\u043d\u043e-\u0441\u0438\u043d\u044f"]}|';
		
		$params = self::getProductPropertyFieldValues_bis ( $product_id, $property_id, $field_name = 'customfield_params' );
		
		if ( empty ( $params ) ) {
			self::addProductPropertyValue ( $product_id, $property_id, null, array ( 'customfield_params' => $p1, 'customfield_price' => '0' ) );
		} else if ( count ( $params ) == 1 ) {
			// Only if not match...
			// @PROBLEM: Так нельзя! Это плохо! virtuemart_customfield_id, уходи!
			self::updateProductPropertyValue ( $params [0]['virtuemart_customfield_id'], array ( 'customfield_params' => $p1 ) );
		} else {
			throw new Exception ( "More then one multivariant value. Product id: $product_id Property id: $property_id" );
		}
	}
	
	protected static function process_product ( $product, $parent_id = 0 ) {
		
		$product_id = self::getProductId ( $product -> identifier (  ) );

		if ( $product_id === null ) {
			$product_id = self::createProduct ( $product, $parent_id );
			if ( $product_id === null ) {
				throw new Exception ( 'Couldn`t create product. Sku: ' . $product -> getDebugInfo (  ) );
			}
			if ( ! self::createProductLocalization ( $product_id, $product, self::$config ['default-locale'] ) ) {
				throw new Exception ( 'Couldn`t create product localization. Sku: ' . $product -> getDebugInfo (  ) );
			}
		} else {
			if ( ! self::updateProduct ( $product_id, $product, $parent_id ) ){
				throw new Exception ( 'Couldn`t update product. Sku: ' . $product -> getDebugInfo (  ) );
			}
			if ( self::productLocalizationExists ( $product_id, self::$config ['default-locale'] ) ) {	
				if ( ! self::updateProductLocalization ( $product_id, $product, self::$config ['default-locale'] ) ) {
					throw new Exception ( 'Couldn`t update product localization. Sku: ' . $product -> getDebugInfo (  ) );
				}
			} else {
				// @TODO: Warning, product without localization
				if ( ! self::createProductLocalization ( $product_id, $product, self::$config ['default-locale'] ) ) {
					throw new Exception ( 'Couldn`t create product localization for existing product. Sku: ' . $product -> getDebugInfo (  ) );
				}
			}
		}
		
		return $product_id;
	}

	/*		Импорт изображений
	
		Миниатюры волшебным образом создаются автоматически в подкаталоге resized. Но в таблицу автоматически не заносятся, а я тоже пока не заношу
	*/
	//@LIGHT-TESTED 14:55 06.12.2018
	protected static function process_images ( $product, $product_id ) {
		$current_image_id_list = self::getProductImages ( $product_id );
		echo ("current images of $product_id<br/>");
		print_r ( $current_image_id_list );
		foreach ( $product -> images (  ) as $image ) {
			
			$image_id = self::getImageId ( self::$config ['product-image-path'] . $image -> identifier (  ) );	// В качестве идентификатора имя изображения. Правилно ли?
			
			if ( $image_id === null ) {
				
				// @TODO: Check image title
				$image_title = $image -> identifier (  );
				
				$image_id = self::createImage ( $image_title, self::$config ['product-image-path'] . $image -> identifier (  ) );
				if ( $image_id === null ) {
					throw new Exception ( "Couldn`t create image {$image -> getDebugInfo (  )}" );
				}
			}
			
			$k = array_search ( $image_id, $current_image_id_list );
			if ( $k !== false ) {
				unset ( $current_image_id_list [$k] );
			} else {
				// @QUESTION: ordering?
				if ( ! self::bindImage ( $product_id, $image_id ) ) {
					throw new Exception ( "Couldn`t bind image: {$image -> getDebugInfo (  )}. Product: {$product -> getDebugInfo (  )}" );
				}
			}
		}
		echo ("2 current images of $product_id<br/>");
		print_r ( $current_image_id_list );
		foreach ( $current_image_id_list as $id => $value ) {
			if ( ! self::unbindImage ( $product_id, $value ) ) {
				throw new Exception ( "Couldn`t bind image: {$image -> getDebugInfo (  )}. Product: {$product -> getDebugInfo (  )}" );
			}
		}
		
	}
	
	/*		Импорт классических КФ
	
		Это не локализуемые КФ и не вариант-КФ. То есть, по сути, различные скрытые признаки или аттрибуты корзины с возможностью модификации цены. Локализовать их можно, но вручную, поиск по ним затруднен, так что их не следует делать много, работу с ними не следует делать частой, поскольку она предполагает ручное вмешательство в языковые файлы
		
		В метаданных имеют type = 1
	*/
	//@NEED_TEST
	protected static function process_properties ( $product, $product_id ) {
		
		foreach ( $product -> properties (  ) as $imported_property ) {
			
			$property_id = self::getPropertyId ( $imported_property -> identifier (  ) );	//custom_title, уникальность под вопросом
			
			// Создание нового КФ - не штатная ситуация, требующая обсуждения. Поэтому КФ будем создавать вручную, а обнаружениие в таблице нового КФ считать ошибкой. Кроме того различные типы КФ имеют свои наборы параметров, что усложняет их автоматическое создание
			if ( $property_id === null ) {
				throw new Exception ( "Unknown custom: " . $imported_property -> identifier (  ) );
				//$property_id = self::createProperty ( $imported_property -> identifier (  ) );
				//if ( $property_id === null ) {
				//	throw new Exception ( "Couldn`t create property: {$imported_property -> getDebugInfo (  )}" );
				//}
			}
			
			// Получаем перечень значений свойства ИД => значение для текущего продукта
			// Здесь только строковоие значения без параметров
			$value_list_db = self::unsophisticate_assoc ( self::getProductPropertyFieldValues ( $product_id, $property_id ), 'customfield_value' );
			foreach ( $imported_property -> value (  ) as $imported_value ) {
				// @NOTE: Возвращает ключ первого найденного значения. Регистрозависима. Для поиска нескольких значений: array array_keys ( $array, $imported_value ) Тоже регистрозависима
				$k = array_search ( $imported_value, $value_list_db );
				if ( $k !== false ) {
					unset ( $value_list_db [$k] );
				} else {
					if ( ! self::addProductPropertyValue ( $product_id, $property_id, $imported_value ) ) {
						throw new Exception ( "Couldn`t bind property value: {$imported_property -> getDebugInfo (  )}. Product: {$product -> getDebugInfo (  )}" );
					}
				}
			}
			
			// Удаляем лишние значения из базы
			foreach ( $value_list_db as $id => $value ) {
				self::removeProductPropertyValue ( $id );
			}
		}		
	}

	/*		Импорт локализуемых КФ
	
		Это КФ, сохраняемые в отдельных ТБД, пригодные для поиска, фильтрации и импорта
	*/
	//@NEED_TEST
	protected static function process_localized_properties ( $product, $product_id ) {
		
		foreach ( $product -> localizedProperties (  ) as $lp ) {
			
			$lp_id = self::getLocalizedPropertyId ( $lp -> identifier (  ) );
			if ( ! $lp_id ) {
				$lp_id = self::createLocalizedProperty ( $lp -> identifier (  ) );	//$lang
				if ( ! $lp_id ) {
					throw new Exception ( "Couldn`t create localized property: {$lp -> getDebugInfo (  )}. Product: {$product -> getDebugInfo (  )}" );
				}
				// @QUESTION: Делаем локализацию имен свойств здесь? Или потом вручную - их все равно не много и добавляться будут редко?
				/* 
				if ( ! self::createLPLocalization ( $lp_id, LOCALIZED_LP_TITILE ,$config ['default-locale'] ) ) {
					throw new Exception ( "Couldn`t localize localized property: {$lp -> getDebugInfo (  )}. Product: {$product -> getDebugInfo (  )}" );
				}
				*/
				
			}
			
			$current_lp_values = self::unsophisticate_assoc ( self::getProductLPValues ( $product_id, $lp_id, self::$config ['default-locale'] ), 'ccf_value' );
			
			foreach ( $lp -> values (  ) as $value ) {
				$k = array_search ( $value, $current_lp_values );
				if ( $k !== false ) {
					unset ( $current_lp_values [$k] );
				} else {
					if ( ! self::addProductLPValue ( $product_id, $lp_id, self::$config ['default-locale'], $value ) ) {
						throw new Exception ( "Couldn`t bind localized property value: {$lp -> getDebugInfo (  )}. Product: {$product -> getDebugInfo (  )}" );
					}
				}
			}
			
			foreach ( $current_lp_values as $key => $v ) {
				self::removeProductLPValue ( $key );
			}
		}		
	}
	
	/*		Создаёт вспомогательные значения КФ для дочерних товаров мультиварианта
	
		КФ, в котором хранятся вспомогательные значения (ВЗМ) задаётся при создании КФ-мультиварианта. Это имя сохранено в $config. В этом КФ хранятся ВСЕ ВЗМ товара, поэтому я снабдил их преффиксами (также содержатся в $config)
		
		@WARNING: обновления уместны если мы меняем размер или цвет в базе вобход системы или если размер и цвет указаны не стандартным смособом - отдельно от артикула. Если же цвет/размер указаны в артикуле, то с изменением мы просто имеем другой товар
	*/
	//@TESTED 13:37 05.12.2018, RENAMED
	protected static function process_specific_product_variant_cf_values ( $product, $product_id ) {
		
		//@TODO: Можно вынести в глобал
		$multi_param_cf_id = self::getPropertyId ( self::$config ['multiproduct-cf-paramcf-name'] );
		// Извлекаем текущие вариант-свойства продукта из ТБД 
		$variant_properties = self::get_specific_product_variant_cf_values ( $product_id, $multi_param_cf_id );	// {id=>value,...} from db
		// Извлекаем новые вариант-свойства продукта из таблицы импорта
		$import_variant_properties = $product -> variantProperties (  );
		
		// Проверяем валидность значений текущих вариант-свойств. Они должны начинающиеся с соответствующих преффиксов и не повторяться
		//@TODO: Если значение не соответствует шаблону, то есть не начинается с преффикса, нужно что-то длать - удалить, выдать предупреждение
		$sizes_vp = self::elem_starts_with ( self::$config ['size-cfvalue-preffix'], $variant_properties );
		// Если повторяется - выдать предупреждение и ...
		if ( count ( $sizes_vp ) > 1 ) {
			throw new Exception ( "Several same variant properties. Product id: $product_id. Property id: $multi_param_cf_id"  );
		}
		// Если вариант-свойств в текущем продукте нет - создать
		if ( empty ( $sizes_vp ) ) {
			self::addProductPropertyValue (
				$product_id,
				$multi_param_cf_id,
				self::$config ['size-cfvalue-preffix'] . $import_variant_properties ['size'],
				array ( 'customfield_params' => '', 'customfield_price' => '0' )
			);
		} else {
			// Если одно и совпадает и совпадает по значению с новым - обновление не нужно, следующий цикл
			if ( strcmp ( $sizes_vp [0]['value'], self::$config ['size-cfvalue-preffix'] . $import_variant_properties ['size'] ) === 0 ) {
				goto color;
			// Если не совпадает - обновить
			} else {
				self::updateProductPropertyValue (
					$sizes_vp [0]['key'],
					array ( 'customfield_value' => self::$config ['size-cfvalue-preffix'] . $import_variant_properties ['size'] )
				);
			}
		}
		
		color:

		$colors_vp = self::elem_starts_with ( self::$config ['color-cfvalue-preffix'], $variant_properties );
		
		if ( count ( $colors_vp ) > 1 ) {
			throw new Exception ( "Several same variant properties. Product id: $product_id. Property id: $multi_param_cf_id"  );
		}
		
		if ( empty ( $colors_vp ) ) {
			self::addProductPropertyValue (
				$product_id,
				$multi_param_cf_id,
				self::$config ['color-cfvalue-preffix'] . $import_variant_properties ['color'],
				array ( 'customfield_params' => '', 'customfield_price' => '0' )
			);
		} else {
			if ( strcmp ( $colors_vp [0]['value'], self::$config ['color-cfvalue-preffix'] . $import_variant_properties ['color'] ) === 0 ) {
				goto finish;
			} else {
				self::updateProductPropertyValue (
					$colors_vp [0]['key'],
					array ( 'customfield_value' => self::$config ['color-cfvalue-preffix'] . $import_variant_properties ['color'] )
				);
			}
		}
		
		finish:
	}
	
	/*		Ищет в массиве $list элементы, начинающиеся с $preffix. Возвращает подмассив [{key=>i, value=>v},...]
	
		Если удовлетворяющих элементов нет, возвращает пустой массив. Регистрозависима. Рабоотает с mb-кодировками. Значения КОПИРУЮТСЯ в новый массив, так что исходный не зависит от изменений в новом. Исходный массив должен содержать текстовые строки. 
	*/
	//@TESTED
	protected static function elem_starts_with ( $preffix, $list ) {
		$sublist = array (  );
		foreach ( $list as $k => $value ) {
			if ( mb_strpos ( $value, $preffix, 0, self::$config ['default-charset'] ) === 0 ) {
				$sublist [] = array ( 'key' => $k ,'value' => $value );
			}
		}
		return $sublist;
	}
	
	/*		Возвращает массив {virtuemart_customfield_id=>customfield_value,...}  из ТБД для указанных $product_id и $property_id
	
		По сути просто обертка и может быть инлайнена
		
		@EXCEPTIONS:
		* Если такой записи нет, вернет пустой массив
	*/
	//@TESTED, RENAMED
	protected static function get_specific_product_variant_cf_values ( $product_id, $property_id ) {
		return self::unsophisticate_assoc ( ( self::getProductPropertyFieldValues ( $product_id, $property_id ) ), 'customfield_value' );
	}
	
	/*		Регистрирует дочерние продукты-варианты в основном продукте
		
		Записывает ИД и специальные параметры вариантов в поле customfield_params таблицы _virtuemart_product_customfields для основного продукта
		
		Обновление и удаление вариантов. Никаких особых действий не требуется - options просто обновляется в соответствии с текущим набором дочерних продуктов-вариантов.
		
		Важно! В списке дочерних продуктов get_product_varialnt_list должны быть только активные (published) товары.
	*/
	//@LIGHT-TESTED 12:55 06.12.2018
	protected static function process_main_product_variants ( $product, $id ) {

		//@TODO: Можно вынести в глобал
		$multi_cf_id = self::getPropertyId ( self::$config ['multiproduct-cf-name'] );
		$multi_param_cf_id = self::getPropertyId ( self::$config ['multiproduct-cf-paramcf-name'] );
	
		// Поле customfield_params на этом этапе уже должно быть заполненным. Продукт должен быть парентом
		$cf_params_string = self::get_multicf_param_string ( $id, $multi_cf_id );
		$params_string = $cf_params_string ['pstring'];
		if ( empty ( $params_string ) ) {
			throw new Exception ( "Empty param string. Product id: [$id]. CF id: [$multi_cf_id]" );
		}
		
		// $params [0] должен содержать формат опций (selectoptions=...). Если нет - это ошибка. Если будем сверяться с этим форматом, можно преобразовать во чтото типа [label=>[label_vales]] для удобства
		// $params [1] должен содержать сами опции (options=...) Исходные значения options вобщем-то можно и отбросить - мы все равно их перезаписываем. Но можно и выполнить проверку корректности или сравнение с новыми данными
		$params = explode ( "|", $params_string );
		if ( ! is_array ( $params ) || ! isset ( $params [0] ) || ! isset ( $params [1] ) ) {
			throw new Exception ( "Incorrect param string. Product id: [$id]. CF id: [$multi_cf_id]. Param string: $params_string" );
		}
		
		// Это не обязательно - все равно мы обновляем запись в БД полностью
		$options = self::unserializeMultiCustomParams ( $params [1] );	//product_id => [customfield_value_1, customfield_value_2]
		
		// Заполняем опции для родительского товара - он у нас без цвета и размера
		$new_options [ $id ] = array ( "0", "0" );
		// Получаем продукты-варианты, дочерние для $id и содержащие КФ $multi_param_cf_id вместе со значениями этих КФ
		foreach ( self::get_product_varialnt_list ( $id, $multi_param_cf_id ) as $child ) {
			
			if ( mb_strpos ( $child ['customfield_value'], self::$config ['color-cfvalue-preffix'], 0, self::$config ['default-charset'] ) === 0 ) {
				$new_options [ $child ['virtuemart_product_id'] ][0] = $child ['customfield_value'];
			} else if ( mb_strpos ( $child ['customfield_value'], self::$config ['size-cfvalue-preffix'], 0, self::$config ['default-charset'] ) === 0 ) {
				$new_options [ $child ['virtuemart_product_id'] ][1] = $child ['customfield_value'];
			} else {
				// warning: unexpected CF value format
			}
		}
		
		$new_params_string =
			$params [0] . "|" .
			self::serializeMultiCustomParams ( $new_options ) . "|";

		self::update_multicf ( $cf_params_string ['id'], /*$id, */$new_params_string );
	}

	function get_multicf_param_string ( $product_id, $cf_id ) {

		$params = self::getProductPropertyFieldValues ( $product_id, $cf_id, 'customfield_params' );
		
		if ( empty ( $params ) ) {
			throw new Exception ( "Missing param field. Product id: [$product_id]. CF id: [$cf_id]" );
		}
		
		$cnt = 0;
		$result = array (  );
		foreach ( $params as $k => $v ) {
			if ( $cnt++ > 0 ) {
				throw new Exception ( "More then one multicf. Product id: [$product_id]. CF id: [$cf_id]" );
			}
			$result ['id'] = $k;
			$result ['pstring'] = $v ['customfield_params'];
		}
		
		return $result;
	}

	function serializeMultiCustomParams ( $params_struct ) {
		
		$string_params = "options={";
		
		foreach ( $params_struct as $child_id => $cf_value_list ) {
			$string_params .= "\"$child_id\":[\"{$cf_value_list[0]}\",\"{$cf_value_list[1]}\"],";
		}
		
		return substr ( $string_params, 0, -1 ) . "}";
	}
		
	function unserializeMultiCustomParams ( $params_string ) {
		/*
		$param_string = substr ( $param_string, 9, -2 );	// Обрезаем options={ вначале и ]} вконце
		// options={"43":["0","0"],"44":["CF_COLOR_RED","CF_SIZE_42"]}
		$parts = explode ( '],', $params_string );
		foreach ( $parts as $p ) {
			
		}
		*/
	}
	
	function get_product_varialnt_list ( $parent_id, $param_cf_id ) {
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );

		$query -> select ( $db -> quoteName ( 'p.virtuemart_product_id' ) );
		$query -> select ( $db -> quoteName ( 'cf.customfield_value' ) );
		$query -> from ( $db -> quoteName ( '#__virtuemart_products', 'p' ) );
		$query -> join ( 'INNER', $db -> quoteName ( '#__virtuemart_product_customfields', 'cf' ) . ' ON (' . $db -> quoteName ( 'p.virtuemart_product_id' ) . ' = ' . $db -> quoteName ( 'cf.virtuemart_product_id' ) . ')' );
		$query -> where ( $db -> quoteName ( 'p.product_parent_id' ) . ' = ' . $parent_id );
		$query -> where ( $db -> quoteName ( 'virtuemart_custom_id' ) . ' = ' . $param_cf_id );

		$db -> setQuery ( $query );
	
		return $db -> loadAssocList (  );		
	}

	function update_multicf ( $customfield_id, /*$product_id,*/ $params_string ) {
		
		$user =& JFactory::getUser();
		$object = new stdClass();

		// Must be a valid primary key value.
		$object -> virtuemart_customfield_id = $customfield_id;
		//$object -> virtuemart_product_id = $product_id;
		$object -> customfield_params = $params_string;
		$object -> modified_on = JFactory::getDate (  ) -> toSql (  );
		$object -> modified_by = $user -> get ( 'id' );

		// return boolean
		return JFactory::getDbo (  ) -> updateObject ( '#__virtuemart_product_customfields', $object, 'virtuemart_customfield_id' );		
	}
	
	/*
	
	*/
	//@LIGHT-TESTED 13:45 06.12.2018
	protected static function process_categories ( $product, $product_id ) {

		$db = JFactory::getDbo (  );
		$current_category_id_list = self::getProductCategories ( $product_id );	//ids
		$current_category_id_list = empty ( $current_category_id_list ) ? array (  ) : $current_category_id_list;
		$categories = $product -> categories (  );	//objects
		
		foreach ( $categories as $category ) {
			
			$parent_id = 0;
			$category_id = 0;
			
			try {
				$db -> transactionStart (  );
				
				foreach ( $category -> path (  ) as $path_item ) {
					$category_id = self::getCategoryId ( $parent_id, $path_item, self::$config ['default-locale'] );
					
					if ( $category_id === null ) {
						// @TODO: Сделать обработку ошибок
						$category_id = self::createCategory ( $parent_id ); //value/null
						if ( $category_id === null ) {
							throw new Exception ( "Couldn`t create category. Category: $path_item of {$category -> getDebugInfo (  )}" );
						}
						if ( ! self::createCategoryLocalization ( $category_id, $path_item, self::$config ['default-locale'] ) ) {	//true/false
							throw new Exception ( "Couldn`t create category localization. Category: $path_item of {$category -> getDebugInfo (  )}" );
						}
					}
					
					$parent_id = $category_id;
				}
				// @IDEA: $imported_category_id_list [] = $category_id;
					
				$k = array_search ( $category_id, $current_category_id_list );
				if ( $k !== false ) {
					unset ( $current_category_id_list [$k] );
					continue;
				}
				
				if ( self::bindCategory ( $product_id, $category_id ) === false ) {
					throw new Exception ( "Couldn`t bind category. Category: {$category -> getDebugInfo (  )}. Product: {$product -> getDebugInfo (  )}" );
				}
				
				$db -> transactionCommit (  );
			} catch ( Exception $e ) {
				$db -> transactionRollback (  );
				JErrorPage::render ( $e );	// @QUESTION: watafaka is? Does this break da penetration?
				throw new Exception ( "Error while category processing. Category: $path_item of {$category -> getDebugInfo (  )}" );
			}
		}
		
		foreach ( $current_category_id_list as $cid ) {
			if ( self::unbindCategory ( $product_id, $cid ) === false ) {
				throw new Exception ( "Couldn`t unbind category. Category id: {$cid}. Product: {$product -> getDebugInfo (  )}" );
			}
		}
		// @IDEA: array_diff ( $current_category_id_list, $imported_category_id_list )	unbind
		// @IDEA: array_diff ( $imported_category_id_list ,$current_category_id_list )	bind
		
		
	}
		
//====================	Products	===========================

/*		Извлекает идентификатор продукта по артикулу

	@RETURN: идентификатор продкута или null если такой не найден. В случае обнаружения дублирующегося артикула выбрасывает исключение - нужно откатить базы и разобраться вручную
	
	@PROBLEMS:
	*
	
	@IDEAS:
	*
*/
	public static function getProductId ( $productSku ) {
		$s = new stdClass (  );
		$s -> column_name = 'virtuemart_product_id';
		$s -> table_name = '#__virtuemart_products';
		$s -> condition_column = 'product_sku';

		$results = self::get_id_ ( $productSku, $s );
		
		if ( $results ) {
			if ( count ( $results ) > 1 ) {
				throw new Exception ( "Several products with sku $productSku" );
			}
			return $results [0];
		} else {
			return null;
		}	
	}

/*		Создаёт новый продукт без локализации

	@RETURN: new id or null

	@TODO: Добавить остальные поля таблицы
*/
	public static function createProduct ( $product, $parent_id ) {
		$columns = array ( 'product_sku', 'product_parent_id', 'product_in_stock', 'published', 'created_on' ); 
		$values = array ( $product -> identifier (  ), $parent_id, 1, 1, JFactory::getDate (  ) -> toSql (  ) );

		return self::create_ ( $columns, $values, '#__virtuemart_products' );
	}

/*		Обновляет данные продукта

	@RETURN: ПРАВДУ если все хорошо

	@TODO: Добавить локализацию
*/
	public static function updateProduct ( $id, $product, $parent_id ) {
		
		$object = new stdClass();

		// Must be a valid primary key value.
		$object -> virtuemart_product_id = $id;
		$object -> product_parent_id = $parent_id;
		$object -> modified_on = JFactory::getDate (  ) -> toSql (  );
		if ( isset ( $product -> published ) ) {
			if ( empty ( $product -> published ) ) {
				$object -> published = 0;
			} else {
				$object -> published = 1;
			}
		}

		// Обновляемая таблица, данные и ключевое поле. Есть еще четвертый логический параметр говорящий как обновлять null-поля, но что это значит, пока не ясно - то ли обнулять то чего нет в данных, то ли не трогать то что и так null в базе
		return JFactory::getDbo (  ) -> updateObject ( '#__virtuemart_products', $object, 'virtuemart_product_id' );
	}

/*		Выполняет локализацию основных аттрибутов продукта (не пользовательских) на один язык

	@PURPOSE: 

	@ARGS: 

	@RETURN: true/false (если вставка оказалась неудачной)
	
	@PROBLEMS:
	* не выполняет проверку существования локали
	* Необходима обработка возврата нескольких ИД при проверке, что добавит громоздкости
	
	@FRATURES:
	* Если запись с таким $product_id уже существует, функция вернет ЛОЖЪ, что позволяет контролировать контроль
*/
	public static function createProductLocalization ( $product_id, $product, $locale ) {
		
		return JFactory::getDbo (  ) -> insertObject (
			"#__virtuemart_products_$locale",
			self::createProductLocalizationObject ( $product_id, $product )
		);
	}
	
	public static function updateProductLocalization ( $product_id, $product, $locale ) {
		
		$localization_object = self::createProductLocalizationObject ( $product_id, $product );
		//unset ( $localization_object -> slug );
		$return = JFactory::getDbo (  ) -> updateObject (
			"#__virtuemart_products_$locale",
			$localization_object,
			'virtuemart_product_id'
		);
		
		return $return;
	}

	public static function createProductLocalizationObject ( $product_id, $product ) {
		
		$lcl = new stdClass (  );
		
		$lcl -> virtuemart_product_id = $product_id;
		//$lcl -> product_s_desc = 
		$lcl -> product_desc = $product -> desc (  );
		$lcl -> product_name = $product -> name (  );
		// meta...	metadesc, metakey, customtitle
		$lcl -> slug = self::normalizeSlug ( $lcl -> product_name, ! self::$config ['localized-slug'] ) . "-$product_id";
		//$lcl -> slug = self::normalizeSlug ( $product -> identifier (  ), ! self::$config ['localized-slug'] );
		
		return $lcl;
	}
	
	public static function productLocalizationExists ( $product_id, $locale ) {
		
		$s = new stdClass (  );
		$s -> column_name = 'virtuemart_product_id';
		$s -> table_name = "#__virtuemart_products_$locale";
		$s -> condition_column = 'virtuemart_product_id';

		return self::get_id_ ( $product_id, $s );
	}

	
//====================	Categories	===========================

/*		Извлекает ИД категории по локализованному идентификатору (имени) и ИД родительской категории

	@RETURN: идентификатор категории или null если что-то не получилось
	
	@PROBLEMS:
	* не выполняет проверку существования локали
	* не учитывает ситуацию когда указанным в аргументах усливиям соответствует не солько значений, что свидетельствует об ошибке. Можно извлекать из базы массив вместо занчения или довериться, но сделать проверочную процедуру, запускаемую время от времени
*/
	public static function getCategoryId ( $parent_id, $identifier, $locale ) {
		
		$name = 'virtuemart_category_id';
		$table = "#__virtuemart_categories";
		$locale_table = "#__virtuemart_categories_$locale";
		$bind_table = "#__virtuemart_category_categories";
		$cond_1 = 'category_name';
		$cond_2 = 'category_parent_id';

		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );

		$query -> select ( $db -> quoteName ( "main.$name" ) );
		$query -> from ( $db -> quoteName ( $table, 'main' ) );
		$query -> join ( 'INNER', $db -> quoteName ( $locale_table, 'lcl' ) .
			" on (" . $db -> quoteName ( "main.$name" ) . ' = ' . $db -> quoteName ( "lcl.$name" ) . ')' );
		$query -> join ( 'INNER', $db -> quoteName ( $bind_table, 'bnd' ) .
			" on (" . $db -> quoteName ( "main.$name" ) . ' = ' . $db -> quoteName ( "bnd.category_child_id" ) . ')' );
		$query -> where ( $db -> quoteName ( "lcl.$cond_1" ) . ' = ' . $db -> quote ( $identifier ) );
		$query -> where ( $db -> quoteName ( "bnd.$cond_2" ) . ' = ' . $db -> quote ( $parent_id ) );

		$db -> setQuery ( $query );
		// return $db -> loadColumn (  );// Returns array or null
		return $db -> loadResult (  );// Returns value or null
	}

/*		Создаёт категорию в указанной родительской категории. Не вносит описания, метаданные и прочее

	@PURPOSE: Предназначена для автоматического создания категории по таблице продукции. Не устанавливает локализованные данные - только категорию и связь с родительской

	@HOW_TO_USE: Вызвать функцию. Возвращенный идентификатор передать в createCategoryLocalization
	
	@ARGS: идентификатор родительской категории

	@RETURN: идентификатор категории или null если что-то не получилось
	
	@PROBLEMS:
	* при возниконвении ошибки могут остаться уже внесенные изменения. Транзакция?
	* если после этой функции не вызвать createCategoryLocalization, то будет создана безымянная категория
	
	@IDEAS:
	* Можно включить сюда createCategoryLocalization, и все обернуть в транзакцию или включить createCategoryLocalization и это в отдельный методы
*/
	public static function createCategory ( $parent_id ) {
		$db = JFactory::getDbo (  );
		
		$main = new stdClass (  );
		
		$main -> created_on = JFactory::getDate (  ) -> toSql (  );
		if ( ! $db -> insertObject ( '#__virtuemart_categories', $main ) ) {
			return null;
		}
		$id = $db -> insertid (  );
		
		$bind = new stdClass (  );
		
		$bind -> category_parent_id = $parent_id;
		$bind -> category_child_id = $id;
		if ( ! $db -> insertObject ( '#__virtuemart_category_categories', $bind ) ) {
			return null;
		}
		
		return $id;
	}

	public static function bindCategory ( $productId, $categoryId ) {
		$s = new stdClass (  );
		$s -> binder = 'virtuemart_product_id';
		$s -> bindee = 'virtuemart_category_id';
		$s -> table_name = '#__virtuemart_product_categories';
		
		return self::bind_ ( $productId, $categoryId, $s );
	}

	public static function unbindCategory ( $productId, $categoryId ) {
		$s = new stdClass (  );
		$s -> binder = 'virtuemart_product_id';
		$s -> bindee = 'virtuemart_category_id';
		$s -> table_name = '#__virtuemart_product_categories';
		
		return self::unbind_ ( $productId, $categoryId, $s );
	}

	public static function getProductCategories ( $productId ) {
		/*
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );
		
		$query
			-> select ( $db -> quoteName ( '' ) )
			-> from ( $db -> quoteName ( '' ) )
			-> where ( $db -> quoteName ( '' ) . ' = ' . $db -> quote ( $product_id ) );
		*/
		$s = new stdClass (  );
		$s -> column_name = 'virtuemart_category_id';
		$s -> table_name = '#__virtuemart_product_categories';
		$s -> condition_column = 'virtuemart_product_id';

		return self::get_id_ ( $productId, $s );
	}

/*		Выполняет локализацию на один язык. Только основные данные

	@PURPOSE: Предназначена для автоматического создания категории по таблице продукции. Устанавливает только критически необходимые данные категории. Изображение, метаданные и прочее необходимо установить другими способами

	@ARGS: идентификатор категории, имя категории в указанной локали и код локали

	@RETURN: true или null если что-то не получилось
	
	@PROBLEMS:
	* не выполняет проверку существования локали
	
	@FRATURES:
	* не выполняет проверку существования категории, соответствующей аргументам, поэтому такую проверку нужно выполнять в вызывающем коде
*/
	public static function createCategoryLocalization ( $category_id, $category_name, $locale ) {
		
		$lcl = new stdClass (  );
		
		$lcl -> virtuemart_category_id = $category_id;
		$lcl -> category_name = $category_name;
		//$lcl -> category_description = 
		// meta...	
		$lcl -> slug = self::normalizeSlug ( $category_name, ! self::$config ['localized-slug'] );
		
		return JFactory::getDbo (  ) -> insertObject ( "#__virtuemart_categories_$locale", $lcl );
	}


//====================	Properties	===========================
// @PROBLEM: Каждую запись обрабатывать не эффективно. Массовая вставка - удаление - модификация. То же для LP

	public static function getPropertyId ( $identifier ) {
		// TODO: делаем запрос ИД таблицы virtuemart_customs по полю custom_title
		$s = new stdClass (  );
		$s -> column_name = 'virtuemart_custom_id';
		$s -> table_name = '#__virtuemart_customs';
		$s -> condition_column = 'custom_title';

		$results = self::get_id_ ( $identifier, $s );
		
		if ( $results ) {
			if ( count ( $results ) > 1 ) {
				print_r ( $results );
				throw new Exception ( "Several properties with title $identifier" );
			}
			return $results [0];
		} else {
			return null;
		}
	}

	public static function createProperty ( $identifier ) {
		$columns = array ( 'custom_title', 'show_title', 'field_type', 'published', 'created_on' ); 
		$values = array ( $identifier, 0, 'S', 1, JFactory::getDate (  ) -> toSql (  ) );

		return self::create_ ( $columns, $values, '#__virtuemart_customs' );
	}

	/*		Возвращает список значений указанного поля свойства продукта
	
		@RETURN: Массив вида {$virtuemart_customfield_id => {$virtuemart_customfield_id=>id, $field_name=>value},...}, который можно "упростить" функцией unsophisticate_assoc
	
		@PROBLEMS:
		* Пользовательские свойства и их значения хранятся в базе данных в виде идентификаторов языковых файлов, так что ни непосредственный поиск, ни создание/удаление по локализованному имени/значению невозможны
		
		@EXCEPTIONS:
		* Если записей, по условию нет, возвращает пустой массив
		* Если задано не корректное имя поля, выбрасывает исключение
	*/
	//@TESTED
	public static function getProductPropertyFieldValues ( $product_id, $property_id, $field_name = 'customfield_value' ) {
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );

		$query -> select ( $db -> quoteName ( 'virtuemart_customfield_id' ) );
		$query -> select ( $db -> quoteName ( $field_name ) );
		$query -> from ( $db -> quoteName ( '#__virtuemart_product_customfields' ) );
		$query -> where ( $db -> quoteName ( 'virtuemart_product_id' ) . ' = ' . $product_id );
		$query -> where ( $db -> quoteName ( 'virtuemart_custom_id' ) . ' = ' . $property_id );

		$db -> setQuery ( $query );
		
		/*
			Без аргумента вернет number => [virtuemart_customfield_id => id, customfield_value => value]
			Хотелось бы чтобы с аргументом оно возвращало virtuemart_customfield_id => customfield_value
			Но с аргументом оно возвращает virtuemart_customfield_id => [virtuemart_customfield_id => id, customfield_value => value]
			При чем передавать нужно именно 'virtuemart_customfield_id', а не $db -> quoteName ('virtuemart_customfield_id')
		*/
		return $db -> loadAssocList ( 'virtuemart_customfield_id' );
	}
	/*
		@TODO: Переделать getProductPropertyFieldValues в такую, а то этот ключ в loadAssocList только мешает
	*/
	public static function getProductPropertyFieldValues_bis ( $product_id, $property_id, $field_name = 'customfield_value' ) {
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );

		$query -> select ( $db -> quoteName ( 'virtuemart_customfield_id' ) );
		$query -> select ( $db -> quoteName ( $field_name ) );
		$query -> from ( $db -> quoteName ( '#__virtuemart_product_customfields' ) );
		$query -> where ( $db -> quoteName ( 'virtuemart_product_id' ) . ' = ' . $product_id );
		$query -> where ( $db -> quoteName ( 'virtuemart_custom_id' ) . ' = ' . $property_id );

		$db -> setQuery ( $query );
		
		/*
			Без аргумента вернет number => [virtuemart_customfield_id => id, customfield_value => value]
			Хотелось бы чтобы с аргументом оно возвращало virtuemart_customfield_id => customfield_value
			Но с аргументом оно возвращает virtuemart_customfield_id => [virtuemart_customfield_id => id, customfield_value => value]
			При чем передавать нужно именно 'virtuemart_customfield_id', а не $db -> quoteName ('virtuemart_customfield_id')
		*/
		return $db -> loadAssocList (  );
	}

	/*		Добавляет (не заменяет!) значение КФ для товара
	
		@PROBLEMS:
		* Ключи $field_list должны соответствовать полям ТБД
		* 
		
		@EXCEPTIONS:
		* Некорректное имя поля в $field_list вызовет исключение
		* Повторное указание поля, например customfield_value в $field_list вызовет исключение
		* "Не родные" customfield_params после сохранения в админпанели будут удалены
		* Не валидные значения, по крайней мере не число для поля customfield_price, будут проигнорированы
	*/
	//@TESTED
	public static function addProductPropertyValue ( $product_id, $property_id, $value, $field_list = null ) {
		
		$user =& JFactory::getUser();
		
		$columns = array ( 'virtuemart_product_id', 'virtuemart_custom_id', 'customfield_value', 'created_by', 'created_on' ); 
		$values = array ( $product_id, $property_id, $value, $user -> get ( 'id' ), JFactory::getDate (  ) -> toSql (  ) );
		
		if ( is_array ( $field_list ) ) {
			foreach ( $field_list as $field_name => $value ) {
				$columns [] = $field_name;
				$values [] = $value;
			}
		}

		return self::create_ ( $columns, $values, '#__virtuemart_product_customfields' );
				
	}
	
	/*		Обновляет значение КФ по ИД значения
	
		@PROBLEMS:
		* Ключи $field_list должны соответствовать полям ТБД
		
		@EXCEPTIONS:
		* Некорректное имя поля в $field вызовет исключение
		* "Не родные" customfield_params после сохранения в админпанели будут удалены
		* Не валидные значения, по крайней мере не число для поля customfield_price, будут проигнорированы - останется предыдущее значение
		* Не существующий ключ не приведет к изменениям в ТБД, но функция завершится успешно
	*/
	//@TESTED
	public static function updateProductPropertyValue ( $property_value_id, $field_list = null ) {
		
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );
		$user =& JFactory::getUser();
		
		if ( $field_list == null || ! is_array ( $field_list ) ) {
			return;
		}
		
		$fields = array (  );
		//@PROBLEM: Не все поля нуждаются в кавыченьи
		foreach ( $field_list as $field_name => $value ) {
			$fields [] = $db -> quoteName ( $field_name ) . ' = ' . $db -> quote ( $value );
		}
		$fields [] = $db -> quoteName ( 'modified_by' ) . ' = ' . $db -> quote ( $user -> get ( 'id' ) );
		$fields [] = $db -> quoteName ( 'modified_on' ) . ' = ' . $db -> quote ( JFactory::getDate (  ) -> toSql (  ) );
		
		$conditions = array (
			$db -> quoteName ( 'virtuemart_customfield_id' ) . " = $property_value_id"
		);
		
		$query -> update ( $db -> quoteName ( '#__virtuemart_product_customfields' ) ) -> set ( $fields ) -> where ( $conditions );

		$db -> setQuery ( $query );

		return $db -> execute (  );				
	}

	public static function removeProductPropertyValue ( $value_id ) {
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );
		
		$conditions = array (
			$db -> quoteName ( 'virtuemart_customfield_id' ) . ' = ' . $db -> quote ( $value_id )
		);
		
		$query -> delete ( $db -> quoteName ( '#__virtuemart_product_customfields' ) );
		$query -> where ( $conditions );
		
		$db -> setQuery ( $query );
		return $db -> execute (  );	
	}


//====================	Images	===========================

	public static function getImageId ( $image_fileurl ) {
		$s = new stdClass (  );
		$s -> column_name = 'virtuemart_media_id';
		$s -> table_name = '#__virtuemart_medias';
		$s -> condition_column = 'file_url';

		$results = self::get_id_ ( $image_fileurl, $s );
		
		if ( $results ) {	//Что возвращает loadColumn в случае отсутствия результата?
			if ( count ( $results ) > 1 ) {
				//log warning	более одного изображения с таким именем в базе
			}
			return $results [0];
		} else {
			return null;
		}
	}
	
	public static function createImage ( $image_title, $image_fileurl ) {
		$columns = array ( 'file_title', 'file_mimetype', 'file_type', 'file_url', 'created_on' ); 
		$values = array ( $image_title, 'image/jpeg', 'product', $image_fileurl, JFactory::getDate (  ) -> toSql (  ) );

		return self::create_ ( $columns, $values, '#__virtuemart_medias' );
	}

	public static function bindImage ( $productId, $imageId ) {
		$s = new stdClass (  );
		$s -> binder = 'virtuemart_product_id';
		$s -> bindee = 'virtuemart_media_id';
		$s -> table_name = '#__virtuemart_product_medias';
		
		return self::bind_ ( $productId, $imageId, $s );	
	}

	public static function unbindImage ( $productId, $imageId ) {
		$s = new stdClass (  );
		$s -> binder = 'virtuemart_product_id';
		$s -> bindee = 'virtuemart_media_id';
		$s -> table_name = '#__virtuemart_product_medias';
		
		return self::unbind_ ( $productId, $imageId, $s );
	}

	public static function getProductImages ( $productId ) {
		$s = new stdClass (  );
		$s -> column_name = 'virtuemart_media_id';
		$s -> table_name = '#__virtuemart_product_medias';
		$s -> condition_column = 'virtuemart_product_id';
		$result = self::get_id_ ( $productId, $s );
		return $result == null ? array (  ) : $result;
	}

	
	//====================	Localized properties	===========================
	
	public static function getLocalizedPropertyId ( $identifier ) {
		$s = new stdClass (  );
		$s -> column_name = 'ccf_id';
		$s -> table_name = '#__rabbit_ccf';
		$s -> condition_column = 'name';

		$results = self::get_id_ ( $identifier, $s );
		
		if ( $results ) {
			if ( count ( $results ) > 1 ) {
				//log warning	более одного свойства с таким именем в базе
			}
			return $results [0];
		} else {
			return null;
		}		
	}
	
	public static function createLocalizedProperty ( $identifier ) {
		$columns = array ( 'name' ); 
		$values = array ( $identifier );

		return self::create_ ( $columns, $values, '#__rabbit_ccf' );	
	}
	
	public static function getProductLPValues ( $product_id, $lp_id, $locale ) {

		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );

		$query -> select ( $db -> quoteName ( 'id' ) );
		$query -> select ( $db -> quoteName ( 'ccf_value' ) );
		$query -> from ( $db -> quoteName ( '#__rabbit_vmp_ccf' ) );
		$query -> where ( $db -> quoteName ( 'vmp_id' ) . ' = ' . $product_id );
		$query -> where ( $db -> quoteName ( 'ccf_id' ) . ' = ' . $lp_id );
		$query -> where ( $db -> quoteName ( 'lang' ) . ' = ' . $db -> quote ( $locale ) );

		$db -> setQuery ( $query );
		
		return $db -> loadAssocList ( 'id' );
	}
	
	public static function addProductLPValue ( $product_id, $lp_id, $locale, $value ) {
		$columns = array ( 'vmp_id', 'ccf_id', 'lang', 'ccf_value' ); 
		$values = array ( $product_id, $lp_id, $locale, $value );

		return self::create_ ( $columns, $values, '#__rabbit_vmp_ccf' );
		
	}
	
	public static function removeProductLPValue ( $value_id ) {
		
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );
		
		$conditions = array (
			$db -> quoteName ( 'id' ) . ' = ' . $db -> quote ( $value_id )
		);
		
		$query -> delete ( $db -> quoteName ( '#__rabbit_vmp_ccf' ) );
		$query -> where ( $conditions );
		
		$db -> setQuery ( $query );
		return $db -> execute (  );
	}
	
	public static function createLPLocalization ( $lp_id, $name, $locale ) {
		
	}
	
//=====================	Data bases	================================
	
/*		Получение связанных сущностей, например изображений продукта или сущности по идентификатору

	@RETURN: array of values or null. If entity selecting, more than one value in the array means error
*/
	static function get_id_ ( $identifier, $s ) {
		// Возвращает объект JDatabaseDriver
		// См. также
		$db = JFactory::getDbo (  );
		// Если передать TRUE, вернет новый объект, по умолчанию - текущий
		$query = $db -> getQuery ( true );

		// Можно передавать имя или массив имен колонок, втч в форме "tbl_aliase.clmn_name". Возвращает себя, так что можно делать chain. Например $q -> select ( 'a.name' ) -> select ( 'b.code' ) эквивалентно $q -> select ( array ( 'a.name', 'b.code' ) )
		// См. также https://api.joomla.org/cms-3/classes/JDatabaseQuery.html
		// quoteName кавычит имена колонок, таблиц. Для кавыченья значений используем quote. Вторым параметром можно указать AS. Можно передавать /возвращать массивы
		$query -> select ( $db -> quoteName ( $s -> column_name ) );
		$query -> from ( $db -> quoteName ( $s -> table_name ) );
		// Массив или chain. По умолчанию AND (второй строковоий аргумент)
		$query -> where ( $db -> quoteName ( $s -> condition_column ) . ' = ' . $db -> quote ( $identifier ) );

		$db -> setQuery ( $query );
		// Возвращает массив значений. В случае неудачи возвращает null
		// @QUESTION: В смысле ошибки или пустого результата? 
		return $db -> loadColumn (  );
	}

/*		Создание сущности

	@RETURN: new id or null
*/
	static function create_ ( $columns, $values, $table_name ) {
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );
		 
		$query
			-> insert ( $db -> quoteName ( $table_name ) ) 
			-> columns ( $db -> quoteName ( $columns ) )
			-> values ( implode ( ',', $db -> quote ( $values ) ) );
		
		// Установка запроса для последующего выполнения. Можно указать смещение и лимит
		// @QUESTION: смещение и лимит для выборки понятны, а для вставки, например?
		$db -> setQuery ( $query );
		// Возвращает КУРСОР и ЛОЖЪ в случае неудачи
		// @QUESTION: что такое КУРСОРЪ? Отвътъ. Курсор - это объект типа mysqli_result, что-то вроде итератора по результатам запроса с допполями
		if ( ! $db -> execute (  ) ) {
			// Не получилось создать. Надо что-то делать
			//$db -> getErrorNum (  );
			//$db -> getErrorMessage (  );
			return null;
		}
		
		// Возвращаем последний добавленный ИД
		return $db -> insertid (  );
	}

/*		Создаёт связь сущностей, одна из которых, обычно - продукт
	
	@RETURN: Возвращает КУРСОР если все нормально, ПРАВДУ если такая привязка ужо была и ЛОЖЪ если что-то пошло не так
*/
	static function bind_ ( $productId, $_id, $s ) {
		
		if ( self::check_bind_ ( $productId, $_id, $s ) ) {
			return true;
		}
		
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );
		
		$columns = array ( $s -> binder, $s -> bindee );
		$values = array ( $db -> quote ( $productId ), $db -> quote ( $_id ) );
		
		$query
			-> insert ( $db -> quoteName ( $s -> table_name ) )
			-> columns ( $db -> quoteName ( $columns ) )
			-> values ( implode ( ',', $values ) );
		
		$db -> setQuery ( $query );
		return $db -> execute (  );
	}

/*		Проверяет связаны ли сущности, одна из которых, обычно - продукт

	@RETURN: количество обнаруженных связей
*/	
	static function check_bind_ ( $productId, $_id, $s ) {
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );
		
		$query
			-> select ( 'COUNT(*)' )
			-> from ( $db -> quoteName ( $s -> table_name ) )
			-> where ( $db -> quoteName ( $s -> binder ) . ' = ' . $db -> quote ( $productId ) )
			-> where ( $db -> quoteName ( $s -> bindee ) . ' = ' . $db -> quote ( $_id ) );
		
		$db -> setQuery ( $query );
		return $db -> loadResult (  );
	}

/*

	@RETURN: КУРСОР или ЛОЖЪ
*/
	static function unbind_ ( $productId, $_id, $s ) {
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );
		
		$conditions = array ( 
			$db -> quoteName ( $s -> binder ) . ' = ' . $db -> quote ( $productId ),
			$db -> quoteName ( $s -> bindee ) . ' = ' . $db -> quote ( $_id )
		);
		
		$query -> delete ( $db -> quoteName ( $s -> table_name ) );
		$query -> where ( $conditions );
		
		$db -> setQuery ( $query );
		return $db -> execute (  );
	}

	static function normalizeSlug ( $slug, $translit ) {
		$st = mb_ereg_replace ( '[\s/]', "_", $slug );
		$st = mb_ereg_replace ( '\W', "", $st );
		if ( ! $st ) {
			return "";
		}
		
		if ( ! $translit ) {
			return mb_strtolower ( $st );
		}
		
		$replacement = array( 
			"й"=>"i","ц"=>"c","у"=>"u","к"=>"k","е"=>"e","н"=>"n", 
			"г"=>"g","ш"=>"sh","щ"=>"sh","з"=>"z","х"=>"x","ъ"=>"\'", 
			"ф"=>"f","ы"=>"i","в"=>"v","а"=>"a","п"=>"p","р"=>"r", 
			"о"=>"o","л"=>"l","д"=>"d","ж"=>"zh","э"=>"ie","ё"=>"e", 
			"я"=>"ya","ч"=>"ch","с"=>"c","м"=>"m","и"=>"i","т"=>"t", 
			"ь"=>"\'","б"=>"b","ю"=>"yu", 
			"Й"=>"I","Ц"=>"C","У"=>"U","К"=>"K","Е"=>"E","Н"=>"N", 
			"Г"=>"G","Ш"=>"SH","Щ"=>"SH","З"=>"Z","Х"=>"X","Ъ"=>"\'", 
			"Ф"=>"F","Ы"=>"I","В"=>"V","А"=>"A","П"=>"P","Р"=>"R", 
			"О"=>"O","Л"=>"L","Д"=>"D","Ж"=>"ZH","Э"=>"IE","Ё"=>"E", 
			"Я"=>"YA","Ч"=>"CH","С"=>"C","М"=>"M","И"=>"I","Т"=>"T", 
			"Ь"=>"\'","Б"=>"B","Ю"=>"YU", 
		); 
		
		foreach($replacement as $i=>$u) { 
			$st = mb_eregi_replace($i,$u,$st); 
		} 
		return strtolower ( $st ); 
	}
	
	/*		Преобразует вывод loadAssoc с параметром в божеский вид
	
		Работает только с выводом из двух колонок. Преобразует key1=>[key1=>value1, key2=>value2] key1=>value2
	*/
	static function unsophisticate_assoc ( $assoc, $value_field ) {

		if ( ! is_array ( $assoc ) ) {
			return null;
		}
		
		$clear_array = array (  );
		foreach ( $assoc as $k => $v ) {
			$clear_array [$k] = $v [$value_field];
		}
		
		return $clear_array;
	}
	
	
/*		==	Test functions	==	*/
	public static function test ( $import_data ) {
		
		$raw_products = $import_data ['data'] -> getAll (  );
		echo $raw_products [34] . "<br/>";
		$product = new Product ( $raw_products [34], $import_data ['meta'] );
		$product_id = self::getProductId ( $product -> identifier (  ) );
		if ( ! $product_id ) {
			$product_id = 1;
		}

		// echo $query -> dump (  ) . "<br/>";
		
		try {
		// Here one to write the test function
		// self::testRepeatedLocalizeProduct ( $raw_products [4], 5 );
		
		// self::testProductImages ( $product, $product_id );
		
		// self::testCreateAndUpdateProductLocalization ( $product, $product_id );
		
		// self::testLP ( $product, $product_id );
		
		//} catch ( RuntimeException $ee ) {
		//	echo "sql error: {$ee -> getMessage (  )} <br/>";
		
		//	self::testCustomFields ( $product, $product_id );
		
		// self::testVariant ( $product, $product_id );
		
		self::testMulti ( 2 );
		
		} catch ( Exception $e ) {
			echo $e -> getMessage (  ) . "<br/>";
			echo $e -> getTraceAsString (  );
		}
	}
	
	public static function testRepeatedLocalizeProduct ( $raw_product, $prod_id ) {
		
		$test = self::localizeProduct ( $prod_id, $raw_product, self::$config ['default-locale'] );
		var_dump ( $test );
		echo ( "<br/>" );
		$test = self::localizeProduct ( $prod_id, $raw_product, self::$config ['default-locale'] );		
	}
	
	public static function testProductImages ( $product, $product_id ) {

		echo "Existed Product images id<br/>";
		print_r ( self::getProductImages ( $product_id ) );
		echo "<br/>";
		
		echo "Imported Product images name<br/>";
		print_r ( $product -> images (  ) );
		echo "<br/>";
		
		echo "Check Imported Product images existance<br/>";
		foreach ( $product -> images (  ) as $test_image ) {
			$test_image_id = self::getImageId ( self::$config ['product-image-path'] . $test_image -> identifier (  ) );
			echo $test_image -> identifier (  ) . ": $test_image_id<br/>";
			if ( ! $test_image_id ) {
				$test_image_id = self::createImage ( $test_image -> identifier (  ), self::$config ['product-image-path'] . $test_image -> identifier (  ) );
			}
			
			if ( ! self::bindImage ( $product_id, $test_image_id ) ) {
				throw new Exception ( "Couldn`t bind image: {$test_image -> getDebugInfo (  )}. Product: {$product -> getDebugInfo (  )}" );
			}
		}
						
	}
	
	public static function testCreateAndUpdateProductLocalization ( $product, $product_id ) {
		if ( ! self::createProductLocalization ( $product_id, $product, self::$config ['default-locale'] ) ) {
			throw new Exception ( 'Couldn`t create product localization. Sku: ' . $product -> getDebugInfo (  ) );
		}
		if ( ! self::updateProductLocalization ( 122, $product, self::$config ['default-locale'] ) ) {
			throw new Exception ( 'Couldn`t update product localization. Sku: ' . $product -> getDebugInfo (  ) );
		}
	}

	public static function testLP ( $product, $product_id ) {
		
		echo "Table: product localized properties<br/>";
		$lp_list = $product -> localizedProperties (  );
		print_r ( $lp_list );
		echo "<br/>";
		
		echo "Table: lp identifier<br/>";
		foreach ( $lp_list as $lp ) {
			echo $lp -> identifier (  ) .": ". implode ( " == ", $lp -> values (  ) );
			echo "<br/>";
		
			$lp_id = self::getLocalizedPropertyId ( $lp -> identifier (  ) );
			if ( ! $lp_id ) {
				$lp_id = self::createLocalizedProperty ( $lp -> identifier (  ) );	//$lang
				if ( ! $lp_id ) {
					throw new Exception ( "Couldn`t create localized property: {$lp -> getDebugInfo (  )}. Product: {$product -> getDebugInfo (  )}" );
				}
				
			}
			echo "DB: LP id: $lp_id <br/>";
		
			// foreach ( $lp -> values (  ) as $value ) {
				// // self::addProductLPValue ( $product_id, $lp_id, self::$config ['default-locale'], $value );
			// }
			
			// echo "DB: Get product lp values<br/>";
			$current_lp_values = self::unsophisticate_assoc ( self::getProductLPValues ( $product_id, $lp_id, self::$config ['default-locale'] ), 'ccf_value' );
			// print_r ( $current_lp_values );
			// echo "<br/>";
			
			foreach ( $current_lp_values as $key => $v ) {
				self::removeProductLPValue ( $key );
			}
		
		}
		echo "<br/>";
	}

	public static function testCustomFields ( $product, $product_id ) {
		
		/* echo "Geting CF id <br/>";
		echo "Existed ientidier (CF_COLOR)  <br/>";
		echo self::getPropertyId ( "CF_COLOR" );
		echo "<br/>";
		
		echo "Existed ientidier (Колекція)  <br/>";
		// Кириллические имена находятся. Повторяющиеся имена вызывают исключение. Не существующие имена возвращают null
		try {
			echo self::getPropertyId ( "Колекція" );
		} catch ( Exception $e ) { $e -> getMessage (  ); }
		echo "<br/>";
		
		echo "Null, missing or empty args<br/>";
		// Если есть запись с пустым идентификатором, возвращает её. Если не передать аргумент, выбросит предупреждение php, но запись вернет. Если соответствующей записи нет, вернет null
		try {
			$t1 =  self::getPropertyId (  );
			var_dump ( $t1 );
		} catch ( Exception $e ) { $e -> getMessage (  ); }
		echo "<br/>";
		 */
		
		/* echo "Geting CF value id <br/>";
		echo "Existing and valid pair product id (1) and CF id (7). Result {7, 10}<br/>";
		// Возвращает массив вида value_id = > {value_id = > id, value => value}
		$t1 =  self::getProductPropertyFieldValues ( 1, 7 );
		var_dump ( $t1 );
		echo "<br/>";
		
		echo "Missing but valid pair product id (2) and CF id (7). Result {}<br/>";
		// Возвращает пустой массив, в том числе для несуществующих и некорректных (строковых, отрицательных) значений входных параметров. Для значений null и пустых строк не возвращает вообще ничего, что странно. Если не передать один из аргументов, выбросит предупреждение php
		try {
			$t1 =  self::getProductPropertyFieldValues ( -2, 7 );
			var_dump ( $t1 );
		} catch ( Exception $e ) { $e -> getMessage (  ); }
		echo "<br/>";
		 */
		 
		/* echo "Creating CF <br/>";
		echo "Valid identifier (TEST_20171221)<br/>";
		// Создаёт запись, также для повторяющегося, пустого и даже отсутствующего идентификатора. В последних двух случаях в базе будет пустое значение
		try {
			$t1 =  self::createProperty ( 'TEST_20171221' );
			var_dump ( $t1 );
		} catch ( Exception $e ) { $e -> getMessage (  ); }
		echo "<br/>";
	 */

		/* echo "Creating CF value <br/>";
		echo "Valid (prod_id: 1, custom_id: 7, value: CF_COLOR_RED)<br/>";
		// Записи будут созданы и для несуществующих продуктов и для несуществующих свойств. Дублируются. В панели отображается каждая продублированная запись. Пустые и null-аргументы вносятся в базу. Отсутствующие аргументы вызывают предупреждение, но тоже вносятся в базу как пустые
		try {
			$t1 =  self::addProductPropertyValue ( 1, 7, CF_COLOR_RED );
			var_dump ( $t1 );
		} catch ( Exception $e ) { $e -> getMessage (  ); }
		echo "<br/>";
		 */

		/* echo "Remooving CF value <br/>";
		echo "Existing (id:41 .. 50)<br/>";
		// Хорошо удоляет. Возвращает ПРАВДУ каг для существующих, так и для не (ну, типо, ничего не подходит, но запрос удачный). Пустые, null- и не переданные значения также удаляет с положительным результатом
		for ( $i = 41; $i <= 50; $i++ ) {
		try {
			$t1 =  self::removeProductPropertyValue ( $i );
			var_dump ( $t1 );
		} catch ( Exception $e ) { $e -> getMessage (  ); }
		echo "<br/>";
		}
		 */
	}
	
	public static function testMulti ( $id ) {
		//id = 2, 30
		$multi_cf_id = self::getPropertyId ( self::$config ['multiproduct-cf-name'] );
		$multi_param_cf_id = self::getPropertyId ( self::$config ['multiproduct-cf-paramcf-name'] );
		echo "\$multi_cf_id = $multi_cf_id, \$multi_param_cf_id = $multi_param_cf_id<br/>";
	
		// Поле customfield_params на этом этапе уже должно быть заполненным. Продукт должен быть парентом
		$cf_params_string = self::get_multicf_param_string ( $id, $multi_cf_id );
		//print_r ( $cf_params_string );
		//echo "<br/>";
		$params_string = $cf_params_string ['pstring'];
		if ( empty ( $params_string ) ) {
			throw new Exception ( "Empty param string. Product id: [$id]. CF id: [$multi_cf_id]" );
		}
		
		echo ( "params_string ['id'] = " . $cf_params_string ['id'] . "<br/>" );
		echo ( "params_string ['pstring'] = " . $cf_params_string ['pstring'] . "<br/>" );
		
		$params = explode ( "|", $params_string );
		if ( ! is_array ( $params ) || ! isset ( $params [0] ) || ! isset ( $params [1] ) ) {
			throw new Exception ( "Incorrect param string. Product id: [$id]. CF id: [$multi_cf_id]. Param string: $params_string" );
		}
		
		echo ( "params [0] = " . $params [0] . "<br/>" );
		echo ( "params [1] = " . $params [1] . "<br/>" );
		
		$new_options [ $id ] = array ( "0", "0" );
		foreach ( self::get_product_varialnt_list ( $id, $multi_param_cf_id ) as $child ) {
			echo ( "Preffixes: " . self::$config ['size-cfvalue-preffix'] . ", " . self::$config ['color-cfvalue-preffix'] );
			echo ( "Child: " );
			print_r ( $child );
			echo ( "<br/>" );
			if ( mb_strpos ( $child ['customfield_value'], self::$config ['size-cfvalue-preffix'], 0, self::$config ['default-charset'] ) === 0 ) {
				$new_options [ $child ['virtuemart_product_id'] ][0] = $child ['customfield_value'];
			} else if ( mb_strpos ( $child ['customfield_value'], self::$config ['color-cfvalue-preffix'], 0, self::$config ['default-charset'] ) === 0 ) {
				$new_options [ $child ['virtuemart_product_id'] ][1] = $child ['customfield_value'];
			} else {
				// warning: unexpected CF value format
			}
		}
		echo ( "New options<br/>" );
		// print_r ( $new_options );
		$new_params_string =
			$params [0] . "|" .
			self::serializeMultiCustomParams ( $new_options ) . "|";
		echo $new_params_string;
		echo ( "<br/>" );
		
		self::update_multicf ( $cf_params_string ['id'], /*$id, */$new_params_string );
	}

	public static function testVariant ( $product, $product_id ) {

		$t1 = self::process_specific_product_variant_cf_values ( $product, $product_id );
		echo ( "Test process_specific_product_variant_cf_values ()<br/>" );
		print_r ( $t1 );
		echo ( "<br/>" );
	}
}

/*		Обертка для продукта

	@PROBLEMS:
	* В текущем виде создаёт пользовательские свойства, специфичные только для продукции категории "Одежда"
	
	@IDEAS:
	* В зависимости от категории, создавать один из подклассов или ...
	* ... передавать в конструктор сформированный вне список пользовательских свойств
*/
class Product {
	
	public function __construct ( $p, $meta ) {
		$this -> data = $p;
		
		foreach ( $meta as $k => $m ) {

			if ( $m ['type'] == 1 ) {
				$this -> properties [] = new Property ( $k, $this -> data -> get ( $k ) );
			} else if ( $m ['type'] == 2 ) {
				$value = $this -> data -> get ( $k );
				if ( empty ( $value ) ) {
					continue;
				}
				$this -> localized_properties [] = new LocalizedProperty ( $k, $this -> data -> get ( $k ) );
			} else if ( $m ['type'] == -1 ) {
				$this -> variant_properties [$k] = $p -> get ( $k );
			}
		}
		//$properties ['color_list']
		
		
		//$this -> images [] = ;
	}
	
	protected $data;
	protected $properties = array ();
	protected $variant_properties = array ();	//@PROBLEM: отличный от properties, формат. Неплохо бы переделать
	protected $localized_properties = array ();
	protected $images;
	
	public $published;
	
	//@PROBLEM: Нужно что-то сделать с этим грязным хаком. Создавать клон объекта, например. А при установке значения выполнять проверку
	protected $new_identifier = null;
	public function identifier ( $new_value = null ) {
		if ( $new_value !== null ) {
			//$this -> data -> set ( 'sku', $new_value );
			$this -> new_identifier = $new_value;
		}
		return $this -> new_identifier === null ?  $this -> data -> get ( 'sku' ) : $this -> new_identifier;
	}

	public function name (  ) {
		return $this -> data -> get ( 'name' );
	}
	
	public function desc (  ) {
		return $this -> data -> get ( 'desc' );
	}
	
	public function categories (  ) {
		
		$categories =  explode ( ',' ,$this -> data -> get ( 'category' ) );
		
		if ( empty ( $categories ) )
			return null;
		
		/*
		array_walk (
			$categories,
			function ( & $value, $key ) {
				$value = trim ( $value );
			}
		);
		
		return $categories;
		*/
		
		$c_objects = array (  );
		foreach ( $categories as $c ) {
			$c_objects [] = new Category ( trim ( $c ) );
		}
		return $c_objects;
	}

	public function properties (  ) {
		
		return $this -> properties;
	}

	public function variantProperties (  ) {
		return $this -> variant_properties;
	}
	
	public function images (  ) {
		$images =  explode ( ',', $this -> data -> get ( 'images' ) );
		
		if ( empty ( $images ) )
			return array (  );
		
		$i_objects = array (  );
		foreach ( $images as $i ) {
			$i_objects [] = new Image ( trim ( $i ) );
		}
		return $i_objects;		
	}
	
	public function localizedProperties (  ) {
		return $this -> localized_properties;
	}
	
	public function getDebugInfo (  ) {
		return $this -> identifier (  );
	}
}

class Property {
	
	public function __construct ( $identifier, $value ) {
		
		$this -> identifier = $identifier;
		
		$this -> value = explode ( ',', $value );
		array_walk (
			$this -> value,
			function ( & $v, $key ) {
				$v = trim ( $v );
			}
		);
	}
	
	protected $identifier;
	protected $value;
	
	/*	В качестве идентификатора поля будем использовать внутренние имена, служащие также идентификаторами языковых файлов, при необходимости отображения. Преобразование имен столбцов таблицы в идентификаторы полей должен выполнять класс CsvMetadata. В базе данных идентификатору соответствует поле custom_title таблиыц virtuemart_customs, за уникальностью которого мы должны следить самостоятельно
	*/
	public function identifier (  ) {
		return $this -> identifier;
	}
	
	public function value (  ) {
		return $this -> value;
	}
	
	public function getDebugInfo (  ) {
		return $this -> identifier (  );
	}
}

class Category {
	
	public function __construct ( $c ) {
		$this -> data = $c;
	}
	
	protected $data;
	
	public function identifier (  ) {
		return $this -> data;
	}
	
	public function path (  ) {
		return explode ( '/', $this -> data );
	}
	
	public function getDebugInfo (  ) {
		return $this -> identifier (  );
	}
}

class Image {
	
	public function __construct ( $p ) {
		$this -> data = $p;
	}
	
	protected $data;
	
	public function identifier (  ) {
		return $this -> data;
	}
	
	public function getDebugInfo (  ) {
		return $this -> identifier (  );
	}
}

class LocalizedProperty {
	
	public function __construct ( $identifier, $value ) {
		
		$this -> identifier = $identifier;
		
		$this -> value = explode ( ',', $value );
		array_walk (
			$this -> value,
			function ( & $v, $key ) {
				$v = trim ( $v );
			}
		);
	}
	
	protected $identifier;
	protected $value;
	
	public function identifier (  ) {
		return $this -> identifier;
	}
	
	public function values (  ) {
		return $this -> value;
	}
	
	public function getDebugInfo (  ) {
		return $this -> identifier (  );
	}
}

