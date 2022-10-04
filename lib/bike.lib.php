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

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
/**
 *	\file       htdocs/bike/lib/bike.lib.php
 *	\brief      Ensemble de fonctions de base pour le module bike
 * 	\ingroup	bike
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function bike_prepare_admin_head()
{
	global $db, $langs, $conf, $user;
	$langs->load("bike@bike");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/bike/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'bike_admin');

    $head[$h][0] = dol_buildpath("/bike/admin/extrafields.php", 1);
	$head[$h][1] = $langs->trans("Extrafields");
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = dol_buildpath("/bike/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'bike_admin', 'remove');

    return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function bike_prepare_head($object)
{
	global $db, $langs, $conf, $user;
	$langs->load("bike@bike");

	$h = 0;
	$head = array();

	if ($user->rights->bike->lire)
	{
		$head[$h][0] = dol_buildpath("/bike/card.php?id=".$object->id, 1);
		$head[$h][1] = $langs->trans("BikeCard");
		$head[$h][2] = 'bike';
		$h++;
	}

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'bike');

    if (empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
        $nbNote = 0;
        if (!empty($object->note_private)) {
            $nbNote++;
        }
        if (!empty($object->note_public)) {
            $nbNote++;
        }
        $head[$h][0] = dol_buildpath("/bike/note.php?id=".$object->id, 1);
        $head[$h][1] = $langs->trans('Notes');
        if ($nbNote > 0) {
            $head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
        }
        $head[$h][2] = 'note';
        $h++;
    }

	$upload_dir = $conf->bike->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);

	$head[$h][0] = dol_buildpath("/bike/document.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = dol_buildpath("/bike/info.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'bike', 'remove');

    return $head;
}
