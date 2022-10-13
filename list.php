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
 *  \file       htdocs/bike/list.php
 *  \ingroup    bike
 *  \brief      Page to list bike
 */


$res = @include("../main.inc.php");                   // For root directory
if (!$res) $res = @include("../../main.inc.php");    // For "custom" directory

require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

dol_include_once("/bike/class/bike.class.php");

if (!empty($conf->stand->enabled)) {
    dol_include_once("/stand/class/stand.class.php");
    dol_include_once("/stand/class/html.form.stand.class.php");
    $langs->load("stand@stand");
}

$langs->load("bike@bike");

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'bikelist';

$search_cyear = GETPOST("search_cyear", "int");
$search_cmonth = GETPOST("search_cmonth", "int");
$search_cday = GETPOST("search_cday", "int");

$search_ref = GETPOST('search_ref', 'alpha') != '' ? GETPOST('search_ref', 'alpha') : GETPOST('sref', 'alpha');

$search_user_author_id = GETPOST('search_user_author_id', 'int');

$search_name = GETPOST('search_name');
$search_code = GETPOST('search_code');
$search_fk_stand = GETPOST('search_fk_stand');
$search_fk_user = GETPOST('search_fk_user');

$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));


$optioncss = GETPOST('optioncss', 'alpha');
$search_btn = GETPOST('button_search', 'alpha');
$search_remove_btn = GETPOST('button_removefilter', 'alpha');

// Security check
$id = GETPOST('id', 'int');
$result = restrictedArea($user, 'bike', $id, '');

