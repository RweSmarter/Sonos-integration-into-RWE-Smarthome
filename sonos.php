  <?php 
/*
 * RWE Smarthome state variable triggers actions on wireles speakers systems
 * @author     Smarter user from  link: http://www.rwe-smarthome-forum.de
 * @date 15/March/2014
 * version 3
 * PhpSonos.Inc.php  class        link: http://homematic-forum.de/forum/download/file.php?id=8877
 * RWE SmartHome.php class        link: https://raw.github.com/Bubelbub/RWE-SmartHome-PHP-master/SmartHome.class.php
 * SmartHome michaelano Utilities link: http://www.rwe-smarthome-forum.de/attachment.php?aid=925
 * ----Instructions---------------------------------------------------------------------------------------------
 * Enter RWE Smarthome constants (username, passwd, RWE Smarthome IP addesse)and Sonos constants in config.ini
 * Create and configure state variables (Zustandsvariable=ZV) in RWE Smarthome. Determine delimiter for ZV names in config.ini file 
 * You can use two options in ZV names:
 * Option1: play messages as mp3 file                   Option2: execute your php script
 * 12345678901234567890123456789012345                  12345678901234567890123456789012345
 * Son_mp3Name_ZoneName_NN                             Sonos_phpFunction
 *  |       |      |      |                             |       |                                                     
 *  |       |      |       Volume 0 -100%               |       |      
 *  |       |    determin ipAddresses in config.ini     |       customer defined function mySonosScript.php
 *  |       |                                           determin variable preFix2 in config.ini
 *  |       e.g. AlarmOn without extention and storage location is defined in config.ini e.g. AlarmOn
 *  determin variable preFix1 in config.ini
 */
// ---init variables------------------------------------------------------------------------------------
global $mydebug;  # true or false is definded in config.ini
global $newLine;  # used for debugging
global $configIni;# content of config.ini file
$newLine          = php_sapi_name() == 'cli' ? "\n" : '<br />';
$configcachefile  = 'Configuration.cache';               # file name for RWE caching to accelarate reading configuration
$configIniFile    = __DIR__ . '/config.ini';             # customize config.ini  and enter username, passwd .....
//----Sonos class  in sub-folder /sonos/Sonos-PHP-master------------------------------------------------
require_once 'Sonos-PHP-master/PHPSonos.inc.php';
//----custom developed scripts in folder  /sonos--------------------------------------------------------
require_once 'mySonosScripts.php';  
//----RWE Smarthome class  in sub-folder /sonos/SmartHome-PHP-master------------------------------------
require_once 'SmartHome-PHP-master/SmartHome.php';
require_once 'SmartHome-PHP-master/Request/BaseRequest.php';
require_once 'SmartHome-PHP-master/Request/LoginRequest.php';
require_once 'SmartHome-PHP-master/Request/GetEntitiesRequest.php';
require_once 'SmartHome-PHP-master/Request/GetShcInformationRequest.php';
require_once 'SmartHome-PHP-master/Request/GetAllLogicalDeviceStatesRequest.php';
require_once 'SmartHome-PHP-master/Request/GetApplicationTokenRequest.php';
require_once 'SmartHome-PHP-master/Request/GetShcTypeRequest.php';
require_once 'SmartHome-PHP-master/Request/GetAllPhysicalDeviceStatesRequest.php';
require_once 'SmartHome-PHP-master/Request/GetMessageListRequest.php';
//----michaelano utilities in sub-folder /sonos/SmartHome-PHP-master------------------------------------
require_once 'michaelano/SmartHome-Utils.php';
//---------------------read config.ini 
if ( (file_exists($configIniFile)) && (is_readable($configIniFile)) )
      {
      $configIni = null;
      if (($configIni = @parse_ini_file($configIniFile,TRUE)) === false){
        echo "Error while read config.ini. Check syntax in your config.ini file".$newLine;  //throw new Exception('[0c802] Error while read configuration file: ' . $configIniFile);
        }
      else {
        $mydebug  = $configIni['General']['debug'];
        }
       }
