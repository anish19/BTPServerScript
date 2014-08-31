<?php
include("connect_db.php");
$latitude = $_POST['lat'];
$longitude = $_POST['lng'];
$speed = $_POST['speed'];
$id = $_POST['id'];

$GLOBALS['id'] = $id;
$GLOBALS['lat'] = $latitude;
$GLOBALS['lng'] = $longitude;
$GLOBALS['speed'] = $speed;

$check_alert_query = "SELECT alert FROM `liveInfo` WHERE id =".$GLOBALS['id'];
$alert_query_result = $GLOBALS['DB']->query($check_alert_query);
$alert_query_row = $alert_query_result->fetch_assoc();
$regularResponse = 0;

if($alert_query_row['alert']==1){
	$OSVidList = getOSVidList($GLOBALS['id']);
	if(count($OSVidList)){
		$inRangeOSVList = getInRangeOSVList($OSVidList);
		if(count($inRangeOSVList)){
			notifyUser($GLOBALS['id'], $inRangeOSVList, "alert");
			updateOSVList($inRangeOSVList);
		}
		else
			$regularResponse=1;
	}
	else
		$regularResponse=1;
}
else
	$regularResponse=1;

if($regularResponse){
	setAlert($GLOBALS['id'], 0);
    notifyUser($GLOBALS['id'], "","");
	updateOSVList($inRangeOSVList);

}

$update_values_query = "UPDATE `liveInfo` SET `speed`=".$GLOBALS['speed'].", `latitude`=".$GLOBALS['lat'].", `longitude`=".$GLOBALS['lng']." WHERE id=".$GLOBALS['id'];
$GLOBALS['DB']->query($update_values_query);

function getOSVidList($id){
	$get_OSV_id_query = "SELECT OSVlist FROM `liveInfo` WHERE id =".$id;
	if($get_OSV_id_result = $GLOBALS['DB']->query($get_OSV_id_query)){
		$get_OSV_id_row = $get_OSV_id_result->fetch_assoc();
		$OSVidList = explode(',', $get_OSV_id_row['OSVlist']);
		if(count($OSVidList)!=0)
			return $OSVidList;
		else
			return 0;
	}
	else 
		return 0;
}

function getInRangeOSVList($list){
	$i=0;
	foreach ($list as $id) {
		$query = 'SELECT `id`, `speed`, `latitude`, `longitude`, `selfOSvalue` FROM `liveInfo` WHERE speed>=selfOSvalue AND selfOSvalue>0 AND ABS(latitude-'.$GLOBALS['lat'].')<0.01 AND ABS(longitude-'.$GLOBALS['lng'].')<0.01 AND id='.$id;
		if($query_result = $GLOBALS['DB']->query($query)){
			while($query_row = $query_result->fetch_assoc()){
				$returnInfo[$i]['id'] =  $query_row['id'];
				$returnInfo[$i]['speed'] = $query_row['speed'];
				$returnInfo[$i]['latitude'] = $query_row['latitude'];
				$returnInfo[$i]['longitude'] = $query_row['longitude'];
				$i++;
			}
		}
	}
	return $returnInfo;
}

function notifyUser($userid, $OSVinfo, $responseType){
	if($responseType == "alert"){
		foreach($OSVinfo as $info)
			echo $info['id'].','.$info['speed'].','.$info['latitude'].','.$info['longitude'].';';
	}
	else{
		$OSVinfo['id'] = 0;
		$OSVinfo['speed'] = 0;
		$OSVinfo['latitude'] = 0;
		$OSVinfo['longitude'] = 0;
		echo $OSVinfo['id'].','.$OSVinfo['speed'].','.$OSVinfo['latitude'].','.$OSVinfo['longitude'].';';
	}
}

function setAlert($id , $value){
	$set_alert_query = "UPDATE `liveInfo` SET alert=0 WHERE id=".$id;
	$GLOBALS['DB']->query($set_alert_query);
}

function updateOSVList($inRangeOSVList){
	$temp = array();
	$i=0;
	foreach ($inRangeOSVList as $OSVInfo) {
		$temp[$i] = $OSVInfo['id'];
		$i++;
	}
	if($i==0)
		$list = "NULL";
	else
		$list = implode(",",$temp);
	$update_OSV_list_query = "UPDATE `liveInfo` SET `OSVlist`=".$list." WHERE id=".$GLOBALS['id'];
	$GLOBALS['DB']->query($update_OSV_list_query);
}
?>