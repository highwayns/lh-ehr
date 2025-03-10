<?php
//function returns the next most recent appointment given a pid
function getNextAppointment ($pid) {
$query = "select * from calendar where pid=? and time>now() order by time limit 1";
$rez = sqlStatement($query, array($pid));
echo "<!-- getNextAppointment $pid -$query- -->";
	return sqlFetchArray($rez);
}


//this function returns calendar data given date and provider
function getCalendarDay($datetime,$owner,$groupname) {
	$query = "select * from calendar where time like ? and owner like ? and groupname like ? order by time";
	$rez = sqlStatement($query, array($datetime . "%", $owner, $groupname));
	for($iter=0; $row=sqlFetchArray($rez); $iter++)
		$returnval[$iter]=$row;

echo "<!-- getCalendarDay -$query- $datetime $owner $groupname -->";
	return $returnval;
}

//toggle the status of a noshow calendar entry
function toggleNoShow( $cid) {
	$rez = sqlStatement("select noshow from calendar where id=?", array($cid));
	$row=sqlFetchArray($rez);
	$oldnoshowval = $row{noshow};
	$newnoshowval = !($oldnoshowval);
	$query = "update calendar set noshow=? where id=?";
	sqlStatement($query, array($newnoshowval, $cid));
}


//return array of provider usernames
function getProviderUsernames() {
	$rez = sqlStatement("select distinct username, lname, fname from users " .
		"where authorized = 1 and username != ''");
	for($iter=0; $row=sqlFetchArray($rez); $iter++)
		$returnval[$iter]=$row;

	return $returnval;
}


function getIDfromUser($name) {
	$query = "select id from users where username=\"" . add_escape_custom($name) . "\" limit 1";
	$rez = sqlStatement($query);
	$row = sqlFetchArray($rez);
	if (!is_numeric($row['id']))
		return -1;
	else
		return $row['id'];
}
function getAuthorizedIDs() {
	$query = "select id, username from users where authorized=1 and username != ''";
	$rez = sqlStatement($query);
	for($iter=0; $row=sqlFetchArray($rez); $iter++)
		$returnval[$iter]=$row;
	return $returnval;
}


function getUserInfo() {
	$rez = sqlStatement("select distinct id, username, lname, fname, authorized, " .
		"info, facility from users where username != ''");
	for($iter=0; $row=sqlFetchArray($rez); $iter++)
		$returnval[$iter]=$row;

	return $returnval;
}
function getUsername($uID) {
	$pi = getProviderInfo($uID,false);
	if (strlen($pi["username"]) > 0) {
		return $pi['username'];
	}

	return "";
}