// --------------------Get all logical devices states
$sh = michaelano\SmartHomePHP\SmartHomeLogin($configIniFile,$configIni['RWE_SmartHome']['Host'],$configIni['RWE_SmartHome']['Username'],$configIni['RWE_SmartHome']['Password']); #login into SH
$CONFIGR = michaelano\SmartHomePHP\GetConfigR($sh,$configcachefile, $mydebug ); # read whole configuration from SH
$ROOMS   = $CONFIGR['Rooms'];                   
$BDS     = $CONFIGR['BDs']  ;               
$LDS     = $CONFIGR['LDs']  ;                 
$devstates = $sh->getAllLogicalDeviceStates();          if ($mydebug )  {  echo '<pre>' , print_r($devstates), '</pre>';}
$LDSARR = $devstates->States->LogicalDeviceState;
//---------------------foreach logical device
foreach ($LDSARR as $LD) {
    $LID         = $LD["LID"]->__toString();
    $ROOM        = $LDS["$LID"][4];
    $LDNAME      = $LDS["$LID"][0];
    //--------------Option1: 3 delimiters = 4 variables ----------------------------------------------------------------------
    if (count(explode($configIni['Sonos']['delimiter'] , $LDNAME))==4) {
    list ($leftLDName1, $mp3file, $sonosZone,$Volume) = array_pad(explode($configIni['Sonos']['delimiter'], $LDNAME,4),4,0); # option 1: splitt variable name
	    if ($leftLDName1 == $configIni['Sonos']['triggerNameSonosPrefix1']) {   # ----------- option 1:  parse variable names into variables
	    	$VALUE = isset ($LD->Ppts->Ppt["Value"]) ? $LD->Ppts->Ppt["Value"]->__toString() : null;
	    	if($VALUE== "True" ) {
	    		$mp3file = $configIni['Sonos']['mp3Folder'].$mp3file. ".mp3";
	    		$IpSpeaker= $configIni['SonosZones'][$sonosZone];
	    		$result= playMessage($IpSpeaker, $mp3file, $Volume);
	    		echo "Option1: ZV: '". $LDNAME."' = ".$VALUE.$newLine;
	    	} 
	    }
    }   
    //--------------Option2: 1 delimiters = 2 variables------------------------------------------------------------------------
    $Test=count(explode($configIni['Sonos']['delimiter'] , $LDNAME));
    if ($Test==2) {
    list ($leftLDName2, $action) = array_pad(explode($configIni['Sonos']['delimiter'], $LDNAME,2),2,0); # option 2:  splitt variable names
	    if ($leftLDName2 == $configIni['Sonos']['triggerNameSonosPrefix2']) {   # ----------- option 2
	    	$VALUE = isset ($LD->Ppts->Ppt["Value"]) ? $LD->Ppts->Ppt["Value"]->__toString() : null;     
	    	if($VALUE== "True" ) {
	    	call_user_func ($action);    	    	
	    	echo "Option2: ZV= '".$LDNAME. "' => mySonosScripts.php  => function ". $action . " ()" . $newLine;
	    	}
	   	}
	}
 } # end foereach logical device
  
