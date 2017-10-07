Pluggit UVC PHP Module für IP-Symcon
===
Dieses IP-Symcon PHP Modul ermöglicht die Kommunikation mit Pluggit Lüftungsgeräten auf Modbus Basis.

Das Modul greift auf die PHP Modbus Funktionsbibliothek von Jan Krakora zurück. Diese ist ebenfalls auf Github zu finden.

## 0. Inhaltsverzeichnis  

1. [Funktionsumfang](#1-funktionsumfang)
2. [Anforderungen](#2-anforderungen)
3. [Installation & Konfiguration](#3-installation--konfiguration)
4. [Funktionen](#4-funktionen)

## 1. Funktionsumfang  
Die folgenden Funktionalitäten sind implementiert:
Auslesen von folgenden Parametern:
- Betriebsmodus
- Bypass Status
- CO2 Sensor
- VOC Sensor
- Luftfeuchte Sensor
- Temperatur Abluft
- Temperatur Außenluft
- Temperatur Fernbedienung
- Temperatur Fortluft
- Temperatur Zuluft
- Lüfter Stufe
- Lüfter 1 Geschwindigkeit
- Lüfter 2 Geschwindigkeit
- Leistung Vorheizregister
- Filter Restzeit
- IP-Adresse
- MAC-Adresse
- Netzwerkmaske
- Gateway
- Firmwareversion
- Seriennummer
- DHCP aktiviert
- Störung

Setzen von folgenden Parametern:
- Betriebsmodus
- Bypass Status
- Lüfter Stufe
- Reset Filter Restzeit

## 2. Anforderungen

- IP-Symcon 4.x installation (Linux / Windows)
- Netzwerkverbindung zum Pluggit Gerät

## 3. Installation & Konfiguration

### Installation in IPS 4.x
Im "Module Control" (Kern Instanzen->Modules) die URL "git://github.com/daschaefer/SymconPluggit.git" hinzufügen.  
Danach ist es möglich eine neue Pluggit UVC Instanz innerhalb des Objektbaumes von IP-Symcon zu erstellen.
### Konfiguration
**IP-Adresse:**

*Die IP-Adresse unter der die Pluggit Anlage erreichbar ist (in der Regel macht hier eine statische IP-Adressvergabe Sinn).*

**Abfrageintervall (s):**

*Die Variablen werden in dem angegebenen Intervall aktualisiert. (Default: 3s)*

**Stufenwiederherstellung (h):**

*Beim setzen der Lüfterstufe 4 wird diese nach der angegebenen Zeit wieder auf Stufe 3 zurückgesetzt. (Default: 1h)*

**Störungen protokollieren:**

*Aktiviert eine Variable in der alle Störungen protokolliert werden.*

## 4. Funktionen

```php
PLUGGIT_GetAirTemperatureExhaust(integer $InstanceID)
```
Gibt die aktuelle Temperatur der Fortluft zurück.

---
```php
PLUGGIT_GetAirTemperatureExtract(integer $InstanceID)
```
Gibt die aktuelle Temperatur der Abluft zurück.

---
```php
PLUGGIT_GetAirTemperatureOutdoor(integer $InstanceID)
```
Gibt die aktuelle Temperatur der Frischluft zurück.

---
```php
PLUGGIT_GetAirTemperatureSupply(integer $InstanceID)
```
Gibt die aktuelle Temperatur der Zuluft zurück.

---
```php
PLUGGIT_GetAlarmState(integer $InstanceID)
```
Gibt den aktuellen Störungswert zurück.

---
```php
PLUGGIT_GetBypassState(integer $InstanceID)
```
Gibt den aktuellen Zustand des Bypasses zurück.

---
```php
PLUGGIT_GetCO2(integer $InstanceID)
```
Gibt den aktuellen Wert vom CO2 Sensor zurück.

---
```php
PLUGGIT_GetDevieSerialnumber(integer $InstanceID)
```
Gibt die Seriennummer vom Gerät zurück.

---
```php
PLUGGIT_GetDHCPStatus(integer $InstanceID)
```
Gibt zurück ob DHCP aktiviert wurde oder nicht.

---
```php
PLUGGIT_GetFan1RPM(integer $InstanceID)
```
Gibt die Drehzahl des ersten Lüfters zurück.

---
```php
PLUGGIT_GetFan2RPM(integer $InstanceID)
```
Gibt die Drehzahl des zweiten Lüfters zurück.

---
```php
PLUGGIT_GetFanSpeedLevel(integer $InstanceID)
```
Gibt die aktuelle Lüfterstufe zurück.

---
```php
PLUGGIT_GetFilterRemainingDays(integer $InstanceID)
```
Gibt die Restzeit für den Filter in Tagen zurück.

---
```php
PLUGGIT_FirmwareVersion(integer $InstanceID)
```
Gibt die Firmware Version vom Gerät zurück.

---
```php
PLUGGIT_GetHumidity(integer $InstanceID)
```
Gibt die aktuell gemessene Luftfeuchtigkeit im Lüftungsprozess zurück (optionaler Sensor als Zubehör erforderlich).

---
```php
PLUGGIT_GetMACAddress(integer $InstanceID)
```
Gibt die MAC-Adresse vom Gerät zurück.

---
```php
PLUGGIT_GetNetworkGateway(integer $InstanceID)
```
Gibt das konfigurierte Netzwerkgateway zurück.

---
```php
PLUGGIT_GetNetworkIPAddress(integer $InstanceID)
```
Gibt die konfigurierte IP-Adresse zurück.

---
```php
PLUGGIT_GetNetworkNetmask(integer $InstanceID)
```
Gibt die konfigurierte Netzwerkmaske zurück.

---
```php
PLUGGIT_GetOperatingState(integer $InstanceID)
```
Gibt den aktuellen Betriebsmodus zurück.

---
```php
PLUGGIT_GetPreheaterPower(integer $InstanceID)
```
Gibt die aktuell abgerufene Leistung vom Vorheizregister in Prozent zurück.

---
```php
PLUGGIT_GetVOC(integer $InstanceID)
```
Gibt den aktuellen Wert vom VOC-Sensor zurück (optional als Zubehör erhältlich).

---
```php
PLUGGIT_ResetFilterRemainingDays(integer $InstanceID)
```
Setzt die Restzeit vom Filter zurück.

---
```php
PLUGGIT_SetBypassState(integer $InstanceID, integer $Value)
```
Öffnet oder schließt den Bypass. Gültige Parameterwerte gemäß der Protokollbeschreibung von Pluggit.

---
```php
PLUGGIT_SetFanSpeedLevel(integer $InstanceID, integer $Value)
```
Setzt die Lüfterstufe. Gültige Parameterwerte gemäß der Protokollbeschreibung von Pluggit.

---
```php
PLUGGIT_SetOperatingState(integer $InstanceID, integer $Value)
```
Setzt den Betriebsmodus. Gültige Parameterwerte gemäß der Protokollbeschreibung von Pluggit.

---
```php
PLUGGIT_Update(integer $InstanceID)
```
Aktualisiert alle Variablen.