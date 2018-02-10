<?php
// no direct access
defined( '_JEXEC' ) or die;

/*
Как делать плагины https://docs.joomla.org/J3.x:Creating_a_Plugin_for_Joomla/ru
Шаблон имени класса плагина plg<PluginGroup><PluginName> имена классов и функций не чувствительны к регистру

В системе функции плагина вызываются так:
$dispatcher = JDispatcher::getInstance();
$results = $dispatcher->trigger( '<EventName>', <ParameterArray> );

Методы с именами событий не требуют какой-либо регистрации и вызываются автоматически
function <EventName>() ...

Список событий https://docs.joomla.org/Plugin/Events

Работаем с запросом (не понятно как получать параметры, так что это здесь только для справки)
$option = $this->app->input->get('option');
$this->app->input->set('language', $lang_code);

Работаем с URI
$uri = JUri::getInstance();
$uri->getPath()
Методы $req = JRequest::get (  ), $uri = JFactory::getURI() являются deprecated

Работаем с сессией
JFactory::getSession()->set('plg_system_languagefilter.language', $languageCode);
$languageCode = JFactory::getSession()->get('plg_system_languagefilter.language');

В коде плагина доступны следующие переменные
    $this->params: набор параметров для данного плагина, заданный администратором
    $this->_name: имя плагина
    $this->_type: группа (тип) плагина
    $this->db: объект базы данных (since Joomla 3.1)
    $this->app: объект приложения (since Joomla 3.1)

*/
class plgSystemUrlparser extends JPlugin
{
	/**
	 * Load the language file on instantiation. Note this is only available in Joomla 3.1 and higher.
	 * If you want to support 3.0 series you must override the constructor
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;
	
	// Здесь нужно создать переменные $db, $app, если мы хотим создать их принудительно, а не использовать встроенные

	function onAfterInitialise (  ) {
        $uri = JUri::getInstance (  );
		// @TODO: Брать разделитель из параметров плагина
		// @TODO: Парсить более умно - запрос может содержать указанную строку, так что нужно брать последний токен, ну и так далее
        $parts = explode ( '--' , $uri -> getPath (  ) );
        if ( count ( $parts ) > 1) {
			// Убираем из запроса опции фильтрации
            $uri -> setPath ( $parts [0] );  // @WARNING: deprecated
			// И сохраняем их в сессии (после использования удолить)
            JFactory::getSession ( ) -> set ('plg_system_urlparser.filteroptions', $parts [1] );
        }
        // Получить из сессии можем так:
        // $filterOptions = JFactory::getSession()->get('plg_system_urlparser.filteroptions');

        // Это нам НЕНУЖЕН. Так для общего развития
        // $link = $uri->toString(array('path', 'query', 'fragment'));

		return true;
	}
}