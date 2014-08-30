<?php
include("connect_db.php")
$latitude = $_POST['lat'];
$longitude = $_POST['lng'];
$speed = $_POST['speed'];
$id = $_POST['id'];

$GLOBALS['id'] = $id;
$GLOBALS['lat'] = $latitude;
$GLOBALS['lng'] = $longitude;
$GLOBALS['speed'] = $speed;

$check_alert_query = "SELECT alert FROM `liveInfo` WHERE id =".$GLOBALS['id'];
$alert_query_result = $DB->query($check_alert_query);
if($alert_query_result['alert']==1){

	$OSVidList = getOSVidList($GLOBALS['id']);

	if($inRangeOSV = OSVInRange($GLOBALS['lat'], $GLOBALS['lng'], $OSVidList)) 
		if($OSVinfo = OSVStillSpeeding($inRangeOSV))
			notifyUser($GLOBALS['id'], $OSVinfo);
	else
		setAlert($GLOBALS['id'], 0);
}

//updating with new values 
$update_values_query = "UPDATE `liveInfo` SET `speed`=".$GLOBALS['speed'].", `latitude`=".$GLOBALS['lat'].", `longitude`=".$GLOBALS['lng']." WHERE id=".$GLOBALS['id'];
$DB->query($update_values_query);


/**
 * Gives the id of the nearby OSV
 * @param [integer] $id
 */
function getOSVidList($id){
	$get_OSV_id_query = "SELECT OSVlist FROM `liveInfo` WHERE id =".$id;
	if($get_OSV_id_result = $DB->query($get_OSV_id_query)){
		$OSVidList = explode(',', $get_OSV_id_result);
		if(count($OSVidList)!=0)
			return $OSVidList;
		else
			return 0;
	}
	else 
		return 0;
}

/**
 * Checking if the vehicle in OSVlist is in Range 
 * @param [double] $latitude
 * @param [double] $longitude
 * @param [array] $OSVList
 */
function OSVInRange($latitude, $longitude, $OSVList){
	$OSVInRangeInfo = array();
	$i=0;
	foreach ($OSVList as $key ) {
		$get_OSV_info_query = "SELECT `id`, `speed`, `latitude`, `longitude` FROM `liveInfo` WHERE id=".$key;
		$get_OSV_info_result = $DB->query($get_OSV_info_query);
		if(abs($get_OSV_info_result['latitude']-$latitude)<0.01 && abs($get_OSV_info_result['longitude']-$longitude)<0.01){
			$OSVInRange[$i]['id'] = $get_OSV_info_result['id'];
			$OSVInRange[$i]['latitude'] = $get_OSV_info_result['latitude'];
			$OSVInRange[$i]['longitude'] = $get_OSV_info_result['longitude'];
			$OSVInRange[$i]['speed'] = $get_OSV_info_result['speed'];
			$i++;
		}
	}
	return $OSVInRange;
}

/**
 * to check if OSV in range is still speeding
 * @param [array] $inRangeOSVinfo
 */
function OSVStillSpeeding($inRangeOSVinfo){
	$speedList = array();
	$i=0;
	foreach ($inRangeOSVinfo as $vehicle) {
		$speedList[$i] = $vehicle['speed'];
		$i++;
	}
	$refSpeed = calculateAverageSpeed($speedList);
	$j=0;
	$vehicleCount=count($inRangeOSVinfo);
	$OSVtoReturn = array();
	foreach ($inRangeOSVinfo as $OSvehicle) {
		if(isOverspeeding($OSvehicle['speed'],$refSpeed,$vehicleCount)){ //possible error in indexing of $OSvehicle
			$OSVtoReturn[$j]['speed'] = $OSvehicle['speed'];
			$OSVtoReturn[$j]['latitude'] = $OSvehicle['latitude'];
			$OSVtoReturn[$j]['longitude'] = $OSvehicle['longitude'];
			$OSVtoReturn[$j]['id'] = $OSvehicle['id'];
			$j++;
		}	
	}
	return $OSVtoReturn;
}

/**
 * to check is vehicle is relatively overspeeding
 * @param  [double]  $speed
 * @param  [double]  $reference
 * @param  [int]  $count
 * @return boolean
 */
function isOverspeeding($speed, $reference, $count){
	if(abs($speed-$reference)<20)
		return true;
	else 
		return false;
}

/**
 * calculates average speed
 * @param  [array] $list
 * @return [double]
 */
function calculateAverageSpeed($list){
	$speedSum = 0;
	foreach ($list as $id => $speed)
		$speedSum += $list[$id];
	$avgSpeed = $speedSum/size($list);
	return $avgSpeed;
}

/**
 * notifes user of the danger from nearby OSV
 * @param  [int] $id
 * @param  [array] $OSVinfo
 * @return [type]
 */
function notifyUser($id, $OSVinfo){
	for($OSVinfo as $info)
		echo $OSVinfo['id'].','.$OSVinfo['speed'].','.$OSVinfo['latitude'].','.$OSVinfo['longitude'].';';
}

/**
 * set alert value to zero, since no OSV in surroundings
 * @param [int] $id
 * @param [int] $value
 */
function setAlert($id , $value){
	$set_alert_query = "UPDATE `liveInfo` SET alert=0 WHERE id=".$id;
	$DB->query($set_alert_query);
}

?>