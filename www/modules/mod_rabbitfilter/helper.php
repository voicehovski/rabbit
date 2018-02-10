<?php

class ModRabbitFilterHelper
{
	
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
	
    /**
     *  Возвращяет названия и значения ЛКФ для формирования фильтра продукции
     *
     * Для вумных циферок нужно не просто количество продуктов, соответствующих занчению ЛКФ, а перечень. Поэтому будем использовать функцию get_a
     *
     * @param   можно передавать опции фильтрации
     */    
    public static function get ( $params ) {

		$db = JFactory::getDbo (  );
        $query = $db -> getQuery ( true );

        // Здесь либо DISTINCT, либо group лишний
        $query
            // -> select ( 'COUNT(DISTINCT ' . $db -> quoteName ( 'v.lcf_value' ) . ') AS "value_count"' )
            // -> select ( 'DISTINCT ' . $db -> quoteName ( 'v.lcf_value' ) )
            -> select ( $db -> quoteName ( 'v.lcf_value' ) )
            -> select ( $db -> quoteName ( 'c.lcf_name' ) )
            -> select ( $db -> quoteName ( 'c.lcf_id' ) )
            -> select ( $db -> quoteName ( 'v.lcf_value_code' ) )
            // group_concat since mysql 5.6
            -> select ( 'count(' . $db -> quoteName ( 'v.vm_product_id' ) . ') as "product_count"' )
            -> from ( $db -> quoteName ( '#__localized_custom_field_values', 'v' ) )
            -> join ( 'INNER', $db -> quoteName ( '#__localized_custom_fields', 'c' ) .
                ' ON (' . $db -> quoteName ( 'c.lcf_id' ) .
                ' = ' . $db -> quoteName ( 'v.lcf_id' ) . ')' )
            -> where ( 'lang = ' . $db -> quote ( self::$config ['default-locale'] ) )
            -> group ( 'v.lcf_value' );

		$db -> setQuery ( $query );
		$result = $db -> loadAssocList (  );

		// Вот это здесь не на месте
		$x = array ();
		foreach ( $result as $r ) {
		    $x [$r ['lcf_name']][] = array ( $r ['lcf_value'], $r ['product_count'], $r ['lcf_value_code'], $r ['lcf_id'] );
        }
		
		return $x;
    }

    /**
     * Вовзращает перечень ЛКФ. Для каждого указан перечень значений, а для каждого значения - перечень продукции.
     * Для формирования модуля фильтрации
     *
     * @RETURN:
     * Формат результата: [lcf_name=>[lcf_value, [vm_product_id], lcf_value_code, lcf_id]]
     *
     * @IDEAS:
     * Намного проще достичь того же результата было бы с помощью sql функции group_concat, доступной с версии 5.6
     *
     * @PROBLEMS:
     * Извлекает всю таблицу. Может кешировать или типа того...
     * Преобразование результатов запроса уместнее смотрелось бы отдельной функцией, но как передать идентификаторы? Условиться об индексах?
     * */
    public static function get_a ( $params ) {

        $db = JFactory::getDbo (  );
        $query = $db -> getQuery ( true );

        $query
            -> select ( $db -> quoteName ( 'v.lcf_value' ) )
            -> select ( $db -> quoteName ( 'c.lcf_name' ) )
            -> select ( $db -> quoteName ( 'c.lcf_id' ) )
            -> select ( $db -> quoteName ( 'v.lcf_value_code' ) )
            -> select ( $db -> quoteName ( 'v.vm_product_id' ) )
            -> from ( $db -> quoteName ( '#__localized_custom_field_values', 'v' ) )
            -> join ( 'INNER', $db -> quoteName ( '#__localized_custom_fields', 'c' ) .
                ' ON (' . $db -> quoteName ( 'c.lcf_id' ) .
                ' = ' . $db -> quoteName ( 'v.lcf_id' ) . ')' )
            -> where ( 'lang = ' . $db -> quote ( self::$config ['default-locale'] ) );

        $db -> setQuery ( $query );
        $result = $db -> loadAssocList (  );

        $x = array (  );
        foreach ( $result as $r ) {
            $x [$r ['lcf_value_code']][] = $r ['vm_product_id'];
        }

        $y = array (  );
        foreach ( $result as $r ) {
            $y [$r ['lcf_id']][0] = $r['lcf_name'];
            $y [$r['lcf_id']][1][$r['lcf_value_code']] = array (  $r ['lcf_value'], $x [$r ['lcf_value_code']] );
        }

        return $y;
    }