$diroutputmassaction = $conf->bike->dir_output . '/temp/massgeneration/' . $user->id;

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) {
    $page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = 'e.ref';
if (!$sortorder) $sortorder = 'DESC';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Bike($db);
$hookmanager->initHooks(array('bikelist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('bike');
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldsbikeearchall = array(
    'e.ref' => 'Ref',
);

$arrayfields = array(
    'e.ref' => array('label' => $langs->trans("Ref"), 'checked' => 1),
    'e.name' => array('label' => $langs->trans("BikeName"), 'checked' => 1),
    'e.code' => array('label' => $langs->trans("BikeCode"), 'checked' => 1),
    'e.fk_stand' => array('label' => $langs->trans("BikeStand"), 'checked' => 1, 'enabled' => $conf->stand->enabled),
    'e.fk_user' => array('label' => $langs->trans("BikeUser"), 'checked' => 1),
    'e.datec' => array('label' => $langs->trans("DateCreation"), 'checked' => 1),
    'e.tms' => array('label' => $langs->trans("DateModificationShort"), 'checked' => 0, 'position' => 500),
    'e.active' => array('label' => $langs->trans("BikeActive"), 'checked' => 1, 'position' => 1000),
);

// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
    foreach ($extrafields->attribute_label as $key => $val) {
        if (!empty($extrafields->attribute_list[$key])) $arrayfields["ef." . $key] = array('label' => $extrafields->attribute_label[$key], 'checked' => (($extrafields->attribute_list[$key] < 0) ? 0 : 1), 'position' => $extrafields->attribute_pos[$key], 'enabled' => (abs($extrafields->attribute_list[$key]) != 3 && $extrafields->attribute_perms[$key]));
    }
}


/*
 * Actions
 */

$error = 0;

if (GETPOST('cancel', 'alpha')) {
    $action = 'list';
    $massaction = '';
}
//if (! GETPOST('confirmmassaction','alpha')) { $massaction=''; }

$parameters = array('socid' => '');
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    // Selection of new fields
    include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

    if ($action == 'enable' && !GETPOST('cancel','alpha'))
    {
        $object->fetch($id);

        $object->active = 1;
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
    else if ($action == 'disable' && !GETPOST('cancel','alpha'))
    {
        $object->fetch($id);

        $object->active = 0;
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
    {
        $search_cyear = '';
        $search_cmonth = '';
        $search_cday = '';

        $search_name = '';
        $search_code = '';
        $search_fk_stand = -1;
        $search_fk_user = -1;

        $search_ref = '';
        $search_user_author_id = '';

        $toselect = '';
        $search_array_options = array();
    }
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
        || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
        $massaction = '';     // Protection to avoid mass action if we force a new search during a mass action confirmation
    }

    // Mass actions
    $objectclass = 'Bike';
    $objectlabel = 'Bikes';
    $permtoread = $user->rights->bike->lire;
    $permtodelete = $user->rights->bike->supprimer;
    $permtomodify = $user->rights->bike->modifier;
    $uploaddir = $conf->bike->dir_output;

    $paramname = 'id';
    $autocopy = 'MAIN_MAIL_AUTOCOPY_TO';    // used to know the automatic BCC to add
    $trackid = 'sta' . $object->id;
    $sendcontext = 'bike';

    include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
}


/*
 * View
 */

$now = dol_now();

$form = new Form($db);
$formother = new FormOther($db);

$userstatic = new User($db);

$title = $langs->trans("Bikes");
$help_url = "";

$sql = 'SELECT';
if ($sall) $sql = 'SELECT DISTINCT';
$sql .= " e.rowid, e.ref, e.active, e.datec, e.name, e.code, e.fk_user, e.fk_stand, e.user_author_id, e.entity, e.tms, ";
$sql .= " s.rowid as stand_id, s.ref as stand_ref, s.name as stand_name, ";
$sql .= " u.rowid as user_id, u.login as user_login, u.firstname as user_firstname, u.lastname as user_lastname ";

// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql .= ($extrafields->attribute_type[$key] != 'separate' ? ",ef." . $key . ' as options_' . $key : '');
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= ' FROM ' . MAIN_DB_PREFIX . 'bike as e';

if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bike_extrafields as ef on (e.rowid = ef.fk_object)";

$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'stand as s ON e.fk_stand = s.rowid';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as u ON e.fk_user = u.rowid';

$sql .= ' WHERE e.entity IN (' . getEntity('bike') . ')';
if ($search_ref) $sql .= natural_search('e.ref', $search_ref);
if ($sall) $sql .= natural_search(array_keys($fieldsbikeearchall), $sall);

if ($search_cmonth > 0) {
    if ($search_cyear > 0 && empty($search_cday))
        $sql .= " AND e.datec BETWEEN '" . $db->idate(dol_get_first_day($search_cyear, $search_cmonth, false)) . "' AND '" . $db->idate(dol_get_last_day($search_cyear, $search_cmonth, false)) . "'";
    else if ($search_cyear > 0 && !empty($search_cday))
        $sql .= " AND e.datec BETWEEN '" . $db->idate(dol_mktime(0, 0, 0, $search_cmonth, $search_cday, $search_cyear)) . "' AND '" . $db->idate(dol_mktime(23, 59, 59, $search_cmonth, $search_cday, $search_cyear)) . "'";
    else
        $sql .= " AND date_format(e.datec, '%m') = '" . $search_cmonth . "'";
} else if ($search_cyear > 0) {
    $sql .= " AND e.datec BETWEEN '" . $db->idate(dol_get_first_day($search_cyear, 1, false)) . "' AND '" . $db->idate(dol_get_last_day($search_cyear, 12, false)) . "'";
}


if ($search_name) $sql .= natural_search('e.name', $search_name);
if ($search_code) $sql .= natural_search('e.code', $search_code);

if ($search_fk_stand > 0) $sql .= " AND e.fk_stand = " . $search_fk_stand;
if ($search_fk_user > 0) $sql .= " AND e.fk_user = " . $search_fk_user;

if ($search_user_author_id > 0) $sql .= " AND e.user_author_id = " . $search_user_author_id;

// Add where from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);

    if (($page * $limit) > $nbtotalofrecords)  // if total resultset is smaller then paging size (filtering), goto and load page 0
    {
        $page = 0;
        $offset = 0;
    }
}

$sql .= $db->plimit($limit + 1, $offset);
//print $sql;

$resql = $db->query($sql);
if ($resql) {
    $title = $langs->trans('ListOfBikes');

    $num = $db->num_rows($resql);

    $arrayofselected = is_array($toselect) ? $toselect : array();

    if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall) {
        $obj = $db->fetch_object($resql);
        $id = $obj->rowid;
        $url = dol_buildpath('/bike/card.php', 1) . '?id=' . $id;

        header("Location: " . $url);
        exit;
    }

    llxHeader('', $title, $help_url);

    $param = '';

    if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
    if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . urlencode($limit);
    if ($sall) $param .= '&sall=' . urlencode($sall);

    if ($search_cday) $param .= '&search_cday=' . urlencode($search_cday);
    if ($search_cmonth) $param .= '&search_cmonth=' . urlencode($search_cmonth);
    if ($search_cyear) $param .= '&search_cyear=' . urlencode($search_cyear);

    if ($search_ref) $param .= '&search_ref=' . urlencode($search_ref);

    if ($search_name) $param .= '&search_name=' . urlencode($search_name);
    if ($search_code) $param .= '&search_code=' . urlencode($search_code);

    if ($search_fk_user > 0) $param .= '&search_fk_user=' . urlencode($search_fk_user);
    if ($search_fk_stand > 0) $param .= '&search_fk_stand=' . urlencode($search_fk_stand);


    if ($search_user_author_id > 0) $param .= '&search_user_author_id=' . urlencode($search_user_author_id);

    if ($optioncss != '') $param .= '&optioncss=' . urlencode($optioncss);

    // Add $param from extra fields
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

    // List of mass actions available
    $arrayofmassactions = array();

    if ($user->rights->bike->supprimer) $arrayofmassactions['predelete'] = $langs->trans("MassActionDelete");
    if (in_array($massaction, array('predelete'))) $arrayofmassactions = array();
    $massactionbutton = $form->selectMassAction('', $arrayofmassactions);

    $newcardbutton = '';
    if ($contextpage == 'bikelist' && $user->rights->bike->creer) {
        $newcardbutton = '<a class="butActionNew" href="' . dol_buildpath('/bike/card.php?action=create', 2) . '"><span class="valignmiddle">' . $langs->trans('NewBike') . '</span>';
        $newcardbutton .= '<span class="fa fa-plus-circle valignmiddle"></span>';
        $newcardbutton .= '</a>';
    }

    // Lines of title fields
    print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="action" value="list">';
    print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
    print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
    print '<input type="hidden" name="page" value="' . $page . '">';
    print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';


    print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'bike2@bike', 0, $newcardbutton, '', $limit);

    $topicmail = "SendBikeRef";
    $modelmail = "bike_send";
    $objecttmp = new Bike($db);
    $trackid = 'cha' . $object->id;

    include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

    if ($sall) {
        foreach ($fieldsbikeearchall as $key => $val) $fieldsbikeearchall[$key] = $langs->trans($val);
        print '<div class="divsearchfieldfilter">' . $langs->trans("FilterOnInto", $sall) . join(', ', $fieldsbikeearchall) . '</div>';
    }

    $moreforfilter = '';

    // If the user can view other users
    if ($user->rights->user->user->lire) {
        $moreforfilter .= '<div class="divsearchfield">';
        $moreforfilter .= $langs->trans('CreatedByUsers') . ': ';
        $moreforfilter .= $form->select_dolusers($search_user_author_id, 'search_user_author_id', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
        $moreforfilter .= '</div>';
    }

    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters);    // Note that $action and $object may have been modified by hook
    if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
    else $moreforfilter = $hookmanager->resPrint;

    if (!empty($moreforfilter)) {
        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        print '</div>';
    }

    $varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
    $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);  // This also change content of $arrayfields
    $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

    print '<tr class="liste_titre_filter">';

    // Ref
    if (!empty($arrayfields['e.ref']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" size="6" type="text" name="search_ref" value="' . $search_ref . '">';
        print '</td>';
    }

    if (!empty($arrayfields['e.name']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" size="10" type="text" name="search_name" value="' . $search_name . '">';
        print '</td>';
    }

    if (!empty($arrayfields['e.code']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" size="10" type="text" name="search_code" value="' . $search_code . '">';
        print '</td>';
    }

    if (!empty($arrayfields['e.fk_stand']['checked']) && !empty($conf->stand->enabled)) {
        $standform = new StandForm($db);
        print '<td class="liste_titre">';
        print $standform->select_stand($search_fk_stand, 'search_fk_stand', '', 1);
        print '</td>';
    }

    if (!empty($arrayfields['e.fk_user']['checked'])) {
        print '<td class="liste_titre">';
        print $form->select_dolusers($search_fk_user,  'search_fk_user', 1);
        print '</td>';
    }

    // Extra fields
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';
    // Fields from hook
    $parameters = array('arrayfields' => $arrayfields);
    $reshook = $hookmanager->executeHooks('printFieldListOption', $parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    // Date de saisie
    if (!empty($arrayfields['e.datec']['checked'])) {
        print '<td class="liste_titre nowraponall" align="left">';
        if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_cday" value="' . $search_cday . '">';
        print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_cmonth" value="' . $search_cmonth . '">';
        $formother->select_year($search_cyear ? $search_cyear : -1, 'search_cyear', 1, 20, 5);
        print '</td>';
    }

    // Date modification
    if (!empty($arrayfields['e.tms']['checked'])) {
        print '<td class="liste_titre">';
        print '</td>';
    }

    if (!empty($arrayfields['e.active']['checked'])) {
        print '<td class="liste_titre">&nbsp;</td>';
    }

    // Action column
    print '<td class="liste_titre" align="middle">';
    $searchpicto = $form->showFilterButtons();
    print $searchpicto;
    print '</td>';

    print "</tr>\n";

    // Fields title
    print '<tr class="liste_titre">';
    if (!empty($arrayfields['e.ref']['checked'])) print_liste_field_titre($arrayfields['e.ref']['label'], $_SERVER["PHP_SELF"], 'e.ref', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['e.name']['checked'])) print_liste_field_titre($arrayfields['e.name']['label'], $_SERVER["PHP_SELF"], 'e.name', '', $param, '', $sortfield, $sortorder, '');
    if (!empty($arrayfields['e.code']['checked'])) print_liste_field_titre($arrayfields['e.code']['label'], $_SERVER["PHP_SELF"], 'e.code', '', $param, '', $sortfield, $sortorder, '');
    if (!empty($arrayfields['e.fk_stand']['checked']) && !empty($conf->stand->enabled)) print_liste_field_titre($arrayfields['e.fk_stand']['label'], $_SERVER["PHP_SELF"], 'e.fk_stand', '', $param, '', $sortfield, $sortorder, '');
    if (!empty($arrayfields['e.fk_user']['checked'])) print_liste_field_titre($arrayfields['e.fk_user']['label'], $_SERVER["PHP_SELF"], 'e.fk_user', '', $param, '', $sortfield, $sortorder, '');

    // Extra fields
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';
    // Hook fields
    $parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
    $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    if (!empty($arrayfields['e.datec']['checked'])) print_liste_field_titre($arrayfields['e.datec']['label'], $_SERVER["PHP_SELF"], 'e.datec', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['e.tms']['checked'])) print_liste_field_titre($arrayfields['e.tms']['label'], $_SERVER["PHP_SELF"], "e.tms", "", $param, 'align="left" class="nowrap"', $sortfield, $sortorder);
    if (!empty($arrayfields['e.active']['checked'])) print_liste_field_titre($arrayfields['e.active']['label'], $_SERVER["PHP_SELF"], "e.active", "", $param, 'align="left" class="nowrap"', $sortfield, $sortorder);

    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', $param, 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
    print '</tr>' . "\n";

    $productstat_cache = array();

    $generic_bike = new Bike($db);
    $generic_user = new User($db);
    $generic_stand = new Stand($db);

    $i = 0;
    $totalarray = array('nbfield' => 0);
    while ($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);


        $generic_bike->id = $obj->rowid;
        $generic_bike->ref = $obj->ref;

        $generic_stand->id = $obj->stand_id;
        $generic_stand->ref = $obj->stand_ref;
        $generic_stand->name = $obj->stand_name;

        $generic_user->id = $obj->user_id;
        $generic_user->login = $obj->user_login;
        $generic_user->firstname = $obj->user_firstname;
        $generic_user->lastname = $obj->user_lastname;

        print '<tr class="oddeven">';

        // Ref
        if (!empty($arrayfields['e.ref']['checked'])) {
            print '<td class="nowrap">';

            print $generic_bike->getNomUrl(1);

            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }


        //
        if (!empty($arrayfields['e.name']['checked'])) {
            print '<td align="left">';
            print $obj->name;
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }


        if (!empty($arrayfields['e.code']['checked'])) {
            print '<td align="left">';
            print $obj->code;
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }


        if (!empty($arrayfields['e.fk_stand']['checked']) && !empty($conf->stand->enabled)) {
            print '<td align="left">';
            print $obj->fk_stand > 0 ? $generic_stand->getNomUrl(1) : '&nbsp;';
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['e.fk_user']['checked'])) {
            print '<td align="left">';
            print $obj->fk_user > 0 ? $generic_user->getNomUrl(1) : '&nbsp;';
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Extra fields
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';
        // Fields from hook
        $parameters = array('arrayfields' => $arrayfields, 'obj' => $obj);
        $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        //
        if (!empty($arrayfields['e.datec']['checked'])) {
            print '<td align="left">';
            print dol_print_date($db->jdate($obj->datec), 'day');
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Date modification
        if (!empty($arrayfields['e.tms']['checked'])) {
            print '<td align="left" class="nowrap">';
            print dol_print_date($db->jdate($obj->tms), 'dayhour', 'tzuser');
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Activated or not
        if (!empty($arrayfields['e.active']['checked'])) {
            print '<td class="center">';
            if (empty($obj->active)) {
                print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$obj->rowid.'&action=enable&mode=0&token='.newToken().'">';
                print img_picto($langs->trans("Disabled"), 'switch_off');
                print '</a>';
            } else {
                print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$obj->rowid.'&action=disable&mode=0&token='.newToken().'">';
                print img_picto($langs->trans("Activated"), 'switch_on');
                print '</a>';
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Action column
        print '<td class="nowrap" align="center">';
        if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
        {
            $selected = 0;
            if (in_array($obj->rowid, $arrayofselected)) $selected = 1;
            print '<input id="cb' . $obj->rowid . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $obj->rowid . '"' . ($selected ? ' checked="checked"' : '') . '>';
        }
        print '</td>';
        if (!$i) $totalarray['nbfield']++;

        print "</tr>\n";

        $i++;
    }

    $db->free($resql);

    $parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
    $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    print '</table>' . "\n";
    print '</div>';

    print '</form>' . "\n";

} else {
    dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
