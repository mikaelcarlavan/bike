<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

/* Copyright (C) 2022	Mikael Carlavan	    <contact@mika-carl.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file 	htdocs/bike/card.php
 * \ingroup bike
 * \brief 	Page to show bike
 */

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

dol_include_once("/bike/class/bike.class.php");
dol_include_once("/bike/lib/bike.lib.php");

if (!empty($conf->stand->enabled)) {
    dol_include_once("/stand/class/html.form.stand.class.php");
    $langs->load("stand@stand");
}

$langs->load("bike@bike");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage','alpha');
$lineid = GETPOST('lineid', 'int');

$result = restrictedArea($user, 'bike', $id);

$object = new Bike($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('bikecard','globalcard'));

$permissiondellink = $user->rights->bike->creer; 	// Used by the include of actions_dellink.inc.php

/*
 * Actions
 */
$error = 0;

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once
	
	if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->bike->supprimer)
	{
		$result = $object->delete($user);
		if ($result > 0)
		{
			header('Location: list.php?restore_lastsearch_values=1');
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	// Add 
	else if ($action == 'add' && $user->rights->bike->creer)
	{
		$ref = $object->getNextNumRef($mysoc);

		$name = GETPOST('name', 'alpha');
		$note_public = GETPOST('note_public', 'restricthtml');
        $note_private = GETPOST('note_private', 'restricthtml');
		$code = GETPOST('code', 'alpha');
        $fk_user = GETPOST('fk_user', 'int');
        $fk_stand = GETPOST('fk_stand', 'int');

		$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
		if ($ret < 0) $error++;

		if (!$error)
		{
			$object->ref = $ref;
			$object->name 	= $name;
			$object->code = $code;
			$object->note_public 	= $note_public;
            $object->note_private 	= $note_private;
            $object->fk_user 	    = $fk_user;
            $object->fk_stand 	    = $fk_stand;

			$id = $object->create($user);
		}
		

		if ($id > 0 && ! $error)
		{
			header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
			exit;
		} else {
			$action = 'create';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
    else if ($action == 'enable' && !GETPOST('cancel','alpha'))
    {
        $object->active = 1;
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
    else if ($action == 'disable' && !GETPOST('cancel','alpha'))
    {
        $object->active = 0;
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
	else if ($action == 'setname' && !GETPOST('cancel','alpha'))
	{
		$object->name = GETPOST('name', 'alpha');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'setfk_user' && !GETPOST('cancel','alpha'))
	{
		$object->fk_user = GETPOST('fk_user', 'int');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
    else if ($action == 'setfk_stand' && !GETPOST('cancel','alpha'))
    {
        $object->fk_stand = GETPOST('fk_stand', 'int');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
    else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->bike->creer)
    {
        // Remove a product line
        $result = $object->deleteline($user, $lineid);
        if ($result > 0) {
            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
            exit;
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    } elseif ($action == 'addline' && $user->rights->bike->creer) {		// Add a new line
        $langs->load('errors');
        $error = 0;

        // Set if we used free entry or predefined product
        $note = (GETPOSTISSET('note') ? GETPOST('note', 'restricthtml') : '');
        $fk_user = GETPOST('fk_user', 'int');

        if (!$error) {

            // Insert line
            $result = $object->addline($note, $fk_user);

            if ($result > 0) {
                $ret = $object->fetch($object->id); // Reload to get new records

                unset($_POST['note']);
                unset($_POST['fk_user']);

            } else {
                setEventMessages($object->error, $object->errors, 'errors');
            }

        }
    }
    elseif ($action == 'updateline' && $user->rights->bike->creer && !$cancel)
    {
        // Update a line
        $note = (GETPOSTISSET('note') ? GETPOST('note', 'restricthtml') : '');
        $fk_user = GETPOST('fk_user', 'int');

        $result = $object->updateline(GETPOST('lineid', 'int'), $note, $fk_user);

        if ($result >= 0) {
            unset($_POST['note']);
            unset($_POST['fk_user']);
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }

    }
    elseif ($action == 'updateline' && $user->rights->bike->creer && $cancel)
    {
        header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id); // Pour reaffichage de la fiche en cours d'edition
        exit();
    }

	if ($action == 'update_extras')
	{
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute','none'));
		if ($ret < 0) $error++;

		if (! $error)
		{
			// Actions on extra fields
			$result = $object->insertExtraFields('BIKE_MODIFY');
			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) $action = 'edit_extras';
	}

	// Actions to build doc
	$upload_dir = $conf->bike->multidir_output[$object->entity];
	$permissiontoadd = $user->rights->bike->creer;
	$permissiontoedit = $user->rights->bike->creer;

	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

    // Action to move up and down lines of object
    include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';
}


/*
 *	View
 */

llxHeader('', $langs->trans('Bike'));

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

// Mode creation
if ($action == 'create' && $user->rights->bike->creer)
{
	print load_fiche_titre($langs->trans('NewBike'),'','bike2@bike');


	print '<form name="crea_bike" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';

	dol_fiche_head('');

	print '<table class="border" width="100%">';

	// Reference
	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('Ref') . '</td><td>';
    print  $object->getNextNumRef($mysoc);
    print '</td></tr>';

    // Name
    print '<tr><td>' . $langs->trans('BikeName') . '</td><td>';
    print '<input type="text" size="60"  class="flat" name="name" value="'.GETPOST('name').'">';
    print '</td></tr>';

    // Code
    print '<tr><td>' . $langs->trans('BikeCode') . '</td><td>';
    print '<input type="text" size="60"  class="flat" name="code" value="'.GETPOST('code').'">';
    print '</td></tr>';

    if (!empty($conf->stand->enabled)) {
        $standform = new StandForm($db);
        print '<tr><td>' . $langs->trans('BikeStand') . '</td><td>';
        print $standform->select_stand(GETPOST('fk_stand', 'int'), 'fk_stand', '', 1);
        print '</td></tr>';
    }

    print '<tr><td>' . $langs->trans('BikeUser') . '</td><td>';
    print $form->select_dolusers(GETPOST('fk_user', 'int'),  'fk_user', 1);
    print '</td></tr>';

	// Other attributes
	$parameters = array('objectsrc' => '', 'socid'=> '');
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'edit');
	}

    // Note
    print '<tr><td>' . $langs->trans('BikeNotePublic') . '</td><td>';
    $doleditor = new DolEditor('note_public', GETPOST("note_public", 'restricthtml'), '', 90, 'dolibarr_notes', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_SOCIETE'), ROWS_3, '90%');
    $doleditor->Create();
    print '</td></tr>';

    if (empty($user->socid)) {
        print '<tr><td>' . $langs->trans('BikeNotePrivate') . '</td><td>';
        $doleditor = new DolEditor('note_private', GETPOST("note_private", 'restricthtml'), '', 90, 'dolibarr_notes', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_SOCIETE'), ROWS_3, '90%');
        $doleditor->Create();
        print '</td></tr>';
    }

	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('CreateBike') . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';

} else {
	// Mode view
	$now = dol_now();

	if ($object->id > 0) 
	{

		$res = $object->fetch_optionals();

		$head = bike_prepare_head($object);
		
		dol_fiche_head($head, 'bike', $langs->trans("Bike"), -1, 'bike2@bike');

		$formconfirm = '';

		// Confirmation to delete
		if ($action == 'delete') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteBike'), $langs->trans('ConfirmDeleteBike'), 'confirm_delete', '', 0, 1);
		}

        if ($action == 'ask_deleteline') {
            $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteBikeLine'), $langs->trans('ConfirmDeleteBikeLine'), 'confirm_deleteline', '', 0, 1);
        }
		// Call Hook formConfirm
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
		elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;

		// Print form confirm
		print $formconfirm;

		// Bike card
		$url = dol_buildpath('/bike/list.php', 1).'?restore_lastsearch_values=1';
		$linkback = '<a href="' . $url . '">' . $langs->trans("BackToList") . '</a>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

		print '<div class="fichecenter">';

		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

        print '<table class="border" width="100%">';
        

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('BikeName');
        print '</td>';
        if ($action != 'editname')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editname&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetLicencePlate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editname') {
            print '<form name="setname" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setname">';
            print '<input type="text" class="flat" size="60" name="name" value="'.$object->name.'">';
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->name ? $object->name : '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('BikeCode');
        print '</td>';
        if ($action != 'editcode')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editcode&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetLicencePlate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editcode') {
            print '<form name="setcode" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setcode">';
            print '<input type="text" class="flat" size="60" name="code" value="'.$object->code.'">';
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->code ? $object->code : '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        if (!empty($conf->stand->enabled)) {
            $standform = new StandForm($db);

            print '<tr><td>';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('BikeStand');
            print '</td>';
            if ($action != 'editfk_stand')
                print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfk_stand&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetLicencePlate'), 1) . '</a></td>';
            print '</tr></table>';
            print '</td><td>';
            if ($action == 'editfk_stand') {
                print '<form name="setfk_stand" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
                print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
                print '<input type="hidden" name="action" value="setfk_stand">';
                print $standform->select_stand($object->fk_stand, 'fk_stand', '', 1);
                print '<input type="text" class="flat" size="60" name="zip" value="'.$object->zip.'">';
                print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
                print '</form>';
            } else {
                print $object->stand ? $object->stand->getNomUrl(1) : '&nbsp;';
            }
            print '</td>';
            print '</tr>';
        }

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('BikeUser');
        print '</td>';
        if ($action != 'editfk_user')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfk_user&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetLicencePlate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editfk_user') {
            print '<form name="setfk_user" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setfk_user">';
            print $form->select_dolusers($object->fk_user,  'fk_user', 1);
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->user ? $object->user->getNomUrl(1) : '&nbsp;';
        }
        print '</td>';
        print '</tr>';

		// Other attributes
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

        print '</table>';

        print '</div>';

        print '<div class="fichehalfright">';

        print '<div class="ficheaddleft">';
        print '<div class="underbanner clearboth"></div>';
        print '</div>';

        print '</div>';

        print '</div>';

        print '<div class="clearboth"></div><br />';

        /*
                 * Lines
                 */
        $result = $object->getLinesArray();

        print '<form name="addline" id="addline" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">';

        print '<div class="div-table-responsive-no-min">';
        print '<table id="tablelines" class="noborder noshadow" width="100%">';

        // Show object lines
        if (!empty($object->lines)) {
            $ret = $object->printObjectLines($action, $mysoc, $object->thirdparty, $lineid, 1);
        }

        $numlines = count($object->lines);

        /*
         * Form to add new line
         */
        if ($user->rights->bike->creer && $action != 'selectlines') {
            if ($action != 'editline') {
                // Add products
                $parameters = array();
                // Note that $action and $object may be modified by hook
                $reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action);
                if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                if (empty($reshook))
                    $object->formAddObjectLine(1, $mysoc, $object->thirdparty);
            }
        }
        print '</table>';
        print '</div>';

        print "</form>";

		dol_fiche_end();

        $object->fetchObjectLinked();

		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been

		// modified by hook
		if (empty($reshook)) {

            // Activate
            if (!$object->active && $user->rights->bike->creer) {
                print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=enable">'.$langs->trans('BikeActivate').'</a>';
            }
            // Deactivate
            if ($object->active && $user->rights->bike->creer) {
                print '<a class="butAction" href="card.php?id='.$object->id.'&amp;action=disable">'.$langs->trans('BikeDeactivate').'</a>';
            }
            // Delete
            if ($user->rights->bike->supprimer) {
                print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=delete">' . $langs->trans('DeleteBike') . '</a></div>';
            }
		}

		print '</div>';

        print '<div class="fichecenter">';
        print '<div class="fichehalfleft">';
        print '<a name="builddoc"></a>'; // ancre


        print '</div>';
        print '<div class="fichehalfright">';
        print '<div class="ficheaddleft">';

        // List of actions on element
        include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
        $formactions = new FormActions($db);
        $somethingshown = $formactions->showactions($object, 'bike', '', 1);

        print '</div>';
        print '</div>';
        print '</div>';

	}
}

// End of page
llxFooter();
$db->close();
