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

JFactory::getDocument()->addStyleDeclaration(
	'
	div.data-summary {
		background-color: #ada;
		font-size: 2em;
		line-height: 1.6;
	}
	
	div.data-full {
		height: 1em;
		overflow: hidden;
		margin-bottom: 2em;
	}
	
	div.whole-data {
		display: none;
	}
	'
);

?>
<form action="<?php echo JRoute::_('index.php?option=com_rabbit'); ?>"
    method="post" name="adminForm" id="adminForm">
    <div class="form-horizontal">
        <fieldset class="adminform">
            <legend><?php echo JText::_('COM_RABBIT_IMPORT_HEADER'); ?></legend>
            <div class="row-fluid">
                <div class="span6">
                    <?php foreach ($this->form->getFieldset() as $field): ?>
                        <div class="control-group">
                            <div class="control-label"><?php echo "control-label"; ?></div>
                            <div class="controls"><?php echo "controls"; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </fieldset>
		
    </div>
    <input type="hidden" name="task" value="rabbit" />
    <?php echo JHtml::_('form.token'); ?>
</form>