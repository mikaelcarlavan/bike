<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2016	Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018		Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2019		Nicolas ZABOURI		<info@inovea-conseil.com>
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
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 or 2 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error: this template page cannot be called directly as an URL";
	exit;
}

global $forcechargementhowtitlelines;

//print $object->element;
// Lines for extrafield

print "<!-- BEGIN PHP TEMPLATE objectline_create.tpl.php -->\n";
$nolinesbefore = (count($this->lines) == 0 || $forcechargementhowtitlelines);
if ($nolinesbefore) {
	?>
	<tr class="liste_titre<?php echo $nolinesbefore ? '' : ' liste_titre_add_'; ?> nodrag nodrop">
		<?php if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
			<td class="linecolnum center"></td>
		<?php } ?>
		<td class="linecoldescription minwidth500imp">
			<div id="add"></div><span class="hideonsmartphone"><?php echo $langs->trans('BikeAddNewNote'); ?></span>
		</td>
        <td class="linecolfkuser right"><?php echo $langs->trans('BikeUser'); ?></td>
        <td class="linecoledit" colspan="3">&nbsp;</td>
	</tr>
	<?php
}
?>
<tr class="pair nodrag nodrop nohoverpair<?php echo $nolinesbefore ? '' : ' liste_titre_create'; ?>">
	<?php
	// Adds a line numbering column
	if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
		echo '<td class="nobottom linecolnum center"></td>';
	}
	?>
	<td class="nobottom linecoldescription minwidth500imp">
		<?php

		if (is_object($hookmanager)) {
			$parameters = array();
			$reshook = $hookmanager->executeHooks('formCreateProductOptions', $parameters, $object, $action);
			if (!empty($hookmanager->resPrint)) {
				print $hookmanager->resPrint;
			}
		}

		// Editor wysiwyg
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$nbrows = ROWS_2;
		$enabled = (!empty($conf->global->FCKEDITOR_ENABLE_DETAILS) ? $conf->global->FCKEDITOR_ENABLE_DETAILS : 0);
		if (!empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) {
			$nbrows = $conf->global->MAIN_INPUT_DESC_HEIGHT;
		}
		$toolbarname = 'dolibarr_details';
		if (!empty($conf->global->FCKEDITOR_ENABLE_DETAILS_FULL)) {
			$toolbarname = 'dolibarr_notes';
		}
		$doleditor = new DolEditor('note', GETPOST('note', 'restricthtml'), '', (empty($conf->global->MAIN_DOLEDITOR_HEIGHT) ? 100 : $conf->global->MAIN_DOLEDITOR_HEIGHT), $toolbarname, '', false, true, $enabled, $nbrows, '98%');
		$doleditor->Create();
		?>
	</td>

	<td class="nobottom linecolfkuser right">
       <?php print $form->select_dolusers(GETPOST('fk_user', 'int'),  'fk_user', 1); ?>
	</td>

    <td class="nobottom linecoldatec right">
        &nbsp;
    </td>

	<td class="nobottom linecoledit center valignmiddle" colspan="3">
		<input type="submit" class="button reposition" value="<?php echo $langs->trans('Add'); ?>" name="addline" id="addline">
	</td>
</tr>

<?php
print "<!-- END PHP TEMPLATE objectline_create.tpl.php -->\n";

