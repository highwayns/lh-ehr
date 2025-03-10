<?php
/**
* Sql functions/classes for LibreHealth EHR.
*
* Includes classes and functions that LibreHealth EHR uses
* to interact with SQL.
*
* LICENSE: This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://opensource.org/licenses/gpl-license.php>.
* 
* @package   LibreHealth EHR
* @link      http://librehealth.io
*/

require_once(dirname(__FILE__) . "/sqlconf.php");
require_once(dirname(__FILE__) . "/../assets/adodb/adodb.inc.php");
require_once(dirname(__FILE__) . "/../assets/adodb/drivers/adodb-mysqli.inc.php");
require_once(dirname(__FILE__) . "/log.inc");

/**
* ADODB_mysql class wrapper to ensure proper auditing in LibreEHR.
*
* @author  Kevin Yeh <kevin.y@integralemr.com>
*/
class ADODB_mysqli_log extends ADODB_mysqli
{
        /**
        * ADODB Execute function wrapper to ensure proper auditing in LibreEHR.
        *
        * @param  string  $sql         query
        * @param  array   $inputarr    binded variables array (optional)
        * @return boolean              returns false if error
        */
        function Execute($sql,$inputarr=false)
        {
            $retval= parent::Execute($sql,$inputarr);
            if ($retval === false) {
              $outcome = false;
              // Stash the error into last_mysql_error so it doesn't get clobbered when
              // we insert into the audit log.
              $GLOBALS['last_mysql_error']=$this->ErrorMsg();

              // Last error no
              $GLOBALS['last_mysql_error_no']=$this->ErrorNo();
            }
            else {
              $outcome = true;
            }
            // Stash the insert ID into lastidado so it doesn't get clobbered when
            // we insert into the audit log.
            $GLOBALS['lastidado']=$this->Insert_ID();
            auditSQLEvent($sql,$outcome,$inputarr);
            return $retval;
        }

        /**
        * ADODB Execute function wrapper to skip auditing in LibreEHR.
        *
        * Bypasses the LibreEHR auditing engine.
        *
        * @param  string  $sql         query
        * @param  array   $inputarr    binded variables array (optional)
        * @return boolean              returns false if error
        */
        function ExecuteNoLog($sql,$inputarr=false)
        {
            return parent::Execute($sql,$inputarr);
        }

        /*
        * ADODB GenID function wrapper to work with LibreEHR.
        *
        * Need to override to fix a bug where call to GenID was updating
        * sequences table but always returning a zero with the LibreEHR audit
        * engine both on and off. Note this bug only appears to occur in recent
        * php versions on windows. The fix is to use the ExecuteNoLog() function
        * rather than the Execute() functions within this function (otherwise,
        * there are no other changes from the original ADODB GenID function).
        *
        * @param  string  $seqname     table name containing sequence (default is adodbseq)
        * @param  integer $startID     id to start with for a new sequence (default is 1)
        * @return integer              returns the sequence integer
        */
        function GenID($seqname='adodbseq',$startID=1)
        {
                // post-nuke sets hasGenID to false
                if (!$this->hasGenID) return false;

                $getnext = sprintf($this->_genIDSQL,$seqname);
                $holdtransOK = $this->_transOK; // save the current status
                $rs = @$this->ExecuteNoLog($getnext);
                if (!$rs) {
                        if ($holdtransOK) $this->_transOK = true; //if the status was ok before reset
                        $u = strtoupper($seqname);
                        $this->ExecuteNoLog(sprintf($this->_genSeqSQL,$seqname));
                        $cnt = $this->GetOne(sprintf($this->_genSeqCountSQL,$seqname));
                        if (!$cnt) $this->ExecuteNoLog(sprintf($this->_genSeq2SQL,$seqname,$startID-1));
                        $rs = $this->ExecuteNoLog($getnext);
                }

                if ($rs) {
                        $this->genID = mysqli_insert_id($this->_connectionID);
                        $rs->Close();
                } else
                        $this->genID = 0;

                return $this->genID;
        }
}
if (!defined('ADODB_FETCH_ASSOC')) define('ADODB_FETCH_ASSOC', 2);
$database = NewADOConnection("mysqli_log"); // Use the subclassed driver which logs execute events
// Below clientFlags flag is telling the mysql connection to allow local_infile setting,
// which is needed to import data in the Administration->Other->External Data Loads feature.
// Note this is a specific bug to work in Ubuntu 12.04, of which the Data Load feature does not
// work and is suspicious for a bug in PHP of that OS; Setting this clientFlags fixes this bug
// and appears to not cause problems in other operating systems.
$database->clientFlags = 128;
$database->port = $port;
$database->PConnect($host, $login, $pass, $dbase);
$GLOBALS['adodb']['db'] = $database;
$GLOBALS['dbh'] = $database->_connectionID;

