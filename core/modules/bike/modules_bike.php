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
 *  \file       htdocs/core/modules/bike/modules_bike.php
 *  \ingroup    bike
 *  \brief      Fichier contenant la classe mere de generation des bikes en PDF
 *  			et la classe mere de numerotation des bikes
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';


/**
 *	Classe mere des modeles de numerotation des references de bikes
 */
abstract class ModeleNumRefBikes
{
	var $error='';

	/**
	 * Return if a module can be used or not
	 *
	 * @return	boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**
	 *  Rbike la description par defaut du modele de numerotation
	 *
	 * 	@return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("bike");
		return $langs->trans("NoDescription");
	}

	/**
	 * 	Rbike un exemple de numerotation
	 *
	 *  @return     string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("bike");
		return $langs->trans("NoExample");
	}

	/**
	 *  Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *  de conflits qui empechera cette numerotation de fonctionner.
	 *
	 *  @return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**
	 * 	Rbike prochaine valeur attribuee
	 *
	 *	@param		Societe		$objsoc     Object third party
	 *	@param		Tranche		$bike	Object bike
	 *	@return     string      Valeur
	 */
	function getNextValue($objsoc,$bike)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *  Rbike version du module numerotation
	 *
	 *  @return     string      Valeur
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		if ($this->version) return $this->version;
		return $langs->trans("NotAvailable");
	}
}
