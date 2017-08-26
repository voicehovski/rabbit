<?php
/**
*
* Description
*
* @package	VirtueMart
* @subpackage Category
* @author RickG, jseros
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: edit.php 9515 2017-04-29 10:58:57Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

AdminUIHelper::startAdminArea($this);
$editor = JFactory::getEditor();
$origLang = '';
if(!empty($this->category->_loadedWithLangFallback)){
	$origLang = '(<span class="allflags flag-'.$this->category->_loadedWithLangFallback.'"></span>)';
}

$this->origLang = '<span class="langfallback">'.$origLang.'</span>';
?>

<form action="index.php" method="post" id="adminForm" name="adminForm" enctype="multipart/form-data">

<?php // Loading Templates in Tabs
AdminUIHelper::buildTabs ( $this, array (	'categoryform' 	=> 	'COM_VIRTUEMART_CATEGORY_FORM_LBL',
                                            'categoryparams' 	=> 	'COM_MENUS_DISPLAY_FIELDSET_LABEL',
									'images' 	=> 	'COM_VIRTUEMART_IMAGES'
									 ),$this->category->virtuemart_category_id );
?>
	<input type="hidden" name="virtuemart_category_id" value="<?php echo $this->category->virtuemart_category_id; ?>" />

	<?php echo $this->addStandardHiddenToForm(); ?>

</form>

<?php AdminUIHelper::endAdminArea(); ?>