//	returns an array of facility id and names
function getUserFacilities($uID) {
  if (!$GLOBALS['restrict_user_facility']) {
  	$rez = sqlStatement("
		select id, name, color
		from facility
		where service_location != 0
	");
  } else {
	  $rez = sqlStatement("
		  select uf.facility_id as id, f.name, f.color
		  from users_facility uf
		  left join facility f on (uf.facility_id = f.id)
		  where uf.tablename='users'
		  and uf.table_id = ?
	    ", array($uID) );
  }
  $returnVal = array();
  while ($row = sqlFetchArray($rez))
	  $returnVal[] = $row;
  return $returnVal;
}

//retrieve the name based on the username
function getNamefromUsername($username) {
	$query = "select * from users where username like '$username' and username != ''";
	$res = sqlQuery($query);
	return $res;
}

//retrieve calendar information from calendar id
function getCalendarfromID ($calid) {

	//this table is deprecated and has been removed from new versions
	//return sqlQuery("select * from calendar where id='$calid'");
}

//get calendar dates in a range of dates
function getCalendarRanges ($fromdate, $todate, $username) {
	$rez = sqlStatement("select * from calendar where time>=? and time<? and owner like ? order by time", array($fromdate, $todate, $username));
	for($iter=0; $row=sqlFetchArray($rez); $iter++)
		$returnval[$iter]=$row;

	return $returnval;

}


//get calendar dates in a range of dates
function getCalendarbyTimeandRange ($time, $fromdate, $todate, $username) {
	$query = "select * from calendar where time like ? and time>=? and time<? and owner like ? order by time";
	$rez = sqlStatement($query, array("%". $time . "%", $fromdate, $todate, $username));
	for($iter=0; $row=sqlFetchArray($rez); $iter++)
		$returnval[$iter]=$row;
echo "<!-- getcalendarbytimeandrange -$query- -->";
	return $returnval;

}


//add new calendar entry item
function newCalendarItem (
$pid,
$time,
$reason,
$owner,
$groupname


) {

	sqlQuery("insert into calendar set pid=?,time=?,reason=?,owner=?,groupname=?,date=now()", array($pid, $time, $reason, $owner, $groupname) );
}

//delete a calendar entry item
function deleteCalendarItem( $calid,$pid) {
	sqlQuery("delete from calendar where id=? and pid=?", array($calid, $pid) );

}


//save an edited calendar entry item
function saveCalendarUpdate (
$calid,
$pid,
$time,
$reason,
$owner,
$groupname


) {

	sqlQuery("update calendar set pid=?,time=?,reason=?,owner=?,groupname=?,date=now() where id=?", array($pid, $time, $reason, $owner, $groupname, $calid) );
}


//replicated functionality of postcalendar_getDate
function pc_getDate($format='%Y%m%d')  {
//    list($Date,$jumpday,$jumpmonth,$jumpyear) = pnVarCleanFromInput('Date','jumpday','jumpmonth','jumpyear');
	if(!isset($Date)) {
        // if we still don't have a date then calculate it
        $time = time();
		// check the jump menu
        if(!isset($jumpday))   $jumpday = strftime('%d',$time);
        if(!isset($jumpmonth)) $jumpmonth = strftime('%m',$time);
        if(!isset($jumpyear))  $jumpyear = strftime('%Y',$time);
		// create the correct date string
        $Date = (int) "$jumpyear$jumpmonth$jumpday";
    }
	$y = substr($Date,0,4);
	$m = substr($Date,4,2);
	$d = substr($Date,6,2);
    return strftime($format,mktime(0,0,0,$m,$d,$y));
}


//this function refreshes Calendar when certain events are triggered
function refreshCalendar() {
    echo "<script type='text/javascript'>";
      echo "var docRoot = top.$('#mainBox');
            //get references to all possible frames for calendar
            var leftFrame = docRoot.find('iframe[name=" . "lst" . "]'); //initial loading of EHR
            var rightFrame = docRoot.find('iframe[name=" . "pat" . "]'); //initial loading of EHR
            var calFrame = docRoot.find('iframe[name=" . "cal" . "]'); //via main menu

            var calFrameStr = 'interface/main/main_info.php'; //url of Calendar Screen used for identification

            //get source string of frames
            var leftFrameSrc = leftFrame.attr('src');
            var rightFrameSrc = rightFrame.attr('src');
            var calFrameSrc = calFrame.attr('src');

            //when Calendar is opened via main menu
            if (calFrameSrc !== undefined) {
                calFrame.attr('src', calFrameSrc); //refresh frame
            } else {
                //when Calendar is opened in one of two initially loaded frames

                //if opened in left frame
                if (leftFrameSrc !== undefined) {
                    if (leftFrameSrc.indexOf(calFrameStr) !== -1) {
                        leftFrame.attr('src', leftFrameSrc); //refresh frame
                    }
                }

                //if opened in right frame
                if (rightFrameSrc !== undefined) {
                    if (rightFrameSrc.indexOf(calFrameStr) !== -1) {
                        rightFrame.attr('src', rightFrameSrc); //refresh frame
                    }
                }
            }";
    echo "</script>";
}

?>
