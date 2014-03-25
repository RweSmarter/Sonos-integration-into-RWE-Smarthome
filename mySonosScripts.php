<?php 
// Smarters user defined functions
function StopAll($parameter)
{ 	global $configIni;# content of config.ini file
	$allZones = $configIni['SonosZones'];  # read Sonos zone from config.ini
	foreach($allZones as $index => $zone)
	 {
  		$ipAddress = $allZones[$index];    # read Sonos zones from config ini
		$sonos = new PHPSonos($ipAddress); # init Sonos speaker
  		$sonos->Stop();  
  	 }
}

function Radio($parameter)

{   global $configIni;# content of config.ini file
	global $LDSARR;
	global $LDS;
	global $newLine;  # used for debugging
	$RWEroom=strtoupper($parameter); # RWE room name only capitle letters, Sonos room name case sensitive
	//---------------------foreach logical device
	foreach ($LDSARR as $LD) {
		$LID         = $LD["LID"]->__toString();
		$ROOM        = $LDS["$LID"][4];
		$LDNAME      = $LDS["$LID"][0];
		$PTTMP       = isset ($LD["PtTmp"])            ? $LD["PtTmp"]->__toString()            : null;  // Rst: RoomTemperatureActuatorState    Actor: Temperature
		if (isset($PTTMP) && $ROOM ==$RWEroom)  
		{
			$RadioStation = isset($configIni['SonosRadiosStation'][$PTTMP]) ? $configIni['SonosRadiosStation'][$PTTMP] : ""; # read radio station from config.ini
			$RadioURL     = isset($configIni['SonosRadiosURL'] [$PTTMP]) ? $configIni['SonosRadiosURL'] [$PTTMP] : "";    # read streaming URL from cnfig.ini
			$ipAddress    = $configIni['SonosZones'][$parameter];     # replace zone name with speaker IP address from config.ini 
			$sonos = new PHPSonos($ipAddress);                        # init Sonos speaker
			$radiosender = $sonos->GetPositionInfo();			      # get Sonos info
			$senderurl = $radiosender["URI"];                         # current radio station
			$save_Status = $sonos->GetTransportInfo();                # save_Status //1 = PLAYING //2 = PAUSED_PLAYBACK //3 = STOPPED
			if ($senderurl !== $RadioURL and $RadioURL !== "" and $save_Status == 1 )  # if new station & url not empty (default temperatur used for playlists) and player is playing
			{
				$sonos->SetRadio($RadioURL, $RadioStation);               # Play radio
				$sonos->Play();                                           
				echo "Option3: ZV= '".$LDNAME. "' => Radio= ". $PTTMP . " Grad   =>Station= " . $RadioStation . $newLine;
			}
		}
	}// end foreach
}

?>
