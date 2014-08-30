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

$prev_speed_query = "SELECT `speed` FROM `liveInfo` WHERE 'id' =".$GLOBALS['id'];
if($prev_speed_query_result = $DB->query($prev_speed_query))
	$prev_speed = $prev_speed_query_result['speed'];

if(abs($speed - $prev_speed) >= 5.0 && $speed >= 30.0){
	$nearbyVehicleInfo = getNearbyVehicleInfo($latitude, $longitude);
	$AvgLimit = calculateAverageSpeed($nearbyVehicleInfo);
	$numberOfVehiclesNearby = count($nearbyVehicleInfo);
	$overspeeding = isOvespeeding($speed, $AvgLimit, $numberOfVehiclesNearby);
	if($overspeeding){
		setNearbyAlert($nearbyVehicleInfo);
		setNearbyOSVList($nearbyVehicleInfo);
	}
	else
		setSelfOSTag($GLOBALS['id'],0);
}

//updating with new values 
$update_values_query = "UPDATE `liveInfo` SET `speed`=".$GLOBALS['speed'].", `latitude`=".$GLOBALS['lat'].", `longitude`=".$GLOBALS['lng']." WHERE id=".$GLOBALS['id'];
$DB->query($update_values_query);


/**
 * utility functions
 */

/**
 * gets information about the nearby vechiles. Nearby vehicles are those which fall within a rectangular range of 2kms
 * @param  [double] $lat
 * @param  [double] $lng
 * @return [array]
 */
function getNearbyVehicleInfo($lat, $lng){
	// $nearby_vehicle_query = 'SELECT `id`, `speed`, `latitude`, `longitude` FROM `liveInfo` WHERE latitude >'.$lat.' AND longitude >'.$lng.' AND latitude <'.$lat.' AND longitude >'.$lng;
	$nearby_vehicle_query = 'SELECT `id`, `speed` FROM `liveInfo` WHERE ABS(latitude-'.$lat.')>0.01 AND ABS(longitude-'.$lng.')>0.01';
	if($nearby_vehicle_query_result = $DB->query($nearby_vehicle_query))
		$nearby_vehicle_info[$nearby_vehicle_query_result['id']] = $nearby_vehicle_query_result['speed'];
	return $nearby_vehicle_info;
}

/**
 * Calculates the average speed of the vehicles nearby
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
 * finds if vehicle is overspeeding or not
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
 * sets the alert value of nearby vehicle as 1
 * @param [array] $info
 */
function setNearbyAlert($info){
	foreach ($info as $id => $value){
		$set_nearby_alert_query = "UPDATE `liveInfo` SET alert=1 WHERE id=".$id;
		$DB->query($set_nearby_alert_query);
	}
}
/**
 * sets the value of selfOSTag
 * @param [int] $id
 * @param [int] $value
 */
function setSelfOSTag($id, $value){
	$set_selfOStag_query = "UPDATE `liveInfo` SET selfOStag =".$value." WHERE id=".$id;
	$DB->query($set_selfOStag_query);
}

/**
 * updates the value for the nearby OSVlist
 * @param [array] $info
 */
function setNearbyOSVList($info){
	foreach ($info as $id => $value){
		$get_nearby_OSVlist_query = "SELECT OSVlist FROM `liveInfo` WHERE id = ".$id;
		if($nearby_OSVlist = $DB->query($set_nearby_alert_query)){
			if(is_null($nearby_OSVlist['OSVlist']))
				$newNearbyOSVlist = $GLOBALS['id'];
			else
				$newNearbyOSVlist = $nearby_OSVlist['OSVlist'].','.$GLOBALS['id'];
			$set_nearby_OSVlist_query = "UPDATE `liveInfo` SET OSVlist =".$newNearbyOSVlist." WHERE id=".$id;
			$DB->query($set_nearby_OSVlist_query);
		}
	}	

}

?>