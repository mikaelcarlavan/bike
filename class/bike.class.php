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
 *  \file       htdocs/bike/class/bike.class.php
 *  \ingroup    bike
 *  \brief      File of class to manage bikes
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

if (!empty($conf->stand->enabled)) {
    dol_include_once("/stand/class/stand.class.php");
}

/**
 * Class to manage products or services
 */
class Bike extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'bike';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'bike';

	/**
	 * @var string Name of subtable line
	 */
	public $table_element_line = 'bikedet';

	/**
	 * @var string Name of class line
	 */
	public $class_element_line = 'BikeLine';

	/**
	 * @var string Field name with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_bike';

	/**
	 * @var string String with name of icon for commande class. Here is object_order.png
	 */
	public $picto = 'bike@bike';

	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;
	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'rowid';

	/**
     * Gestion id
     * @var int
     */
	public $id = 0;

	/**
	 * Reference.
	 * @var string
	 */
	public $ref;

    /**
     * Name.
     * @var string
     */
    public $name;

    /**
     * Code.
     * @var string
     */
    public $code;

    /**
     * Current user id
     * @var int
     */
    public $fk_user;

    /**
     * Current stand id
     * @var int
     */
    public $fk_stand;

    /**
     * @var string Public note
     * @see update_note()
     */
    public $note_public;

    /**
     * @var string Private note
     * @see update_note()
     */
    public $note_private;

    /**
     * Active
     * @var int
     */
    public $active;

	/**
	 * Creation date
	 * @var int
	 */
	public $datec;

    /**
     * Modify user id
     * @var int
     */
    public $fk_user_modif = 0;

	/**
	 * Author id
	 * @var int
	 */
	public $user_author_id = 0;

	/**
	 * Timestamp
	 * @var int
	 */
	public $tms;

	/**
     * Entity
     * @var int
     */
	public $entity;

    /**
     * Notes/lines
     * @var BikeLine[]
     */
    public $lines = array();

    /**
     * Stand
     * @var Stand
     */
    public $stand = null;


    /**
     * Current user
     * @var User
     */
    public $user = null;

	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' =>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'entity' =>array('type'=>'integer', 'label'=>'Entity', 'default'=>1, 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>15, 'index'=>1),
        'ref' =>array('type'=>'varchar(30)', 'label'=>'Ref', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'showoncombobox'=>1, 'position'=>20),
		'name' =>array('type'=>'varchar(255)', 'label'=>'BikeName', 'enabled'=>1, 'visible'=>1, 'position'=>25),
		'code' =>array('type'=>'varchar(255)', 'label'=>'BikeCode', 'enabled'=>1, 'visible'=>1, 'position'=>30),
        'fk_user' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'BikeUser', 'enabled'=>1, 'visible'=>1, 'position'=>35),
		'fk_stand' =>array('type'=>'integer:Stand:stand/class/stand.class.php', 'label'=>'BikeStand', 'enabled'=>1, 'visible'=>1, 'position'=>40),
        'note_private' =>array('type'=>'text', 'label'=>'NotePublic', 'enabled'=>1, 'visible'=>0, 'position'=>45),
        'note_public' =>array('type'=>'text', 'label'=>'NotePrivate', 'enabled'=>1, 'visible'=>0, 'position'=>50),
        'active' =>array('type'=>'smallint(6)', 'label'=>'BikeActive', 'enabled'=>1, 'visible'=>1, 'position'=>65),
        'datec' =>array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-1, 'position'=>70),
        'fk_user_modif' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fk user modify', 'enabled'=>1, 'visible'=>-1, 'position'=>75),
		'user_author_id' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fk user author', 'enabled'=>1, 'visible'=>-1, 'position'=>80),
		'tms' =>array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>100)
		);
	// END MODULEBUILDER PROPERTIES

    /**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $langs;

		$this->db = $db;
	}

    /**
     *  Check existing ref
     *
     */
    function verify()
    {
        global $conf, $langs, $mysoc;

        $sql = "SELECT *";
        $sql .= " FROM ".MAIN_DB_PREFIX."bike";
        $sql .= " WHERE entity IN (".getEntity('bike').")";
        $sql .= " AND ref = '".$this->db->escape($this->ref)."'";

        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);

            if ($num == 0) {
                return 1;
            }
        }

        return -1;
    }

	/**
	 *	Insert bike into database
	 *
	 *	@param	User	$user     		User making insert
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 * 
	 *	@return int			     		Id of gestion if OK, < 0 if KO
	 */
	function create($user, $notrigger=0)
	{
		global $conf, $langs, $mysoc;

        $error=0;

		dol_syslog(get_class($this)."::create", LOG_DEBUG);

		$this->db->begin();

		$this->datec = dol_now();
		$this->entity = $conf->entity;
		$this->user_author_id = $user->id;
		$this->ref = empty($this->ref) ? $this->getNextNumRef($mysoc) : $this->ref;
        $this->active = 1;

        $result = $this->verify();

        if ($result > 0) {
            $now = dol_now();

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."bike (";
            $sql.= " ref";
            $sql.= " , name";
            $sql.= " , code";
            $sql.= " , fk_user";
            $sql.= " , fk_stand";
            $sql.= " , note_public";
            $sql.= " , note_private";
            $sql.= " , datec";
            $sql.= " , user_author_id";
            $sql.= " , fk_user_modif";
            $sql.= " , active";
            $sql.= " , entity";
            $sql.= " , tms";
            $sql.= ") VALUES (";
            $sql.= " ".(!empty($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null");
            $sql.= ", ".(!empty($this->name) ? "'".$this->db->escape($this->name)."'" : "null");
            $sql.= ", ".(!empty($this->code) ? "'".$this->db->escape($this->code)."'" : "null");
            $sql.= ", ".(!empty($this->fk_user) ? $this->fk_user : "0");
            $sql.= ", ".(!empty($this->fk_stand) ? $this->fk_stand : "0");
            $sql.= ", ".(!empty($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null");
            $sql.= ", ".(!empty($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null");
            $sql.= ", ".(!empty($this->datec) ? "'".$this->db->idate($this->datec)."'" : "null");
            $sql.= ", ".(!empty($this->user_author_id) ? $this->user_author_id : "0");
            $sql.= ", ".(!empty($this->fk_user_modif) ? $this->fk_user_modif : "0");
            $sql.= ", ".(!empty($this->active) ? $this->active : "0");
            $sql.= ", ".(!empty($this->entity) ? $this->entity : "0");
            $sql.= ", '".$this->db->idate($now)."'";
            $sql.= ")";

            dol_syslog(get_class($this)."::Create", LOG_DEBUG);
            $result = $this->db->query($sql);
            if ( $result )
            {
                $id = $this->db->last_insert_id(MAIN_DB_PREFIX."bike");

                if ($id > 0)
                {
                    $this->id				= $id;
                }
                else
                {
                    $error++;
                    $this->error='ErrorFailedToGetInsertedId';
                }
            }
            else
            {
                $error++;
                $this->error=$this->db->lasterror();
            }
        } else {
            // Product already exists with this ref
            $error++;
            $this->error = $langs->trans('BikeAlreadyExists');
        }

		if (! $error)
		{
			$result = $this->insertExtraFields();
			if ($result < 0) $error++;
		}
	

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Call trigger
	            $result = $this->call_trigger('BIKE_CREATE',$user);
	            if ($result < 0) $error++;
	            // End call triggers
			}
		}

		if (! $error)
		{
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->db->rollback();
			return -$error;
		}

	}

	/**
	 *	Update a record into database.
	 *
	 *	@param  User	$user       Object user making update
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return int         		1 if OK, -1 if ref already exists, -2 if other error
	 */
	function update($user, $notrigger=0)
	{
		global $langs, $conf, $hookmanager;

		$error=0;


		// Clean parameters
		$id = $this->id;

		// Check parameters
		if (empty($id))
		{
			$this->error = "Object must be fetched before calling update";
			return -1;
		}


		$this->db->begin();

        $this->fk_user_modif = $user->id;

		$sql = "UPDATE ".MAIN_DB_PREFIX."bike";
		$sql.= " SET ref = ".(!empty($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null");
		$sql.= ", name = ".(!empty($this->name) ? "'".$this->db->escape($this->name)."'" : "null");
		$sql.= ", code = ".(!empty($this->code) ? "'".$this->db->escape($this->code)."'" : "null");
        $sql.= ", note_public = ".(!empty($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null");
        $sql.= ", note_private = ".(!empty($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null");
        $sql.= ", fk_user = ".(!empty($this->fk_user) ? $this->fk_user : "0");
        $sql.= ", fk_stand = ".(!empty($this->fk_stand) ? $this->fk_stand : "0");
        $sql.= ", fk_user_modif = ".(!empty($this->fk_user_modif) ? $this->fk_user_modif : "0");
        $sql.= ", active = ".(!empty($this->active) ? $this->active : "0");
        $sql.= ", tms = '".$this->db->idate(dol_now())."'";
        $sql.= " WHERE rowid = " . $id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if (! $notrigger)
			{
				// Call trigger
				$result = $this->call_trigger('BIKE_MODIFY',$user);
				if ($result < 0) $error++;
				// End call triggers
			}

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$langs->trans("Error")." : ".$this->db->error()." - ".$sql;
			$this->errors[]=$this->error;
			$this->db->rollback();

			return -1;				
		}
	}

	/**
	 *  Load a slice in memory from database
	 *
	 *  @param	int		$id      			Id of slide
	 *  @return int     					<0 if KO, 0 if not found, >0 if OK
	 */
	function fetch($id, $ref='')
	{
		global $langs, $conf;

		dol_syslog(get_class($this)."::fetch id=".$id);


		// Check parameters
        if (empty($id) && empty($ref))
        {
            $this->error = 'ErrorWrongParameters';
            //dol_print_error(get_class($this)."::fetch ".$this->error);
            return -1;
        }

		$sql = "SELECT e.rowid, e.ref, e.datec, e.active, e.tms, e.name, e.code, e.note_public, e.note_private, e.fk_user, e.fk_stand, ";
		$sql.= " e.user_author_id, e.fk_user_modif, e.entity ";
		$sql.= " FROM ".MAIN_DB_PREFIX."bike e";
        if ($id > 0) {
            $sql.= " WHERE e.rowid=".$id;
        } else {
            $sql.= " WHERE e.entity IN (".getEntity('bike').") AND e.ref='".$this->db->escape($ref)."'";
        }

		$resql = $this->db->query($sql);
		if ( $resql )
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$obj = $this->db->fetch_object($resql);

				$this->id				= $obj->rowid;

				$this->user_author_id 	= $obj->user_author_id;
                $this->fk_user_modif 	= $obj->fk_user_modif;
				$this->ref 				= $obj->ref;
				$this->datec 			= $this->db->jdate($obj->datec);
				$this->tms 			    = $this->db->jdate($obj->tms);

                $this->name 		    = $obj->name;
				$this->code 	        = $obj->code;
                $this->note_public 	   = $obj->note_public;
                $this->note_private 	= $obj->note_private;
				$this->fk_user 	        = $obj->fk_user;
                $this->fk_stand 	     = $obj->fk_stand;

				$this->entity			= $obj->entity;
                $this->active			= $obj->active;

                if ($this->fk_stand > 0) {
                    $this->stand = new Stand($this->db);
                    $this->stand->fetch($this->fk_stand);
                }

                if ($this->fk_user > 0) {
                    $this->user = new User($this->db);
                    $this->user->fetch($this->fk_user);
                }

				$this->fetch_optionals();
                $this->fetch_lines();

				$this->db->free($resql);

				return 1;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Load array lines
	 *
	 *	@return		int						<0 if KO, >0 if OK
	 */
	public function fetch_lines()
	{
		global $langs, $conf;
		// phpcs:enable
        $this->lines = array();

        $sql = 'SELECT l.rowid, l.fk_bike, l.note, l.fk_user, l.user_author_id, l.datec, l.tms ';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'bikedet as l';
        $sql .= ' WHERE l.fk_bike = '.$this->id;
        $sql .= ' ORDER BY l.rowid';

        dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);

            $i = 0;
            while ($i < $num) {
                $objp = $this->db->fetch_object($result);

                $line = new BikeLine($this->db);

                $line->rowid            = $objp->rowid;
                $line->id               = $objp->rowid;
                $line->fk_bike          = $objp->fk_bike;
                $line->fk_user          = $objp->fk_user;
                $line->note            = $objp->note;

                $line->user_author_id 	= $objp->user_author_id;
                $line->datec 			= $this->db->jdate($objp->datec);
                $line->tms 			    = $this->db->jdate($objp->tms);

                $line->user = new User($this->db);
                $line->user->fetch($line->fk_user);

                $line->fetch_optionals();

                $this->lines[$i] = $line;
                $i++;
            }

            $this->db->free($result);

            return 1;
        } else {
            $this->error = $this->db->error();
            return -3;
        }
	}

    /**
     * 	Create an array of order lines
     *
     * 	@return int		>0 if OK, <0 if KO
     */
    function getLinesArray()
    {
        return $this->fetch_lines();
    }

	/**
	 *  Delete a gestion from database (if not used)
	 *
	 *	@param      User	$user       
	 *  @param  	int		$notrigger	    0=launch triggers after, 1=disable triggers
	 * 	@return		int					< 0 if KO, 0 = Not possible, > 0 if OK
	 */
	function delete(User $user, $notrigger=0)
	{
		global $conf, $langs;

		$error=0;

		// Clean parameters
		$id = $this->id;

		// Check parameters
		if (empty($id))
		{
			$this->error = "Object must be fetched before calling delete";
			return -1;
		}
		
		$this->db->begin();

        $sqlz = "DELETE FROM ".MAIN_DB_PREFIX."bikedet";
        $sqlz.= " WHERE fk_bike = ".$id;
        dol_syslog(get_class($this).'::delete', LOG_DEBUG);
        $resultz = $this->db->query($sqlz);

        if ( ! $resultz )
        {
            $error++;
            $this->errors[] = $this->db->lasterror();
        }


        $sqlz = "DELETE FROM ".MAIN_DB_PREFIX."bike";
		$sqlz.= " WHERE rowid = ".$id;
		dol_syslog(get_class($this).'::delete', LOG_DEBUG);
		$resultz = $this->db->query($sqlz);

		if ( ! $resultz )
		{
			$error++;
			$this->errors[] = $this->db->lasterror();
		}		

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Call trigger
	            $result = $this->call_trigger('BIKE_DELETE',$user);
	            if ($result < 0) $error++;
	            // End call triggers
			}
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -$error;
		}

	}

    /**
     *	Show add free and predefined products/services form
     *
     *  @param	int		        $dateSelector       1=Show also date range input fields
     *  @param	Societe			$seller				Object thirdparty who sell
     *  @param	Societe			$buyer				Object thirdparty who buy
     *  @param	string			$defaulttpldir		Directory where to find the template
     *	@return	void
     */
    public function formAddObjectLine($dateSelector, $seller, $buyer, $defaulttpldir = '/core/tpl')
    {
        global $conf, $user, $langs, $object, $hookmanager, $extrafields;
        global $form;

        // Line extrafield
        if (!is_object($extrafields)) {
            require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
            $extrafields = new ExtraFields($this->db);
        }
        $extrafields->fetch_name_optionals_label($this->table_element_line);

        // Output template part (modules that overwrite templates must declare this into descriptor)
        // Use global variables + $dateSelector + $seller and $buyer
        // Note: This is deprecated. If you need to overwrite the tpl file, use instead the hook 'formAddObjectLine'.
        $tpl = dol_buildpath('bike/tpl/objectline_create.tpl.php');

        if (empty($conf->file->strict_mode)) {
            $res = @include $tpl;
        } else {
            $res = include $tpl; // for debug
        }
    }

    /**
     *	Return HTML table for object lines
     *	TODO Move this into an output class file (htmlline.class.php)
     *	If lines are into a template, title must also be into a template
     *	But for the moment we don't know if it's possible as we keep a method available on overloaded objects.
     *
     *	@param	string		$action				Action code
     *	@param  string		$seller            	Object of seller third party
     *	@param  string  	$buyer             	Object of buyer third party
     *	@param	int			$selected		   	Object line selected
     *	@param  int	    	$dateSelector      	1=Show also date range input fields
     *  @param	string		$defaulttpldir		Directory where to find the template
     *	@return	void
     */
    public function printObjectLines($action, $seller, $buyer, $selected = 0, $dateSelector = 0, $defaulttpldir = '/core/tpl')
    {
        global $conf, $hookmanager, $langs, $user, $form, $extrafields, $object;
        // TODO We should not use global var for this
        global $inputalsopricewithtax, $usemargins, $disableedit, $disablemove, $disableremove, $outputalsopricetotalwithtax;

        $num = count($this->lines);

        // Line extrafield
        if (!is_object($extrafields)) {
            require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
            $extrafields = new ExtraFields($this->db);
        }
        $extrafields->fetch_name_optionals_label($this->table_element_line);

        $parameters = array('num'=>$num, 'dateSelector'=>$dateSelector, 'seller'=>$seller, 'buyer'=>$buyer, 'selected'=>$selected, 'table_element_line'=>$this->table_element_line);
        $reshook = $hookmanager->executeHooks('printObjectLineTitle', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
        if (empty($reshook)) {

            $tpl = dol_buildpath('bike/tpl/objectline_title.tpl.php');

            if (empty($conf->file->strict_mode)) {
                $res = @include $tpl;
            } else {
                $res = include $tpl; // for debug
            }
        }

        $i = 0;

        print "<!-- begin printObjectLines() --><tbody>\n";
        foreach ($this->lines as $line) {
            //Line extrafield
            $line->fetch_optionals();

            //if (is_object($hookmanager) && (($line->product_type == 9 && ! empty($line->special_code)) || ! empty($line->fk_parent_line)))
            if (is_object($hookmanager)) {   // Old code is commented on preceding line.
                $parameters = array('line'=>$line, 'num'=>$num, 'i'=>$i, 'dateSelector'=>$dateSelector, 'seller'=>$seller, 'buyer'=>$buyer, 'selected'=>$selected, 'table_element_line'=>$line->table_element);
                $reshook = $hookmanager->executeHooks('printObjectLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
            }
            if (empty($reshook)) {
                $this->printObjectLine($action, $line, '', $num, $i, $dateSelector, $seller, $buyer, $selected, $extrafields, $defaulttpldir);
            }

            $i++;
        }
        print "</tbody><!-- end printObjectLines() -->\n";
    }

    /**
     *	Return HTML content of a detail line
     *	TODO Move this into an output class file (htmlline.class.php)
     *
     *	@param	string      		$action				GET/POST action
     *	@param  CommonObjectLine 	$line			    Selected object line to output
     *	@param  string	    		$var               	Is it a an odd line (true)
     *	@param  int		    		$num               	Number of line (0)
     *	@param  int		    		$i					I
     *	@param  int		    		$dateSelector      	1=Show also date range input fields
     *	@param  string	    		$seller            	Object of seller third party
     *	@param  string	    		$buyer             	Object of buyer third party
     *	@param	int					$selected		   	Object line selected
     *  @param  Extrafields			$extrafields		Object of extrafields
     *  @param	string				$defaulttpldir		Directory where to find the template (deprecated)
     *	@return	void
     */
    public function printObjectLine($action, $line, $var, $num, $i, $dateSelector, $seller, $buyer, $selected = 0, $extrafields = null, $defaulttpldir = '/core/tpl')
    {
        global $conf, $langs, $user, $object, $hookmanager;
        global $form;
        global $object_rights, $disableedit, $disablemove, $disableremove; // TODO We should not use global var for this !

        $object_rights = $this->getRights();

        $element = $this->element;

        $text = '';
        $description = '';

        // Line in view mode
        if ($action != 'editline' || $selected != $line->id) {
            $label = $line->note;

            $tpl = dol_buildpath('bike/tpl/objectline_view.tpl.php');

            if (empty($conf->file->strict_mode)) {
                $res = @include $tpl;
            } else {
                $res = include $tpl; // for debug
            }
        }

        // Line in update mode
        if ($action == 'editline' && $selected == $line->id) {
            $label = $line->note;

            $tpl = dol_buildpath('bike/tpl/objectline_edit.tpl.php');

            if (empty($conf->file->strict_mode)) {
                $res = @include $tpl;
            } else {
                $res = include $tpl; // for debug
            }
        }
    }

    /**
     *    Add a bike line into database (linked to product/service or not)
     *
     * @param $note
     * @return     int                                >0 if OK, <0 if KO
     */
    public function addline($note, $fk_user = 0)
    {
        global $mysoc, $conf, $langs, $user;

        $logtext = "::addline bikeid=$this->id, note=$note";
        dol_syslog(get_class($this).$logtext, LOG_DEBUG);


        $note = trim($note);

        $this->db->begin();

        // Insert line
        $this->line = new BikeLine($this->db);

        $this->line->context = $this->context;

        $this->line->fk_bike = $this->id;
        $this->line->note = $note;
        $this->line->fk_user = $fk_user;

        $result = $this->line->insert($user);
        if ($result > 0) {
            $this->db->commit();
            return $this->line->id;
        } else {
            $this->error = $this->line->error;
            dol_syslog(get_class($this)."::addline error=".$this->error, LOG_ERR);
            $this->db->rollback();
            return -2;
        }
    }

    /**
     *  Update a line in database
     *
     *  @param    	int				$rowid            	Id of line to update
     *  @param    	string			$note             	Note of line
     * 	@param		int				$notrigger			disable line update trigger
     *  @return   	int              					< 0 if KO, > 0 if OK
     */
    public function updateline($rowid, $note, $fk_user, $notrigger = 0)
    {
        global $conf, $mysoc, $langs, $user;

        dol_syslog(get_class($this)."::updateline id=$rowid, note=$note");

        $this->db->begin();

        //Fetch current line from the database and then clone the object and set it in $oldline property
        $line = new BikeLine($this->db);
        $line->fetch($rowid);

        $staticline = clone $line;

        $line->oldline = $staticline;
        $this->line = $line;
        $this->line->context = $this->context;

        $this->line->id = $rowid;
        $this->line->note = $note;
        $this->line->fk_user = $fk_user;

        $result = $this->line->update($user, $notrigger);
        if ($result > 0) {
            $this->db->commit();
            return $result;
        } else {
            $this->error = $this->line->error;

            $this->db->rollback();
            return -1;
        }
    }

    /**
     *  Delete a bike line
     *
     *	@param      User	$user		User object
     *  @param      int		$lineid		Id of line to delete
     *  @return     int        		 	>0 if OK, 0 if nothing to do, <0 if KO
     */
    public function deleteline($user = null, $lineid = 0)
    {
        $this->db->begin();

        $line = new BikeLine($this->db);

            // For triggers
        if ($line->fetch($lineid) > 0) {

            // Memorize previous line for triggers
            $staticline = clone $line;
            $line->oldline = $staticline;

            if ($line->delete($user) > 0) {
                $this->db->commit();
                return 1;
            } else {
                $this->db->rollback();
                $this->error = $line->error;
                return -1;
            }
        } else {
            $this->db->rollback();
            return 0;
        }
    }
    
     /**
     *      \brief Return next reference of confirmation not already used (or last reference)
     *      @param	   soc  		           objet company
     *      @param     mode                    'next' for next value or 'last' for last value
     *      @return    string                  free ref or last ref
     */
    function getNextNumRef($soc, $mode = 'next')
    {
        global $conf, $langs;

        $langs->load("bike@bike");

        // Clean parameters (if not defined or using deprecated value)
        if (empty($conf->global->BIKE_ADDON)){
            $conf->global->BIKE_ADDON = 'mod_bike_cargo';
        }else if ($conf->global->BIKE_ADDON == 'gravel'){
            $conf->global->BIKE_ADDON = 'mod_bike_gravel';
        }else if ($conf->global->BIKE_ADDON == 'cargo'){
            $conf->global->BIKE_ADDON = 'mod_bike_cargo';
        }

        $included = false;

        $classname = $conf->global->BIKE_ADDON;
        $file = $classname.'.php';

        // Include file with class
        $dir = '/bike/core/modules/bike/';
        $included = dol_include_once($dir.$file);

        if (! $included)
        {
            $this->error = $langs->trans('FailedToIncludeNumberingFile');
            return -1;
        }

        $obj = new $classname();

        $numref = "";
        $numref = $obj->getNumRef($soc, $this, $mode);

        if ($numref != "")
        {
            return $numref;
        }
        else
        {
            return -1;
        }
	}

	
	/**
	 *	Charge les informations d'ordre info dans l'objet commande
	 *
	 *	@param  int		$id       Id of order
	 *	@return	void
	 */
	function info($id)
	{
		$sql = 'SELECT e.rowid, e.datec as datec, e.tms as datem,';
		$sql.= ' e.user_author_id as fk_user_author';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'bike as e';
		$sql.= ' WHERE e.rowid = '.$id;
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation   = $cuser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
			}

			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

    /**
     *	Return clicable link of object (with eventually picto)
     *
     *	@param      int			$withpicto                Add picto into link
     *	@param      int			$max          	          Max length to show
     *	@param      int			$short			          ???
     *  @param	    int   	    $notooltip		          1=Disable tooltip
     *	@return     string          			          String with URL
     */
    function getNomUrl($withpicto=0, $option='', $max=0, $short=0, $notooltip=0)
    {
        global $conf, $langs, $user;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result='';

        $url = dol_buildpath('/bike/card.php', 1).'?id='.$this->id;

        if ($short) return $url;

        $picto = 'bike@bike';
        $label = '';

		if ($user->rights->bike->lire) {
			$label = '<u>'.$langs->trans("ShowBike").'</u>';
			$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		}

		$linkclose='';
		if (empty($notooltip) && $user->rights->bike->lire)
		{
		    if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
		    {
		        $label=$langs->trans("ShowBike");
		        $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
		    }
		    $linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
		    $linkclose.=' class="classfortooltip"';
		}

        $linkstart = '<a href="'.$url.'"';
        $linkstart.= $linkclose.'>';
        $linkend = '</a>';

        if ($withpicto) $result .= ($linkstart.img_object(($notooltip?'':$label), $picto, ($notooltip?'':'class="classfortooltip"'), 0, 0, $notooltip?0:1).$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
		$result .= $linkstart .$this->ref. $linkend;
		
        return $result;
	}
	
    /**
     *	Return status label of Bike
     *
     *	@param      int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *	@return     string      		Libelle
     */
    function getLibStatut($mode)
    {
		return $this->LibStatut(0, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return label of status
	 *
	 *	@param		int		$status      	  Id status
	 *	@param      int		$mode        	  0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return     string					  Label of status
	 */
	public function LibStatut($status, $mode)
	{
		// phpcs:enable
		global $langs, $conf;
		return '';
	}

	/**
	 *  Return list of bikes
	 *
	 *  @return     int             		-1 if KO, array with result if OK
	 */
	function liste_array()
	{
		global $user;

		$bikes = array();

		$sql = "SELECT e.rowid as id, e.ref, e.datec";
		$sql.= " FROM ".MAIN_DB_PREFIX."bike as e";
		$sql.= " WHERE e.entity IN (".getEntity('bike').")";

		$result=$this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			if ($num)
			{
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);

					$datec = $this->db->jdate($obj->datec);
					$bike = new Bike($this->db);
					$bike->fetch($obj->id);

					$bikes[$obj->id] = $bike;

					$i++;
				}
			}
			return $bikes;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}
}

/**
 *  Class to manage bike lines
 */
class BikeLine extends CommonObjectLine
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'bikedet';

    public $table_element = 'bikedet';

    public $oldline;

    /**
     * Id of parent bike
     * @var int
     */
    public $fk_bike;

    /**
     * @var string Note
     */
    public $note;

    /**
     * User
     * @var int
     */
    public $fk_user = 0;

    /**
     * Creation date
     * @var int
     */
    public $datec;

    /**
     * Author id
     * @var int
     */
    public $user_author_id = 0;

    /**
     * Timestamp
     * @var int
     */
    public $tms;

    /**
     * Current user
     * @var User
     */
    public $user = null;

    /**
     *      Constructor
     *
     *      @param     DoliDB	$db      handler d'acces base de donnee
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Load line order
     *
     *  @param  int		$rowid          Id line order
     *  @return	int						<0 if KO, >0 if OK
     */
    public function fetch($rowid)
    {
        $sql = 'SELECT cd.rowid, cd.fk_bike, cd.fk_user, cd.user_author_id, cd.note, cd.datec, cd.tms';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'bikedet as cd';
        $sql .= ' WHERE cd.rowid = '.((int) $rowid);
        $result = $this->db->query($sql);
        if ($result) {
            $objp = $this->db->fetch_object($result);
            $this->rowid            = $objp->rowid;
            $this->id               = $objp->rowid;
            $this->fk_bike          = $objp->fk_bike;
            $this->note            = $objp->note;
            $this->fk_user          = $objp->fk_user;

            $this->user = new User($this->db);
            $this->user->fetch($this->fk_user);

            $this->datec 			= $this->db->jdate($objp->datec);
            $this->tms 			    = $this->db->jdate($objp->tms);

            $this->db->free($result);

            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * 	Delete line in database
     *
     *	@param      User	$user        	User that modify
     *  @param      int		$notrigger	    0=launch triggers after, 1=disable triggers
     *	@return	 int  <0 si ko, >0 si ok
     */
    public function delete(User $user, $notrigger = 0)
    {
        global $conf, $langs;

        $error = 0;

        if (empty($this->id) && !empty($this->rowid)) {		// For backward compatibility
            $this->id = $this->rowid;
        }


        $this->db->begin();

        $sql = 'DELETE FROM '.MAIN_DB_PREFIX."bikedet WHERE rowid = ".((int) $this->id);

        dol_syslog("BikeLine::delete", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {

            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('LINEBIKE_DELETE', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return 1;
            }

            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', '.$errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     *	Insert line into database
     *
     *	@param      User	$user        	User that modify
     *	@param      int		$notrigger		1 = disable triggers
     *	@return		int						<0 if KO, >0 if OK
     */
    public function insert($user = null, $notrigger = 0)
    {
        global $langs, $conf;

        $error = 0;

        dol_syslog(get_class($this)."::insert rang=".$this->rang);

        $this->db->begin();

        $this->datec = dol_now();
        $this->user_author_id = $user ? $user->id : 0;

        // Insertion dans base de la ligne
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'bikedet (fk_bike, note, fk_user, user_author_id, datec, tms)';
        $sql .= " VALUES (".$this->fk_bike.",";
        $sql .= " ".(!empty($this->note) ? "'".$this->db->escape($this->note)."'" : "null").",";
        $sql .= " ".(!empty($this->fk_user) ? $this->fk_user : "0").",";
        $sql .= " ".(!empty($this->user_author_id) ? $this->user_author_id : "0").",";
        $sql .= " '".$this->db->idate(dol_now())."',";
        $sql .= " '".$this->db->idate(dol_now())."'";
        $sql .= ')';

        dol_syslog(get_class($this)."::insert", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'bikedet');
            $this->rowid = $this->id;

            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('LINEBIKE_INSERT', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return 1;
            }

            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this)."::insert ".$errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', '.$errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->error = $this->db->error();
            $this->db->rollback();
            return -2;
        }
    }

    /**
     *	Update the line object into db
     *
     *	@param      User	$user        	User that modify
     *	@param      int		$notrigger		1 = disable triggers
     *	@return		int		<0 si ko, >0 si ok
     */
    public function update(User $user, $notrigger = 0)
    {
        global $conf, $langs;

        $error = 0;

        $this->db->begin();

        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."bikedet SET";
        $sql .= " note=".(!empty($this->note) ? "'".$this->db->escape($this->note)."'" : "null");
        $sql .= " , fk_user=".(!empty($this->fk_user) ? $this->fk_user : "0");
        $sql .= " , tms='".$this->db->idate(dol_now())."'";
        $sql .= " WHERE rowid = ".((int) $this->rowid);

        dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {


            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('LINEBIKE_UPDATE', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return 1;
            }

            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', '.$errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->error = $this->db->error();
            $this->db->rollback();
            return -2;
        }
    }
}