// Modified 5/2009 by BM for UTF-8 project ---------
if (!$disable_utf8_flag) {
 $success_flag = $database->Execute("SET NAMES 'utf8'");
  if (!$success_flag) {
   error_log("PHP custom error: from librehealth ehr library/sql.inc  - Unable to set up UTF8 encoding with mysql database: ".getSqlLastError(), 0);
  }
}

// Turn off STRICT SQL
$sql_strict_set_success = $database->Execute("SET sql_mode = ''");
if (!$sql_strict_set_success) {
 error_log("Unable to set strict sql setting: ".getSqlLastError(), 0);
}

// set up associations in adodb calls (not sure why above define
//  command does not work)
$GLOBALS['adodb']['db']->SetFetchMode(ADODB_FETCH_ASSOC);

//fmg: This makes the login screen informative when no connection can be made
if (!$GLOBALS['dbh']) {
  //try to be more helpful
  if ($host == "localhost") {
    echo "Check that mysqld is running.<p>";
  } else {
    echo "Check that you can ping the server '".text($host)."'.<p>";
  }//if local
  HelpfulDie("Could not connect to server!", getSqlLastError());
  exit;
}//if no connection

/**
* Standard sql query in LibreEHR.
*
* Function that will allow use of the adodb binding
* feature to prevent sql-injection. Will continue to
* be compatible with previous function calls that do
* not use binding.
* It will return a recordset object.
* The sqlFetchArray() function should be used to
* utilize the return object.
*
* @param  string  $statement  query
* @param  array   $binds      binded variables array (optional)
* @return recordset
*/
function sqlStatement($statement, $binds=false )
{
  // Below line is to avoid a nasty bug in windows.
  if (empty($binds)) $binds = false;

  // Use adodb Execute with binding and return a recordset.
  //   Note that the auditSQLEvent function is embedded
  //    in the Execute command.
  $recordset = $GLOBALS['adodb']['db']->Execute( $statement, $binds );
  if ($recordset === FALSE) {
    HelpfulDie("query failed: $statement", getSqlLastError());
  }
  return $recordset;
}

/**
* Specialized sql query in LibreEHR that skips auditing.
*
* Function that will allow use of the adodb binding
* feature to prevent sql-injection. Will continue to
* be compatible with previous function calls that do
* not use binding. It is equivalent to the 
* sqlStatement() function, EXCEPT it skips the
* audit engine. This function should only be used
* in very special situations.
* It will return a recordset object.
* The sqlFetchArray() function should be used to
* utilize the return object.
*
* @param  string  $statement  query
* @param  array   $binds      binded variables array (optional)
* @return recordset
*/
function sqlStatementNoLog($statement, $binds=false )
{
  // Below line is to avoid a nasty bug in windows.
  if (empty($binds)) $binds = false;

  // Use adodb ExecuteNoLog with binding and return a recordset.
  $recordset = $GLOBALS['adodb']['db']->ExecuteNoLog( $statement, $binds );
  if ($recordset === FALSE) {
    HelpfulDie("query failed: $statement", getSqlLastError());
  }
  return $recordset;
}

/**
* sqlStatement() function wrapper for CDR engine in LibreEHR.
* Allows option to turn on/off auditing specifically for the
* CDR engine.
* 
* @param  string  $statement  query
* @param  array   $binds      binded variables array (optional)
* @return recordset/resource
*/
function sqlStatementCdrEngine($statement, $binds=false )
{
  // Below line is to avoid a nasty bug in windows.
  if (empty($binds)) $binds = false;

  if ($GLOBALS['audit_events_cdr']) {
    return sqlStatement($statement,$binds);
  }
  else {
    return sqlStatementNoLog($statement,$binds);
  }
}

/**
* Returns a row (as an array) from a sql recordset.
*
* Function that will allow use of the adodb binding
* feature to prevent sql-injection.
* It will act upon the object returned from the
* sqlStatement() function (and sqlQ() function).
*
* @param recordset $r
* @return array
*/
function sqlFetchArray($r)
{
  //treat as an adodb recordset
  if ($r === FALSE)
    return false;
  if ($r->EOF)
    return false;
  //ensure it's an object (ie. is set)
  if (!is_object($r))
    return false;

    return $r->FetchRow();
}

