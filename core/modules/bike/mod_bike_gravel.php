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
 * \file       htdocs/core/modules/bike/mod_bike_saphir.php
 * \ingroup    bike
 * \brief      File that contains the numbering module rules Saphir
 */

dol_include_once("/bike/core/modules/bike/modules_bike.php");


/**
 * Class of file that contains the numbering module rules Saphir
 */
class mod_bike_gravel extends ModeleNumRefBikes
{
		var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
		var $error = '';
		var $nom = 'Gravel';


    /**
     *  Return description of module
     *
     *  @return     string      Texte descripif
     */
		function info()
    {
				global $conf,$langs;

				$langs->load("bike@bike");

				$form = new Form($this->db);

				$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
				$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				$texte.= '<input type="hidden" name="action" value="updateMask">';
				$texte.= '<input type="hidden" name="maskconstbike" value="BIKE_GRAVEL_MASK">';
				$texte.= '<table class="nobordernopadding" width="100%">';

				$tooltip=$langs->trans("GenericMaskCodes",$langs->transnoentities("Bike"),$langs->transnoentities("Bike"));
				$tooltip.=$langs->trans("GenericMaskCodes2");
				$tooltip.=$langs->trans("GenericMaskCodes3");
				$tooltip.=$langs->trans("GenericMaskCodes4a",$langs->transnoentities("Bike"),$langs->transnoentities("Bike"));
				$tooltip.=$langs->trans("GenericMaskCodes5");

				// Parametrage du prefix
				$texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
				$texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskbike" value="'.$conf->global->BIKE_GRAVEL_MASK.'">',$tooltip,1,1).'</td>';

				$texte.= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';

				$texte.= '</tr>';

				$texte.= '</table>';
				$texte.= '</form>';

				return $texte;
    }

    /**
     *  Rbike un exemple de numerotation
     *
     *  @return     string      Example
     */
    function getExample()
    {
        global $conf,$langs,$mysoc;

        $old_code_client=$mysoc->code_client;
        $old_code_type=$mysoc->typent_code;
        $mysoc->code_client='CCCCCCCCCC';
        $mysoc->typent_code='TTTTTTTTTT';
        $numExample = $this->getNextValue($mysoc,'');
        $mysoc->code_client=$old_code_client;
        $mysoc->typent_code=$old_code_type;

        if (! $numExample)
        {
            $numExample = 'NotConfigured';
        }
        return $numExample;
    }


    /**
     * Return next value
     *
     * @param	Societe		$objsoc     Object third party
     * @param   Facture		$bike	Object bike
     * @param   string		$mode       'next' for next value or 'last' for last value
     * @return  string      			Value if OK, 0 if KO
     */
    function getNextValue($objsoc,$bike,$mode='next')
    {
        global $db,$conf;

        require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';

        // Get Mask value
        $mask=$conf->global->BIKE_GRAVEL_MASK;

        if (! $mask)
        {
            $this->error='NotConfigured';
            return 0;
        }

        $where='';

        $numFinal=get_next_value($db, $mask,'bike','ref', $where, $objsoc, $bike->datec, $mode);
        if (! preg_match('/([0-9])+/',$numFinal)) $this->error = $numFinal;

        return  $numFinal;
    }


    /**
     * Return next free value
     *
     * @param	Societe		$objsoc     	Object third party
     * @param	string		$objforref		Object for number to search
     * @param   string		$mode       	'next' for next value or 'last' for last value
     * @return  string      				Next free value
     */
    function getNumRef($objsoc,$objforref,$mode='next')
    {
        return $this->getNextValue($objsoc,$objforref,$mode);
	}
		
}
