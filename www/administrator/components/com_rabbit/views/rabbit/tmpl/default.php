<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_orangeei
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
 
// No direct access
defined('_JEXEC') or die('Restricted access');

JFactory::getDocument()->addScriptDeclaration(
	'
	Rabbit.some_function = function() {
		var form = document .getElementById ( "some_id" );
		//...
	};

	jQuery ( document ) .ready ( function ( $ ) {
		
	} );
	'
);

JFactory::getDocument()->addStyleDeclaration(
	'
	div {
		
	}
	'
);
?>
<form action="<?php echo JRoute::_('index.php?option=com_rabbit&view=check'); ?>"
    method="post" name="adminForm" id="adminForm" enctype="multipart/form-data" >
    <div class="form-horizontal">
        <fieldset class="adminform">
            <legend><?php echo JText::_('COM_RABBIT_RABBIT_DETAILS'); ?></legend>
            <div class="row-fluid">
                <div class="span6">
                    <?php foreach ($this->form->getFieldset() as $field): ?>
                        <div class="control-group">
                            <div class="control-label"><?php echo $field->label; ?></div>
                            <div class="controls"><?php echo $field->input; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </fieldset>
    </div>
    <input type="hidden" name="task" value="rabbit.check">
    <?php echo JHtml::_('form.token'); ?>
</form>