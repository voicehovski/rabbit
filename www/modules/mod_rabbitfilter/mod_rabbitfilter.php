<?php

defined ( '_JEXEC' ) or die;

require_once dirname ( __FILE__ ) . '/helper.php';

$option1 = $params -> get ( 'option1', '1' );

//$query = JUri::getInstance (  ) -> getQuery ( true );	//true превращает строку в массив, но одинаковыые индексы съедаются последним значением. Нам такое не устраиваетс. Нас такое не нужно
$filter_options = modRabbitFilterHelper::fetch_filter_options (true );
$product_properties = modRabbitFilterHelper::get_a ( $option1 );

require JModuleHelper::getLayoutPath ( 'mod_rabbitfilter' );