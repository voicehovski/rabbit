<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_orangeei
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
 
defined('_JEXEC') or die('Restricted access');
 
// Require helper file
JLoader::register('RabbitHelper', JPATH_COMPONENT . '/helpers/rabbit.php');
session_write_close (  );
//JLoader::register('RabbitHelper', JPATH_COMPONENT . '/helpers/csvh.php');	не работает
if ( ! class_exists ( 'csvHelper' ) ) require ( JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'csvh.php' );
session_start (  );
 
$controller = JControllerLegacy::getInstance('Rabbit');
 
// Perform the Request task
$controller->execute(JFactory::getApplication()->input->get('task'));
 
// Redirect if set by the controller
$controller->redirect();