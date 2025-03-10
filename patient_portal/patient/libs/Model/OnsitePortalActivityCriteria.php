<?php
/** @package    LibreHealth EHR::Model */

/**
 *
 * Copyright (C) 2016-2017 Jerry Padgett <sjpadgett@gmail.com>
 *
 * LICENSE: This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0
 * See the Mozilla Public License for more details.
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package LibreHealth EHR
 * @author Jerry Padgett <sjpadgett@gmail.com>
 * @link http://librehealth.io
 */

/** import supporting libraries */
require_once("DAO/OnsitePortalActivityCriteriaDAO.php");

/**
 * The OnsitePortalActivityCriteria class extends OnsitePortalActivityDAOCriteria and is used
 * to query the database for objects and collections
 * 
 * @inheritdocs
 * @package LibreHealth EHR::Model
 * @author ClassBuilder
 * @version 1.0
 */
class OnsitePortalActivityCriteria extends OnsitePortalActivityCriteriaDAO
{
    
    /**
     * GetFieldFromProp returns the DB column for a given class property
     * 
     * If any fields that are not part of the table need to be supported
     * by this Criteria class, they can be added inside the switch statement
     * in this method
     * 
     * @see Criteria::GetFieldFromProp()
     */
    /*
    public function GetFieldFromProp($propname)
    {
        switch($propname)
        {
             case 'CustomProp1':
                return 'my_db_column_1';
             case 'CustomProp2':
                return 'my_db_column_2';
            default:
                return parent::GetFieldFromProp($propname);
        }
    }
    */
    
    /**
     * For custom query logic, you may override OnPrepare and set the $this->_where to whatever
     * sql code is necessary.  If you choose to manually set _where then Phreeze will not touch
     * your where clause at all and so any of the standard property names will be ignored
     *
     * @see Criteria::OnPrepare()
     */
    /*
    function OnPrepare()
    {
        if ($this->MyCustomField == "special value")
        {
            // _where must begin with "where"
            $this->_where = "where db_field ....";
        }
    }
    */

}
?>