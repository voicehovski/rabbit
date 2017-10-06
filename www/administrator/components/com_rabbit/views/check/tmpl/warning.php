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
            <legend><?php echo JText::_('COM_RABBIT_CHECK_WARNING_DETAILS'); ?></legend>
            <div class="row-fluid">
                <div class="span6">
					
					<?php
					
						echo "<h2>Syntax errors</h2>";
						foreach ( $productList -> products (  ) as $product ) {
							$le = $product -> errors (  );// array
							if ( ! empty ( $le ) ) {
								// create row error makeup
							}
							foreach ( $product -> properties (  ) as $property ) {
								$se = $property -> errors (  );
								if ( ! empty ( $se ) ) {
									echo $se -> rowIndex (  ); // ...
									// or $poduct -> rowIndex ... $property -> colIndex (  ) ... $se -> comment (  )
									// create cell error makeup
								}
							}
						}
						// print row error makeup
						// print cell error makeup
						
					
						echo "<table>";
						echo "<tr>";
						echo "<th>#</th>";
						foreach ( $headeList as $header ) {
							echo "<th>$header</th>";
						}
						echo "</tr>";
						foreach ( $productList -> products (  ) as $product ) {
							
							$class = "";
							$tooltip = "";
							$le = $product -> errors (  );
							if ( ! empty ( $le ) ) {
								$tooltip = "title='" . array_reduce ( $le, function ( $carry, $item ) { return $carry . "::" . $item -> comment (  ) }, "" ) . "'";
								$class = "class='wrong-row'";
							}
							echo "<tr $tooltip $class >";
							echo "<td>" . $product -> rowIndex (  ) . "</td>";
							
							foreach ( $product -> properties (  ) as $property ) {
								
								$class = "";
								$tooltip = "";
								$se = $property -> errors (  );
								if ( ! empty ( $se ) ) {
									$class = "class='wrong-cell'";
									$tooltip = "title='" . $se -> comment (  ) . "'";
								}
								echo "<td $tooltip $class >" . $property -> value (  ) . "</td>";
							}
							
							echo "</tr>"
						}
						echo "</table>";
					?>
					
                    <?php foreach ($this->form->getFieldset() as $field): ?>
                        <div class="control-group">
                            <div class="control-label"><?php echo "control-label"; ?></div>
                            <div class="controls"><?php echo "controls"; ?></div>
							<p>Список ошибок</p>
							<?php var_dump ($field); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </fieldset>
    </div>
    <input type="hidden" name="task" value="" />
    <?php echo JHtml::_('form.token'); ?>
</form>