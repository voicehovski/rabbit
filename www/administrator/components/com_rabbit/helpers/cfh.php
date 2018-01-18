<?
defined('_JEXEC') or die('Restricted access');

class PropertyStorage {
	
	/*
		Не совсем понятно как подключать класс. Можно ли разместить эту функцию здесь, учитывая что в ней используются функции типа normalizeSlug
	*/
	protected static function _process_properties ( $property_list, $product_id, $property_storage ) {
		
		// $property_list = $property_storage -> getPropertyList ( $product ) ... Таким образом имеем здесь объект Product
		/*
		foreach ( $property_list as $imported_property ) {
			
			//	Что брать в качестве идентификатора? $imported_property -> identifier (  ) в чистом виде подходит плохо ввиду кирилличности
			$property_name = "PROPERTY_NAME_" . strtoupper ( self::normalizeSlug ( $imported_property -> identifier (  ) ) );
			$property_id = $property_storage -> getPropertyId ( $property_name );
			if ( ! $property_id ) {
				//throw new Exception ( "Unknown property: {$imported_property -> getDebugInfo (  )}. Product: {$product_id}" );
				
				// @IDEA: Мы решили не создавать свойства в процессе импорта, то есть следующий код нужно закоменчить. Можно оставить его выполняемым, а $property_storage реализовать так чтобы createProperty () выбрасывал исключение "Unknown property"
				$property_id = $property_storage -> createProperty ( $property_name );
				if ( ! $property_id ) {
					throw new Exception ( "Couldn`t create property: {$imported_property -> getDebugInfo (  )}. Product: {$product_id}" );
				}
				// @QUESTION: Делаем локализацию имен свойств здесь? Или потом вручную - их все равно не много и добавляться будут редко?
				// @PROBLEM: 1) Как называть? Если брать из заголовка таблицы, то к нему повышаются требования 2) Что делать с обычными свойствами? Для их локализации нужно изменять языковые файлы
				if ( ! $property_storage -> createPropertyLocalization ( $property_id, $imported_property -> identifier (  ) ,$config ['default-locale'] ) ) {
					throw new Exception ( "Couldn`t localize property: {$imported_property -> getDebugInfo (  )}. Product: {$product_id}" );
				}
			}
			
			// *Nice* include unsophisticate_assoc to avoid fieldname passing here
			$value_list_db =  $property_storage -> getNiceProductPropertyValues ( $product_id, $property_id, self::$config ['default-locale'] );
			
			foreach ( $imported_property -> values (  ) as $imported_value ) {
				$k = array_search ( $imported_value, $value_list_db );
				if ( $k !== false ) {
					unset ( $value_list_db [$k] );
				} else {
					if ( ! $property_storage -> addProductPropertyValue ( $product_id, $property_id, self::$config ['default-locale'], $imported_value ) ) {
						throw new Exception ( "Couldn`t bind localized property value: {$imported_property -> getDebugInfo (  )}. Product: {$product_id}" );
					}
				}
			}
			
			foreach ( $value_list_db as $value_id => $value ) {
				$property_storage -> removeProductPropertyValue ( $value_id );
			}
		}
		*/		
	}

	//abstract? Здесь делаем абстрактные методы, а в наследниках реализуем реализации...
	public function getPropertyId ( $property_name ) {}
	public function createProperty ( $property_name ) {}
	public function createPropertyLocalization ( $property_id, $localized_property_name ,$locale ) {}
	public function getNiceProductPropertyValues ( $product_id, $property_id, $locale ) {}
	public function addProductPropertyValue ( $product_id, $property_id, $locale, $value ) {}
	public function removeProductPropertyValue ( $value_id ) {}
	
}

// Здесь размещаем классы данных, типа Property