// Play MP3 on Sonos speaker
// @ $playerIP   = IP Address Sonos Player  192.168.178.25 
// @ $mp3File    = //NAS-TS-119/Qmultimedia/Musik/RweSmartHome/
// @ $Volume     =  0-100% 
function playMessage($playerIP, $mp3File, $volume)
{
global $mydebug;  # true or false is definded in config.ini
global $newLine;  # used for debugging
$wiederherstellen ="";
$sonos = new PHPSonos($playerIP);                //Sonos ZP IPAddress
# Einstellungen speichern
$save_MediaInfo = $sonos->GetMediaInfo();
$save_PositionInfo = $sonos->GetPositionInfo();
$save_Mute =$sonos->GetMute();
$save_Vol = $sonos->GetVolume();
$save_Status = $sonos->GetTransportInfo();
$save_TransportSettings = $sonos->GetTransportSettings();
$save_GetCurrentPlaylist = $sonos->GetCurrentPlaylist();
$message_pos = count($save_GetCurrentPlaylist) + 1;
# Es läuft eine Radiostation, wenn Radio läuft, muss zuerst die Liste wieder aktiviert werden
if (substr($save_PositionInfo["TrackURI"], 0, 17) == "x-rincon-mp3radio")
{  	$wiederherstellen = "Radio";   if ($mydebug ){echo "# zum Wiederherstellen es lief ein Radio Sender".$newLine;}
	$sonos->SetQueue("x-rincon-queue:" . getRINCON($playerIP) . "#0"); //Playliste aktivieren
}
elseif (substr($save_PositionInfo["TrackURI"], 0, 11) == "x-sonos-mms" || substr($save_PositionInfo["TrackURI"], 0, 11)== "x-file-cifs") // Es läuft eine Musikliste
{
	$wiederherstellen = "Playlist";      if ($mydebug ){echo "# es laeuft Playliste".$newLine;}
}
elseif ($save_PositionInfo["TrackURI"] == "" ) {
	if ($mydebug ){echo "# es wird keine Musik abgespielt, also einfach nur nachricht abspielen".$newLine;}
}
$sonos->AddToQueue("x-file-cifs:" . $mp3File);
//Auf den neuen Track zeigen
$sonos->SetTrack($message_pos);
$sonos->SetPlayMode("NORMAL");
$sonos->SetMute(false);
$sonos->SetVolume($volume);
$sonos->Play();   // Abspielen
$abort = false;

# Prüfen ob Meldung zu ende gespielt ist
do {
	# Infos zur Message einlesen
	$message_PositionInfo = $sonos->GetPositionInfo();
		if ($message_PositionInfo["RelTime"] == $message_PositionInfo["TrackDuration"] ) {
			sleep(1);
			$abort = true; // Message fertig
		}
} # do

while($abort == false);
    $sonos->RemoveFromQueue($message_pos); 	if ($mydebug ){echo "# Message wieder aus Queue entfernen".$newLine;}
    if ($wiederherstellen == "Playlist" ){
    	$sonos->SetTrack($save_PositionInfo["Track"]); if ($mydebug ){echo "Wieder alten Zustand herstellen: ";}
		$sonos->Seek($save_PositionInfo["RelTime"],"NONE");
		$sonos->SetVolume($save_Vol);
		$sonos->SetMute($save_Mute);
    	# Wenn alte Playlist in Pause war dann Pause setzen ansonsten Play, save_Status //1 = PLAYING //2 = PAUSED_PLAYBACK //3 = STOPPED
		# $save_TransportSettings repeat oder shuffle wenn Repeat oder Shuffle aktiviert ist und die Musik nicht läuft,muss die Pause gesetzt werden, da sonst die Musik anläuft
		if($save_Status != 1 && ($save_TransportSettings['shuffle'] == 1 || $save_TransportSettings['repeat'] == 1)) {
			$sonos->Pause();
			}
		if ($save_Status == 1) {
			  $sonos->Play();
			}
		if ($save_Status == 3 ) {
			$sonos->Stop();
			}
	}elseif ($wiederherstellen == "Radio" ){
		# je nach dem ob Radio vorhier Lief oder nicht da Zustand wieder herstellen. save_Status //1 = PLAYING //2 = PAUSED_PLAYBACK //3 = STOPPED
		# $save_TransportSettings  repeat shuffle ...alten Radiosender weiterspielen
			$sonos->SetRadio($save_PositionInfo["URI"], $save_MediaInfo["title"]);
			$sonos->SetVolume($save_Vol);
			$sonos->SetMute($save_Mute);
			if($save_Status == 1) {
				$sonos->Play();
		}
	}
}
# Funktionen
// @ zoneplayerIp = IP Address Sonos Player  192.168.178.25
// @return = UID String, den man im $sonos->SetQueue Befehl braucht.
function getRINCON($zoneplayerIp) {
	$url = "http://" . $zoneplayerIp . ":1400/status/zp";
	$xml = simpleXML_load_file($url);
	$uid = $xml->ZPInfo->LocalUID;
	return $uid;
}
?>