/**
 * Wrapper for ADODB getAssoc
 * 
 * @see http://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:getassoc
 * 
 * @param string $sql
 * @param string[] $bindvars
 * @param boolean $forceArray
 * @param boolean $first2Cols
 * @return array
 */
function sqlGetAssoc( $sql, $bindvars=false, $forceArray=false, $first2Cols=false ) {
  
  return $GLOBALS['adodb']['db']->getAssoc( $sql, $bindvars, $forceArray, $first2Cols  );
  
}

/**
* Standard sql insert query in OpenEMR.
*
* Function that will allow use of the adodb binding
* feature to prevent sql-injection. This function
* is specialized for insert function and will return
* the last id generated from the insert.
*
* @param  string   $statement  query
* @param  array    $binds      binded variables array (optional)
* @return integer  Last id generated from the sql insert command
*/
function sqlInsert($statement, $binds=false)
{
  // Below line is to avoid a nasty bug in windows.
  if (empty($binds)) $binds = false;

  //Run a adodb execute
  // Note the auditSQLEvent function is embedded in the
  //   Execute function.
  $recordset = $GLOBALS['adodb']['db']->Execute($statement, $binds);
  if ($recordset === FALSE) {
    HelpfulDie("insert failed: $statement", getSqlLastError());
  }
  // Return the correct last id generated using function
  //   that is safe with the audit engine.
  return getSqlLastID();
}

/**
* Specialized sql query in LibreEHR that only returns
* the first row of query results as an associative array.
*
* Function that will allow use of the adodb binding
* feature to prevent sql-injection.
*
* @param  string  $statement  query
* @param  array   $binds      binded variables array (optional)
* @return array
*/
function sqlQuery($statement, $binds=false)
{
  // Below line is to avoid a nasty bug in windows.
  if (empty($binds)) $binds = false;

  $recordset = $GLOBALS['adodb']['db']->Execute( $statement, $binds );

  if ($recordset === FALSE) {
    HelpfulDie("query failed: $statement", getSqlLastError());
  }
  if ($recordset->EOF)
   return FALSE;
  $rez = $recordset->FetchRow();
  if ($rez == FALSE)
    return FALSE;
  return $rez;
}

/**
* Specialized sql query in LibreEHR that bypasses the auditing engine
* and only returns the first row of query results as an associative array.
*
* Function that will allow use of the adodb binding
* feature to prevent sql-injection. It is equivalent to the
* sqlQuery() function, EXCEPT it skips the
* audit engine. This function should only be used
* in very special situations.
*
* @param  string  $statement  query
* @param  array   $binds      binded variables array (optional)
* @return array
*/
function sqlQueryNoLog($statement, $binds=false)
{
  // Below line is to avoid a nasty bug in windows.
  if (empty($binds)) $binds = false;

  $recordset = $GLOBALS['adodb']['db']->ExecuteNoLog( $statement, $binds );

  if ($recordset === FALSE) {
    HelpfulDie("query failed: $statement", getSqlLastError());
  }
  if ($recordset->EOF)
   return FALSE;
  $rez = $recordset->FetchRow();
  if ($rez == FALSE)
    return FALSE;
  return $rez;
}

/**
* Specialized sql query in LibreEHR that ignores sql errors, bypasses the
* auditing engine and only returns the first row of query results as an
* associative array.
*
* Function that will allow use of the adodb binding
* feature to prevent sql-injection. It is equivalent to the
* sqlQuery() function, EXCEPT it skips the
* audit engine and ignores erros. This function should only be used
* in very special situations.
*
* @param  string  $statement  query
* @param  array   $binds      binded variables array (optional)
* @return array
*/
function sqlQueryNoLogIgnoreError($statement, $binds=false)
{
  // Below line is to avoid a nasty bug in windows.
  if (empty($binds)) $binds = false;

  $recordset = $GLOBALS['adodb']['db']->ExecuteNoLog( $statement, $binds );

  if ($recordset === FALSE) {
    // ignore the error and return FALSE
    return FALSE;
  }
  if ($recordset->EOF)
   return FALSE;
  $rez = $recordset->FetchRow();
  if ($rez == FALSE)
    return FALSE;
  return $rez;
}

