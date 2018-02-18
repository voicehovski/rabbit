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

?>
<form action="<?php echo JRoute::_('index.php?option=com_rabbit'); ?>"
    method="post" name="adminForm" id="adminForm">
    <div class="form-horizontal">
        <fieldset class="adminform">
            <legend><?php echo JText::_('COM_RABBIT_TRANLATE_CHECK_OK_HEADER'); ?></legend>
            <div class="row-fluid">
                <div class="span6">
                    <?php foreach ($this->form->getFieldset() as $field): ?>
                        <div class="control-group">
                            <div class="control-label"><?php echo "control-label"; ?></div>
                            <div class="controls"><?php echo "controls"; ?></div>
							<p>Список внесенных изменений или сводные данные</p>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </fieldset>
        <fieldset>
			<legend><?php echo JText::_('COM_RABBIT_TRANLATE_CHECK_OK_DETAILS') . ': Переведено'; ?></legend>
			<?php //if ( ! $this -> import_struct ) { ?>
				<div> Empty session </div>
			<?php //} else { ?>
			<?php //foreach ( $this -> import_struct as $is ) { ?>
				<div>
					<?php //echo $is; ?>
				</div>
			<?php //} ?>
			<?php //} ?>
        </fieldset>
    </div>
    <input type="hidden" name="task" value="rabbit" />
    <?php echo JHtml::_('form.token'); ?>
</form>