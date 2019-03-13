<?php
/*
______  _   _  _____ ___  ___          ______  _   _ ______            _____  _      _____  _____  _   _  _____ 
|  ___|| | | ||  ___||  \/  |          | ___ \| | | || ___ \          /  __ \| |    |_   _||  ___|| \ | ||_   _|
| |_   | |_| || |__  | .  . |  ______  | |_/ /| |_| || |_/ /  ______  | /  \/| |      | |  | |__  |  \| |  | |  
|  _|  |  _  ||  __| | |\/| | |______| |  __/ |  _  ||  __/  |______| | |    | |      | |  |  __| | . ` |  | |  
| |    | | | || |___ | |  | |          | |    | | | || |              | \__/\| |____ _| |_ | |___ | |\  |  | |  
\_|    \_| |_/\____/ \_|  |_/          \_|    \_| |_/\_|               \____/\_____/ \___/ \____/ \_| \_/  \_/  
################################################################################################################

Autor: Hannes Bosch 13.03.2019
Info:
	FHEM-PHP-CLIENT ist eine Php Anwendung um von einem Webserver auf einen FHEM Server zugreifen zu können.
	So lassen sich zum Beispiel Befehle senden oder werte auslesen.
	Zusätzlich ist diese Anwendung hilfreich um sich ein eigenes UI mittels Html zu erstellen.	
Systemvorraussetzungen:
	
	
Code (Änderungen auf eigene Gefahr):
*/
class FHEM{
	public $Server_Host;
	public $CsrfToken;
	private $UserPass = null;
	
	function __construct($address, $port=8083, $user=null, $pass=null){
		//Server addresse Speichern
		$this->Server_Host = $address.":".$port."/fhem";
		//Logindaten
		$this->UserPass = "$user:$pass";
		//Csrf Tokenfür spätere Anfragen auslesen
		$this->CsrfToken = $this->getToken($address, $port, $user, $pass);
	}
	
	//++getToken++
	//Liest das Crsf-Token aus dem Antwort Header des Servers
	function getToken($address, $port=8083, $user=null, $pass=null){
		$ch = curl_init(); //Neue Curl Verbindung
		$headers = []; //Array Headers, wird später mit Antwortkopfzeilen gefüllt
		
		//Curl Einstellungen
		curl_setopt($ch, CURLOPT_URL, $this->Server_Host); //Adresse
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Body nicht ausgeben
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); //Weiterleitungen Aktzeptieren
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, //Liest Header aus und speichert diese im Array $headers
		  function($curl, $header) use (&$headers)
		  {
			$len = strlen($header);
			$header = explode(':', $header, 2);
			if (count($header) < 2) // ignore invalid headers
			  return $len;

			$name = strtolower(trim($header[0]));
			if (!array_key_exists($name, $headers))
			  $headers[$name] = [trim($header[1])];
			else
			  $headers[$name][] = trim($header[1]);

			return $len;
		  }
		);
		//Logindaten mitsenden
			if($this->UserPass !== null){
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
				curl_setopt($ch, CURLOPT_USERPWD, $this->UserPass);
			}
		curl_exec($ch);
		
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE); //HTTP Code auslesen
		curl_close($ch);
		if ($httpcode>=200 && $httpcode<300){
			return $headers["x-fhem-csrftoken"][0]; //Erfolgreich
		} else {
			die("Error beim lesen des CSRF Tokens HTTP Code: ".$httpcode); //Fehler
		}
	}
	//++curl++
	//Interne Funktion, Ist für alle Anfragen an den Server zuständig
	private function curl($query, $path=""){
		if($this->CsrfToken == null) die("Kein Csrf Token gesetzt");
		
		$query["fwcsrf"] = $this->CsrfToken;
		$query["XHR"] = 1;
		
		$query_str = http_build_query($query);
		$url = $this->Server_Host.$path."?".$query_str;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url); //Adresse
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Body nicht ausgeben
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); //Weiterleitungen Aktzeptieren
		//Logindaten mitsenden
			if($this->UserPass !== null){
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
				curl_setopt($ch, CURLOPT_USERPWD, $this->UserPass);
			}
		$data = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE); //HTTP Code auslesen
		curl_close($ch);
		if ($httpcode>=200 && $httpcode<300){
			return $data; //Erfolgreich
		} else {
			die("Error bei Curl Anfrage: ".$httpcode); //Fehler
		}
	}
	
	//++cmd+++
	//Sendet einen FHEM Befehl und gibt die Antwort zurück
	function cmd($command){
		return $this->curl(['cmd' => $command]);
	}
	//++getRooms++
	//Liefert einen Array mit allen Räumen zurück
	function getRooms(){
		$data = json_decode($this->curl(['cmd' => "jsonlist2 TYPE=.* room"]));
		$rooms = [];
		foreach($data->Results as $device){
			if(isset($device->Attributes->room)){
				array_push($rooms, $device->Attributes->room);
			}
		}
		$rooms = array_unique($rooms);
		sort($rooms);
		return $rooms;
	}
	//++getDevicesInRoom++
	//Gibt alle Geräte aus einem Raum zurück
	function getDevicesInRoom($room){
		$data = json_decode($this->curl(['cmd' => "jsonlist2 room=".$room]));
		return $data;
	}
	//++getDeviceNamesInRoom++
	//Listet alle Geräte in einem Raum
	function getDeviceNamesInRoom($room){
		$data = json_decode($this->curl(['cmd' => "jsonlist2 room=".$room]));
		$devices = [];
		foreach($data->Results as $device){
			if(isset($device->Name)){
				array_push($devices, $device->Name);
			}
		}
		$devices = array_unique($devices);
		sort($devices);
		return $devices;
	}
	//++get++
	//get Befehl in Fhem
	function get($device, $devspec){
			$result = $this->curl(['cmd' => "get ".$device." ".$devspec]);
			return str_replace("\n", "", $result);
	}
	//++getState++
	//Gibt den aktuellen Status eines Gerätes zurück
	function getState($device){
		$result = $this->curl(['cmd' => "getstate ".$device]);
		return $result;
	}
	//++getFhemSVGImage++
	//Gibt den Svg Code aus dem /www/images/fhemSVG/ Ordner zurück
	function getFhemSVGImage($imageName){
		return $this->curl([], "/images/fhemSVG/".$imageName.".svg");
	}
	//++getOpenAutomationImage++
	//Gibt den Svg Code aus dem /www/images/openautomation/ Ordner zurück
	function getOpenAutomationImage($imageName){
		return $this->curl([], "/images/openautomation/".$imageName.".svg");
	}
}