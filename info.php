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
 *      \file       htdocs/bike/info.php
 *      \ingroup    bike
 *		\brief      Page des info d'une vélo
 */

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once("/bike/class/bike.class.php");
dol_include_once("/bike/lib/bike.lib.php");

if (!$user->rights->bike->lire)	accessforbidden();


$langs->load("bike@bike");

$id = GETPOST("id",'int');
$ref=GETPOST('ref','alpha');

// Security check
$result=restrictedArea($user,'bike',$id,'');

$object = new Bike($db);
if (! $object->fetch($id, $ref) > 0)
{
    dol_print_error($db);
    exit;
}


/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans('Bike'));
$object->info($object->id);

$head = bike_prepare_head($object);
dol_fiche_head($head, 'info', $langs->trans("Bike"), 0, 'bike@bike');

// Order card

$url = dol_buildpath('/etatcommissions/list.php', 1).'?restore_lastsearch_values=1';
$linkback = '<a href="' . $url . '">' . $langs->trans("BackToList") . '</a>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();
