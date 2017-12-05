<?php

defined('_JEXEC') or die('Restricted access');


class DBHelper {
	
	static $config = array (
		'localized-slug' => true,
		'default-locale' => 'uk_ua',
		
		'product-image-path' => 'images/virtuemart/product/'
	);
		
	public static function import ( $import_data ) {
		
		//		==	Test block	==
			 // self::test ( $import_data );
			 // return;
		//		==	End of test block	==

		$db = JFactory::getDbo (  );
		
		foreach ( $import_data ['data'] -> getAll (  ) as $raw_product ) {
			
			$product = new Product ( $raw_product, $import_data ['meta'] );
			
			//		==	Product import	==
			$product_id = self::getProductId ( $raw_product -> get ( 'sku' ) );

			if ( $product_id === null ) {
				$product_id = self::createProduct ( $product );
				if ( $product_id === null ) {
					throw new Exception ( 'Couldn`t create product. Sku: ' . $product -> getDebugInfo (  ) );
				}
				if ( ! self::createProductLocalization ( $product_id, $product, self::$config ['default-locale'] ) ) {
					throw new Exception ( 'Couldn`t create product localization. Sku: ' . $product -> getDebugInfo (  ) );
				}
			} else {
				if ( ! self::updateProduct ( $product_id, $product ) ){
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
			
			
			//		==	Category import	==
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
				if ( ! self::unbindCategory ( $product_id, $cid ) === false ) {
					throw new Exception ( "Couldn`t unbind category. Category: {$category -> getDebugInfo (  )}. Product: {$product -> getDebugInfo (  )}" );
				}
			}
			// @IDEA: array_diff ( $current_category_id_list, $imported_category_id_list )	unbind
			// @IDEA: array_diff ( $imported_category_id_list ,$current_category_id_list )	bind
			
			
			//		==	Custom fields import	==
			/*
			foreach ( $product -> properties (  ) as $property ) {
				
				$property_id = self::getPropertyId ( $property -> identifier (  ) );	//custom_title, уникальность под вопросом
				
				// Проверяем, существет ли такое свойство в системе. При необходимости создаём
				if ( $property_id === null ) {
					$property_id = self::createProperty ( $property -> identifier (  ) );
					if ( $property_id === null ) {
						throw new Exception ( "Couldn`t create property: {$property -> getDebugInfo (  )}" );
					}
				}
				
				// Получаем перечень значений свойства ИД => значение для текущего продукта
				$current_property_value_list = self::unsophisticate_assoc ( self::getProductPropertyValues ( $product_id, $property_id ), 'customfield_value' );
				foreach ( $property -> value (  ) as $value ) {
					// @NOTE: Возвращает ключ первого найденного значения. Регистрозависима. Для поиска нескольких значений: array array_keys ( $array, $value ) Тоже регистрозависима
					$k = array_search ( $value, $current_property_value_list );
					if ( $k !== false ) {
						unset ( $current_property_value_list [$k] );
					} else {
						if ( ! self::bindProductPropertyValue ( $product_id, $property_id, $value ) ) {
							throw new Exception ( "Couldn`t bind property value: {$property -> getDebugInfo (  )}. Product: {$product -> getDebugInfo (  )}" );
						}
					}
				}
				
				// Удаляем лишние значения из базы
				foreach ( $current_property_value_list as $id => $value ) {
					self::removeProductPropertyValue ( $id );
				}
			}
			*/
			
			
			//		==	Images import	==
			
			$current_image_id_list = self::getProductImages ( $product_id );
			
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
				
				foreach ( $current_image_id_list as $id => $value ) {
					if ( ! self::unbindImage ( $product_id, $image_id ) ) {
						throw new Exception ( "Couldn`t bind image: {$image -> getDebugInfo (  )}. Product: {$product -> getDebugInfo (  )}" );
					}
				}
			}
			
			
			//		==	Custom custom fields import	==
			//Реализовать и тестировать функции, подумать с lang
			//Сделать дополнительное свойство метаданных
			//	Разделить import_data в check view на meta и data
			//	Передавать meta через метод import модели и здесь в каждый продукт (криво, но работать будет)
			//	В продукте разделять поля по свойству type метаданных 
			//Здесь в конфиге - язык по умолчанию, в переводах делаем конкретный язык
			
			foreach ( $product -> localizedProperties (  ) as $lp ) {
				
				$lp_id = self::getLocalizedPropertyId ( $lp -> identifier (  ) );
				if ( ! $lp_id ) {
					$lp_id = self::createLocalizedProperty ( $lp -> identifier (  ) );	//$lang
					if ( ! $lp_id ) {
						throw new Exception ( "Couldn`t create localized property: {$lp -> getDebugInfo (  )}. Product: {$product -> getDebugInfo (  )}" );
					}
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
					self::removeProductLPValue ( $key );	//$lang
				}
			}
			
		}
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
	public static function createProduct ( $product ) {
		$columns = array ( 'product_sku', 'product_in_stock', 'published', 'created_on' ); 
		$values = array ( $product -> identifier (  ), 1, 1, JFactory::getDate (  ) -> toSql (  ) );

		return self::create_ ( $columns, $values, '#__virtuemart_products' );
	}

/*		Обновляет данные продукта

	@RETURN: ПРАВДУ если все хорошо

	@TODO: Добавить локализацию
*/
	public static function updateProduct ( $id, $product ) {
		
		$object = new stdClass();

		// Must be a valid primary key value.
		$object -> virtuemart_product_id = $id;
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

	/*		Возвращает список значений свойства продукта
	
		@PROBLEMS:
		* Пользовательские свойства и их значения хранятся в базе данных в виде идентификаторов языковых файлов, так что ни непосредственный поиск, ни создание/удаление по локализованному имени/значению невозможны
	*/
	public static function getProductPropertyValues ( $product_id, $property_id ) {
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );

		$query -> select ( $db -> quoteName ( 'virtuemart_customfield_id' ) );
		$query -> select ( $db -> quoteName ( 'customfield_value' ) );
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

	public static function bindProductPropertyValue ( $product_id, $property_id, $value ) {
		
	}

	public static function removeProductPropertyValue ( $value_id ) {
		
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
		echo $raw_products [35] . "<br/>";
		$product = new Product ( $raw_products [35], $import_data ['meta'] );
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
			}
		}
		//$properties ['color_list']
		
		
		//$this -> images [] = ;
	}
	
	protected $data;
	protected $properties;
	protected $localized_properties;
	protected $images;
	
	public $published;
	
	public function identifier (  ) {
		return $this -> data -> get ( 'sku' );
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

