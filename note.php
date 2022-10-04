<?php
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
 *  \file       htdocs/bike/note.php
 *  \ingroup    bike
 *  \brief      Page to show bike's notes
 */


$res = @include("../main.inc.php");                   // For root directory
if (!$res) $res = @include("../../main.inc.php");    // For "custom" directory

dol_include_once("/bike/class/bike.class.php");
dol_include_once("/bike/lib/bike.lib.php");

// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills'));
$langs->load('bike@bike');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$action = GETPOST('action', 'aZ09');

// Security check
$socid = 0;
if ($user->socid) {
    $socid = $user->socid;
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('bikenote'));

$result = restrictedArea($user, 'bike', $id);


$object = new Bike($db);
if (!$object->fetch($id, $ref) > 0) {
    dol_print_error($db);
    exit;
}

$permissionnote = $user->rights->bike->creer; // Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

$reshook = $hookmanager->executeHooks('doActions', array(), $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
    include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once
}


/*
 * View
 */
$title = $langs->trans('Bike')." - ".$langs->trans('Notes');
$help_url = '';
llxHeader('', $title, $help_url);

$form = new Form($db);

if ($id > 0 || !empty($ref)) {

    $head = bike_prepare_head($object);

    print dol_get_fiche_head($head, 'note', $langs->trans("Bike"), -1, 'bike');

    // Bike card

    $linkback = '<a href="'.DOL_URL_ROOT.'/bike/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');


    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';


    $cssclass = "titlefield";
    include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

    print '</div>';

    print dol_get_fiche_end();
}


// End of page
llxFooter();
$db->close();
