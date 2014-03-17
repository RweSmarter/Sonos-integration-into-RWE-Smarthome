<?php 
// Smarters user defined functions
function StopAll()
{ 	ini_set('allow_url_fopen ','ON');
	global $configIni;# content of config.ini file
	$allZones = $configIni['SonosZones'];
	foreach($allZones as $index => $zone)
	 {
  		$ipAddress = $allZones[$index];
		$sonos = new PHPSonos($ipAddress); //Sonos IPAdresse
  		$sonos->Stop();
  	 }
}
?>
