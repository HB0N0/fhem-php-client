# FHEM-PHP-CLIENT
### Autor:
Hannes Bosch 13.03.2019
### Info:
FHEM-PHP-CLIENT ist eine Php Anwendung um von einem Webserver auf einen FHEM Server zugreifen zu können.
So lassen sich zum Beispiel Befehle senden oder werte auslesen.
Zusätzlich ist diese Anwendung hilfreich um sich ein eigenes UI mittels Html zu erstellen.	

### Installation
Die Datei "fhem_api.php" auf den Webserver kopieren.
Nun kann sie ganz einfach in einen Php Skript eingebunden werden:
```php
<?php
	require_once("fhem_api.php");
```

### Verbindung zum Server
Um Php mit dem Fhem Server zu Verbinden muss eine neue Instanz der Klasse FHEM intialisiert werden:
```php
<?php
	require_once("fhem_api.php");
	
	$fhem = new FHEM("192.168.0.55", 8083, "BENUTZER", "PASSWORT");
```
Benutzername und Passwort optional, nur wenn diese in der fhem.cfg festgelegt wurden müssen sie mit übermittelt werden.

### Funktionen
**cmd:**
Hiermit kann jeglicher Befehl in FHEM ausgeführt werden.
```php
$fhem->cmd("set Lampe1 an");	
```
der Rückgabewert wird als String zurückgegeben.
	
**getRooms:**
Gibt einen Array aus allen in Fhem definierten Räumen zurück.
```php
$fhem->getRooms();
//["Wohnzimmer", "Esszimmer", "Schlafzimmer", ...]
```

**getDevicesInRoom:**
Gibt alle Daten zu Geräten in einem Raum zurück.
```php
$fhem->getDevicesInRoom("Wohnzimmer");
```

**getDeviceNamesInRoom:**
Gibt nur die Namen aller Geräte in einem Raum zurück.
```php
$fhem->getDeviceNamesInRoom("Wohnzimmer");
```