/**
* sqlQuery() function wrapper for CDR engine in LibreEHR.
* Allows option to turn on/off auditing specifically for the
* CDR engine.
*
* @param  string  $statement  query
* @param  array   $binds      binded variables array (optional)
* @return array
*/
function sqlQueryCdrEngine($statement, $binds=false )
{
  // Below line is to avoid a nasty bug in windows.
  if (empty($binds)) $binds = false;

  if ($GLOBALS['audit_events_cdr']) {
    return sqlQuery($statement,$binds);
  }
  else {
    return sqlQueryNoLog($statement,$binds);
  }
}

/**
* Specialized sql query in LibreEHR that skips auditing.
*
* This function should only be used in very special situations.
*
* @param  string  $statement  query
*/
function sqlInsertClean_audit($statement)
{

  $ret = $GLOBALS['adodb']['db']->ExecuteNoLog($statement);
  if ($ret === FALSE) {
    HelpfulDie("insert failed: $statement", getSqlLastError());
  }
}

/**
* Function that will safely return the last ID inserted,
* and accounts for the audit engine.
*
* @return  integer Last ID that was inserted into sql
*/
function getSqlLastID() {
    return $GLOBALS['lastidado'] > 0 ? $GLOBALS['lastidado'] : $GLOBALS['adodb']['db']->Insert_ID();
}

/**
* Function that will safely return the last error,
* and accounts for the audit engine.
*
* @param   string  $mode either adodb(default) or native_mysql
* @return  string        last mysql error
*/
function getSqlLastError() {
    return !empty($GLOBALS['last_mysql_error']) ? $GLOBALS['last_mysql_error'] : $GLOBALS['adodb']['db']->ErrorMsg();
}

/**
 * Function that will safely return the last error no,
 * and accounts for the audit engine.
 *
 * @param   string  $mode either adodb(default) or native_mysql
 * @return  string        last mysql error no
 */
function getSqlLastErrorNo() {
    return !empty($GLOBALS['last_mysql_error_no']) ? $GLOBALS['last_mysql_error_no'] : $GLOBALS['adodb']['db']->ErrorNo();
}

/**
* Function that will return an array listing
* of columns that exist in a table.
*
* @param   string  $table sql table
* @return  array
*/
function sqlListFields($table) {
  $sql = "SHOW COLUMNS FROM ". add_escape_custom($table);
  $resource = sqlQ($sql);
  $field_list = array();
  while($row = sqlFetchArray($resource)) {
    $field_list[] = $row['Field'];
  }
  return $field_list;
}

/**
* Returns the number of sql rows
*
* @param recordset $r
* @return integer Number of rows
*/
function sqlNumRows($r)
{
  return $r->RecordCount();
}

/**
* Error function for LibreEHR sql functions
*
* @param string $statement
* @param string $sqlerr
*/
function HelpfulDie ($statement, $sqlerr='')
{
  echo "<p><p><font color='red'>ERROR:</font> ".text($statement)."<p>";
  $logMsg="SQL Error with statement:".$statement;
  if ($sqlerr) {
    echo "Error: <font color='red'>".text($sqlerr)."</font><p>";
    $logMsg.="--".$sqlerr;
  }//if error
  $backtrace=debug_backtrace();
  for($level=1;$level<count($backtrace);$level++)
  {
      $info=$backtrace[$level];
      echo "<br>".text($info["file"]." at ".$info["line"].":".$info["function"]);
      if($level>1){
          echo "(".text(implode(",",$info["args"])).")";
      }
  }
  $logMsg.="==>".$backtrace[1]["file"]." at ".$backtrace[1]["line"].":".$backtrace[1]["function"];
  error_log($logMsg);
  
  exit;
}

/**
* @todo document use of the generate_id function
*/
function generate_id () {
  $database = $GLOBALS['adodb']['db'];
  return $database->GenID("sequences");
}

/**
* Deprecated function. Standard sql query in LibreEHR.
*
* Function that will allow use of the adodb binding
* feature to prevent sql-injection. Will continue to
* be compatible with previous function calls that do
* not use binding.
* It will return a recordset object.
* The sqlFetchArray() function should be used to
* utilize the return object.
*
* @deprecated
* @param  string  $statement  query
* @param  array   $binds      binded variables array (optional)
* @return recordset
*/
function sqlQ($statement, $binds=false )
{
  // Below line is to avoid a nasty bug in windows.
  if (empty($binds)) $binds = false;

  $recordset = $GLOBALS['adodb']['db']->Execute( $statement, $binds ) or
    HelpfulDie("query failed: $statement", getSqlLastError());
  return $recordset;
}