    /**
     *  Возвращает массив идентификаторов продукции, соответствующей $options, то есть содержащей указанные ЛКФ
     *
     *  @TODO:    Надо чтобы оно возвращало не только идентификаторы продукции, но и относящиеся к ним значения ЛКФ
     *
        $options	индексный массив 0 - lcf_id, 1 - codes array
        $lang	код языка вида uk_ua

        Возвращает массив ид продуктов
    */
    public static function filter_lcf ( $options, $lang ) {

        if ( count ( $options ) == 0 ) {
            return null;	// or empty array
        }

        $db = JFactory::getDbo (  );
        $query = $db -> getQuery ( true );

        $query
            -> select ( 'DISTINCT ' . $db -> quoteName ( 'T0.vm_product_id' ) )
            -> from ( $db -> quoteName ( '#__localized_custom_field_values', 'T0' ) )
            -> where ( $db -> quoteName ( 'T0.lcf_value_code' ) . ' in (' . implode ( ",", $options [0][1] ) . ')' )
            -> where ( $db -> quoteName ( 'T0.lang' ) . ' = ' . $db -> quote ( $lang ) );

        for ( $i = 1; $i < count ( $options ); $i++ ) {
            $table_alias = "T$i";
            $query
                -> from ( $db -> quoteName ( '#__localized_custom_field_values', $table_alias ) )
                -> where ( $db -> quoteName ( $table_alias . '.lcf_value_code' ) . ' in (' . implode ( ",", $options [$i][1] ) . ')' )
                -> where ( $db -> quoteName ( $table_alias . '.vm_product_id' ) . ' = ' . $db -> quoteName ( 'T0.vm_product_id' ) )
                -> where ( $db -> quoteName ( $table_alias . '.lang' ) . ' = ' . $db -> quote ( $lang ) );
        }

        $DUMP = $query -> dump (  );

        $db -> setQuery ( $query );
        return $db -> loadColumn (  );
    }

    public static function get_lcf_values_for ( $ids ) {

    }

    /**
     * Извлекает опции фильтрации (оттуда где они будут) и возвращает их в удобном для дальнейшей обработки виде
     *
     * Если $mode установлен, возвращает массив вида [option_name=>[option_value]]
     * Если сброшен - [[option_name, [option_value]]]
     * */
    public static function fetch_filter_options ( $mode ) {

        $KEY_PATTERN = '/^[0-9]+$/';
        $VALUE_PATTERN = '/^[0-9]+$/';

        $param_string = JUri::getInstance (  ) -> getQuery (  );

        if ( empty ( $param_string ) ) {
            return null;
        }

        $params = explode ( "&", $param_string );
        $x = array (  );

        foreach ( $params as $p ) {
            $o = explode ( "=", $p );
            if ( count  ( $o ) != 2 )
                return array (  );

            if ( ! preg_match ($KEY_PATTERN, $o [0] ) ) {
                return array (  );
            }

            if ( ! preg_match ($VALUE_PATTERN, $o [1] ) ) {
                return array (  );
            }

            $x [$o [0]][] = $o [1];
        }

        if ( $mode )
            return $x;

        $y = array (  );
        foreach ( $x as $k => $v ) {
            $y [] = array ( $k, $v );
        }
        return $y;
    }
}

