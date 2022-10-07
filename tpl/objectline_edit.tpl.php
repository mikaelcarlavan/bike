<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2020	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $seller, $buyer
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $canchangeproduct (0 by default, 1 to allow to change the product if it is a predefined product)
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}


$usemargins = 0;
if (!empty($conf->margin->enabled) && !empty($object->element) && in_array($object->element, array('facture', 'facturerec', 'propal', 'commande'))) {
	$usemargins = 1;
}

global $forceall, $senderissupplier, $inputalsopricewithtax, $canchangeproduct;
if (empty($dateSelector)) {
	$dateSelector = 0;
}
if (empty($forceall)) {
	$forceall = 0;
}
if (empty($senderissupplier)) {
	$senderissupplier = 0;
}
if (empty($inputalsopricewithtax)) {
	$inputalsopricewithtax = 0;
}
if (empty($canchangeproduct)) {
	$canchangeproduct = 0;
}

// Define colspan for the button 'Add'
$colspan = 3; // Col total ht + col edit + col delete
if (!empty($inputalsopricewithtax)) {
	$colspan++; // We add 1 if col total ttc
}
if (in_array($object->element, array('propal', 'supplier_proposal', 'facture', 'facturerec', 'invoice', 'commande', 'order', 'order_supplier', 'invoice_supplier'))) {
	$colspan++; // With this, there is a column move button
}
if (!empty($conf->multicurrency->enabled) && $this->multicurrency_code != $conf->currency) {
	$colspan += 2;
}

print "<!-- BEGIN PHP TEMPLATE objectline_edit.tpl.php -->\n";

$coldisplay = 0;
?>
<tr class="oddeven tredited">
<?php if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
		<td class="linecolnum center"><?php $coldisplay++; ?><?php echo ($i + 1); ?></td>
<?php }

$coldisplay++;
?>
	<td>
	<div id="line_<?php echo $line->id; ?>"></div>

	<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">

	<?php

	if (is_object($hookmanager)) {
		$parameters = array('line'=>$line, 'buyer'=>$buyer);
		$reshook = $hookmanager->executeHooks('formEditProductOptions', $parameters, $this, $action);
	}

	// Do not allow editing during a situation cycle
    // editor wysiwyg
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $nbrows = ROWS_2;
    if (!empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) {
        $nbrows = $conf->global->MAIN_INPUT_DESC_HEIGHT;
    }
    $enable = (isset($conf->global->FCKEDITOR_ENABLE_DETAILS) ? $conf->global->FCKEDITOR_ENABLE_DETAILS : 0);
    $toolbarname = 'dolibarr_details';
    if (!empty($conf->global->FCKEDITOR_ENABLE_DETAILS_FULL)) {
        $toolbarname = 'dolibarr_notes';
    }
    $doleditor = new DolEditor('note', $line->note, '', (empty($conf->global->MAIN_DOLEDITOR_HEIGHT) ? 164 : $conf->global->MAIN_DOLEDITOR_HEIGHT), $toolbarname, '', false, true, $enable, $nbrows, '98%');
    $doleditor->Create();


	//Line extrafield
	if (!empty($extrafields)) {
		$temps = $line->showOptionals($extrafields, 'edit', array('class'=>'tredited'), '', '', 1, 'line');
		if (!empty($temps)) {
			print '<div style="padding-top: 10px" id="extrafield_lines_area_edit" name="extrafield_lines_area_edit">';
			print $temps;
			print '</div>';
		}
	}

	?>
	</td>

    <td class="nobottom linecolfkuser right">
        <?php print $form->select_dolusers($line->fk_user,  'fk_user', 1); ?>
    </td>

    <td class="nobottom linecoluserphone right">
        <input type="text" size="40" name="user_phone" id="user_phone" class="flat right" value="<?php echo $line->user_phone; ?>">
    </td>

	<!-- colspan for this td because it replace total_ht+3 td for buttons+... -->
	<td class="center valignmiddle" colspan="3">
		<input type="submit" class="button buttongen marginbottomonly button-save" id="savelinebutton marginbottomonly" name="save" value="<?php echo $langs->trans("Save"); ?>"><br>
		<input type="submit" class="button buttongen marginbottomonly button-cancel" id="cancellinebutton" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
	</td>
</tr>
<!-- END PHP TEMPLATE objectline_edit.tpl.php -->
