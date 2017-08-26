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


class RabbitModelRabbit extends JModelAdmin
{
	
	public function getTable($type = 'Rabbit', $prefix = 'RabbitTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}
	
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm(
			'com_rabbit.rabbit',
			'rabbit',
			array(
				'control' => 'jform',
				'load_data' => false
			)
		);
 
		if (empty($form))
		{
			return false;
		}
 
		return $form;
	}
	
	public function storeUploadedFiles (  ) {
		$input    = JFactory::getApplication()->input;
		$userfile = $input->files->get('jform', null, 'raw');
		
		$tmp_src = $userfile [ 'import_file' ] ['tmp_name'];
		//$tmp_dest = JPATH_ROOT . '/public_html/tmp/' . $userfile [ 'import_file' ] [ 'name' ];
		$tmp_dest = '/tmp/' . $userfile [ 'import_file' ] [ 'name' ];
		
		jimport('joomla.filesystem.file');
		JFile::upload($tmp_src, $tmp_dest, false, true);
		//echo JFile::copy($tmp_src, $tmp_dest);
		//move_uploaded_file ($tmp_src, $tmp_dest);

		// $package = JInstallerHelper::unpack($tmp_dest, true);
		// return $package;
	}
}