/**
* Simple wrapper for sqlInsert() function (deprecated).
*
* Function that will allow use of the adodb binding feature
* to prevent sql-injection.
*
* @deprecated
* @param  string   $statement  query
* @param  array    $binds      binded variables array (optional)
* @return integer  Last id generated from the sql insert command
*/
function idSqlStatement($statement , $binds=false )
{
  // Below line is to avoid a nasty bug in windows.
  if (empty($binds)) $binds = false;

  return sqlInsert($statement, $binds);
}

/**
* Simple wrapper for sqlInsert() function (deprecated).
*
* Function that will allow use of the adodb binding feature
* to prevent sql-injection.
*
* @deprecated
* @param  string   $statement  query
* @param  array    $binds      binded variables array (optional)
* @return integer  Last id generated from the sql insert command
*/
function sqlInsertClean($statement, $binds=false )
{
  // Below line is to avoid a nasty bug in windows.
  if (empty($binds)) $binds = false;

  return sqlInsert($statement, $binds);
}


/**
* Sql close connection function (deprecated)
*
* No longer needed since PHP does this automatically.
*
* @deprecated
* @return boolean
*/
function sqlClose()
{
  //----------Close our mysql connection
  $closed = $GLOBALS['adodb']['db']->close or
    HelpfulDie("could not disconnect from mysql server link", getSqlLastError());
  return $closed;
}

/**
* Very simple wrapper function and not necessary (deprecated)
*
* Do not use.
*
* @deprecated
* @return connection
*/
function get_db() {
  return $GLOBALS['adodb']['db'];
}

/**
 * Generic mysql select db function
 * Used when converted to mysqli to centralize special circumstances.
 * @param string $database
 */
function generic_sql_select_db($database, $link = null)
{
  if (is_null($link))
    $link = $GLOBALS['dbh'];
  mysqli_select_db($link, $database);
}

/**
 * Generic mysql affected rows function
 * Used when converted to mysqli to centralize special circumstances.
 *
 */
function generic_sql_affected_rows()
{
  return mysqli_affected_rows($GLOBALS['dbh']);
}

/**
 * Generic mysql insert id function
 * Used when converted to mysqli to centralize special circumstances.
 *
                 */
function generic_sql_insert_id()
{
  return mysqli_insert_id($GLOBALS['dbh']);
}


/**
 * Begin a Transaction.
 */
function sqlBeginTrans()
{
    $GLOBALS['adodb']['db']->BeginTrans();
}


/**
 * Commit a transaction
 */
function sqlCommitTrans($ok=true)
{
    $GLOBALS['adodb']['db']->CommitTrans();
}


/**
 * Rollback a transaction
 */
function sqlRollbackTrans()
{
    $GLOBALS['adodb']['db']->RollbackTrans();
}

// this function is used to sync the column structure of the parent table with the child table.Here it is used to sync the original table with the patient table.
function sync_tables ($parent_table, $child_table) {
$parent_array = array();
$child_array = array();
//get columns from parent table
$sql = "SHOW COLUMNS FROM `$parent_table`";
$result = sqlQ($sql);
while($row = sqlFetchArray($result)){
  array_push($parent_array, $row['Field']." ".$row['Type']);
}

//get columns from child table
$sql = "SHOW COLUMNS FROM `$child_table`";
$result = sqlQ($sql);
while($row = sqlFetchArray($result)){
  array_push($child_array, $row['Field']." ".$row['Type']);
}

//find the differences in their columns
if (count($parent_array) > count($child_array)) {
// it means a column is added to the parent table 
//perform add column query here
$diff = array_diff($parent_array, $child_array);
foreach ($diff as $key) {
  $first = "ALTER TABLE ";
  $second = " ".$child_table." ";
  $third = "ADD COLUMN ";
  $fourth = $key;
  $sql = $first.$second.$third.$fourth;
  sqlStatement($sql);
  return true;
}

}
elseif(count($parent_array) < count($child_array)){
//it means a column is deleted from the parent table
$diff = array_diff($child_array, $parent_array);
foreach ($diff as $key) {
  $first = "ALTER TABLE ";
  $second = " ".$child_table." ";
  $third = "DROP ";
  $fourth = strstr($key, " ", true);
  $sql = $first.$second.$third.$fourth;
  sqlStatement($sql);
  return true;
}

}
elseif (count($parent_array) == count($child_array)) {
  //IT MEANS BOTH ARE FULLY SYNCED
  return true;
}

}
?>
