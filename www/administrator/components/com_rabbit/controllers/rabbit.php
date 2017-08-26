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
 
class RabbitControllerRabbit extends JControllerForm
{
	public function translate ( $cachable = false, $urlparams = false ) {
		$this->setRedirect(JRoute::_('index.php?option=com_rabbit&view=translate', false) );
	}
		
	public function import ( $cachable = false, $urlparams = false ) {
		$this->setRedirect(JRoute::_('index.php?option=com_rabbit&view=import', false) );
	}
	
	public function check ( $cachable = false, $urlparams = false ) {
		$model = $this -> getModel ( 'rabbit' );
		$model -> storeUploadedFiles (  );
		$this->setRedirect(JRoute::_('index.php?option=com_rabbit&view=check', false) );
	}
	
	public function rollback ( $cachable = false, $urlparams = false ) {
		$this->setRedirect(JRoute::_('index.php?option=com_rabbit', false) );
	}
	
	public function close ( $cachable = false, $urlparams = false ) {
		$this->setRedirect(JRoute::_('index.php', false) );
	}
}