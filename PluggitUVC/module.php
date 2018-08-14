<?

require_once(__dir__.'/../libs/Phpmodbus/ModbusMaster.php');

class Pluggit extends IPSModule
{
    var $Modbus_Properties = array(
        array("name" => "Seriennummer",                         "ident" => "prmSystemSerialNum",            "varType" => 3,     "varProfile" => null,                           "varHasAction" => false),
        array("name" => "Firmware Version",                     "ident" => "prmFWVersion",                  "varType" => 3,     "varProfile" => null,                           "varHasAction" => false),
        array("name" => "DHCP aktiviert",                       "ident" => "prmDHCPEN",                     "varType" => 0,     "varProfile" => null,                           "varHasAction" => false),
        array("name" => "IP-Adresse",                           "ident" => "prmCurrentIPAddress",           "varType" => 3,     "varProfile" => null,                           "varHasAction" => false),
        array("name" => "Netzmaske",                            "ident" => "prmCurrentIPMask",              "varType" => 3,     "varProfile" => null,                           "varHasAction" => false),
        array("name" => "Gateway",                              "ident" => "prmCurrentIPGateway",           "varType" => 3,     "varProfile" => null,                           "varHasAction" => false),
        array("name" => "MAC Adresse",                          "ident" => "prmMACAddr",                    "varType" => 3,     "varProfile" => null,                           "varHasAction" => false),
        array("name" => "Temperatur Außenluft",                 "ident" => "prmRamIdxT1",                   "varType" => 2,     "varProfile" => "~Temperature",                 "varHasAction" => false),
        array("name" => "Temperatur Zuluft",                    "ident" => "prmRamIdxT2",                   "varType" => 2,     "varProfile" => "~Temperature",                 "varHasAction" => false),
        array("name" => "Temperatur Abluft",                    "ident" => "prmRamIdxT3",                   "varType" => 2,     "varProfile" => "~Temperature",                 "varHasAction" => false),
        array("name" => "Temperatur Fortluft",                  "ident" => "prmRamIdxT4",                   "varType" => 2,     "varProfile" => "~Temperature",                 "varHasAction" => false),
        array("name" => "Temperatur Fernbedienung",             "ident" => "prmRamIdxT5",                   "varType" => 2,     "varProfile" => "~Temperature",                 "varHasAction" => false),
        array("name" => "Luftfeuchte",                          "ident" => "prmRamIdxRh3Corrected",         "varType" => 1,     "varProfile" => "~Humidity",                    "varHasAction" => false),
        array("name" => "Lüfter Stufe",                         "ident" => "prmRomIdxSpeedLevel",           "varType" => 1,     "varProfile" => "PLUGGIT.FANSpeedLevels",       "varHasAction" => true),
        array("name" => "Lüfter1 Umdrehungsgeschwindigkeit",    "ident" => "prmHALTaho1",                   "varType" => 2,     "varProfile" => "PLUGGIT.FANSpeedRPM",          "varHasAction" => false),
        array("name" => "Lüfter2 Umdrehungsgeschwindigkeit",    "ident" => "prmHALTaho2",                   "varType" => 2,     "varProfile" => "PLUGGIT.FANSpeedRPM",          "varHasAction" => false),
        array("name" => "Filter Restzeit",                      "ident" => "prmFilterRemainingTime",        "varType" => 1,     "varProfile" => "PLUGGIT.FilterRemainingTime",  "varHasAction" => true),
        array("name" => "Betriebsmodus",                        "ident" => "prmRamIdxUnitMode",             "varType" => 1,     "varProfile" => "PLUGGIT.UnitMode",             "varHasAction" => true),
        array("name" => "Bypass Status",                        "ident" => "prmRamIdxBypassActualState",    "varType" => 1,     "varProfile" => "PLUGGIT.BypassState",          "varHasAction" => true),
        array("name" => "VOC",                                  "ident" => "prmVOC",                        "varType" => 2,     "varProfile" => null,                           "varHasAction" => false),
        array("name" => "CO2",                                  "ident" => "prmHACCO2Val",                  "varType" => 1,     "varProfile" => "Occurrence.CO2",               "varHasAction" => false),
        array("name" => "Vorheizregister Leistung",             "ident" => "prmPreheaterDutyCycle",         "varType" => 1,     "varProfile" => "~Intensity.100",               "varHasAction" => false),
        array("name" => "Störung",                              "ident" => "prmLastActiveAlarm",            "varType" => 1,     "varProfile" => "PLUGGIT.AlarmState",           "varHasAction" => false)
    );
        
    public function Create()
    {
        parent::Create();

        $this->CreateVariableProfiles();

        $this->RegisterPropertyString("IP", "");
        $this->RegisterPropertyInteger("Poller", 3);
        $this->RegisterPropertyInteger("ResetFanSpeedLevel", 1);
        $this->RegisterPropertyInteger("AlarmArchive", 1);

        $this->RegisterTimer("Poller", 0, "PLUG_Update(\$_IPS['TARGET']);");
        $this->RegisterTimer("ResetFanSpeedLevel", 0, "PLUG_SetFanSpeedLevel(\$_IPS['TARGET'], 3);");
    }
    
    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // Get Handler for Archive
        $instances = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}'); 
        $archive_handler = $instances[0];

        // create variables
        foreach ($this->Modbus_Properties as $property) {
            $var = @IPS_GetObjectIDByIdent($property['ident'], $this->InstanceID);
            if(!$var) {
                $var = IPS_CreateVariable($property['varType']);
                IPS_SetIdent($var, $property['ident']);
                IPS_SetName($var, $property['name']);
                IPS_SetParent($var, $this->InstanceID);
            }
            if($property['varProfile'] != null) {
                if(IPS_VariableProfileExists($property['varProfile']))
                    IPS_SetVariableCustomProfile($var, $property['varProfile']);
            }
            if($property['varHasAction'])
                $this->EnableAction($property['ident']);
            else
                @$this->DisableAction($property['ident']);
        }

        $var = @IPS_GetObjectIDByIdent("AlarmArchive", $this->InstanceID);
        if($this->ReadPropertyInteger("AlarmArchive") == 1) {
            if(!$var) {
                $var = IPS_CreateVariable(3);
                IPS_SetIdent($var, "AlarmArchive");
                IPS_SetName($var, "Störungsprotokoll");
                IPS_SetParent($var, $this->InstanceID);

                AC_SetLoggingStatus($archive_handler, $var, true);
                AC_SetAggregationType($archive_handler, $var, 0);
                IPS_ApplyChanges($archive_handler);
            }
        } else {
            AC_SetLoggingStatus($archive_handler, $var, false);
            IPS_ApplyChanges($archive_handler);
            AC_DeleteVariableData($archive_handler, IPS_GetObjectIDByIdent("prmLastActiveAlarm", $this->InstanceID), 0, 0);

            IPS_DeleteVariable($var);
        }

        $this->SetTimerInterval("Poller", $this->ReadPropertyInteger("Poller")*1000);

        $this->SetStatus(102);
    }

    public function RequestAction($Ident, $Value) 
    { 
        switch ($Ident) 
        { 
            case 'prmFilterRemainingTime':
                if($Value == 10) { // reset button
                    $this->ResetFilterRemainingDays();
                }
                break;
            case 'prmRomIdxSpeedLevel':
                $this->SetFanSpeedLevel($Value);
                break;
            case 'prmRamIdxUnitMode':
                $this->SetOperatingState($Value);
                break;
            case 'prmRamIdxBypassActualState':
                if($Value == 128 || $Value == 32896) {
                    $this->SetBypassState($Value);
                }
                break;
            default:
                break; 
        }
    }

    public function Update() {
        foreach($this->Modbus_Properties as $property) {
            $var = @IPS_GetObjectIDByIdent($property['ident'], $this->InstanceID);
            
            $value = null;


            switch ($property['ident']) {
                case 'prmCurrentIPAddress':
                    $value = $this->GetNetworkIPAddress();
                    break;
                case 'prmMACAddr':
                    $value = $this->GetMACAddress();
                    break;
                case 'prmSystemSerialNum':
                    $value = $this->GetDeviceSerialnumber();
                    break;
                case 'prmFWVersion':
                    $value = $this->GetFirmwareVersion();
                    break;  
                case 'prmCurrentIPMask':
                    $value = $this->GetNetworkNetmask();
                    break;   
                case 'prmCurrentIPGateway':
                    $value = $this->GetNetworkGateway();
                    break; 
                case 'prmDHCPEN':
                    $value = $this->GetDHCPStatus();
                    break;
                case 'prmRamIdxT1':
                    $value = $this->GetAirTemperatureOutdoor();
                    break;
                case 'prmRamIdxT2':
                    $value = $this->GetAirTemperatureSupply();
                    break;
                case 'prmRamIdxT3':
                    $value = $this->GetAirTemperatureExtract();
                    break;
                case 'prmRamIdxT4':
                    $value = $this->GetAirTemperatureExhaust();
                    break;
                case 'prmRamIdxT5':
                    $value = $this->GetTemperatureRemotecontrol();
                    break;
                case 'prmRamIdxRh3Corrected':
                    $value = $this->GetHumidity();
                    break;
                case 'prmRomIdxSpeedLevel':
                    $value = $this->GetFanSpeedLevel();
                    break;
                case 'prmHALTaho1':
                    $value = $this->GetFan1RPM();
                    break;
                case 'prmHALTaho2':
                    $value = $this->GetFan2RPM();
                    break;
                case 'prmFilterRemainingTime':
                    $value = $this->GetFilterRemainingDays();
                    if($value > 100)
                        $color = 0x00d900;
                    elseif($value < 100 && $value > 30)
                        $color = 0xef9418;
                    else
                        $color = 0xFF0000;

                    IPS_SetVariableProfileAssociation("PLUGGIT.FilterRemainingTime", 1, $value." Tage", "", $color);
                    $value = 1;
                    break;
                case 'prmRamIdxBypassActualState':
                    $value = $this->GetBypassState();

                    switch ($value) {
                        case 0:
                            $status_label = "Aus";
                            $status_color = 0x8a94a1;
                            $switch_label = "Bypass einschalten";
                            $switch_color = 0x74A3FA;
                            $switch_value = 128;
                            break;
                        case 1:
                            $status_label = "Bypass arbeitet";
                            $status_color = 0xdfce00;
                            $switch_label = "---";
                            $switch_label = "---";
                            $switch_color = null;
                            $switch_value = -1;
                            break;
                        case 32:
                            $status_label = "Bypass wird ausgeschaltet";
                            $status_color = 0xdfce00;
                            $switch_label = "---";
                            $switch_color = null;
                            $switch_value = -1;
                            break;
                        case 64:
                            $status_label = "Bypass wird eingeschaltet";
                            $status_color = 0xdfce00;
                            $switch_label = "---";
                            $switch_color = null;
                            $switch_value = -1;
                            break;
                        case 255:
                            $status_label = "Ein";
                            $status_color = 0x74A3FA;
                            $switch_label = "Bypass ausschalten";
                            $switch_color = 0x8a94a1;
                            $switch_value = 32896;
                            break;
                        default:
                            $status_label = "---";
                            $status_color = null;
                            $switch_label = "---";
                            $switch_color = null;
                            $switch_value = -1;
                            break;
                    }

                    $value = -1;

                    $profileName = "PLUGGIT.BypassState";
                    IPS_DeleteVariableProfile($profileName);
                    IPS_CreateVariableProfile($profileName, 1);
                    IPS_SetVariableProfileAssociation($profileName, $value, $status_label, "", $status_color);
                    if($switch_value > 0)
                        IPS_SetVariableProfileAssociation($profileName, $switch_value, $switch_label, "", $switch_color);

                    IPS_SetVariableCustomProfile($var, $profileName);
                    break;
                case 'prmVOC':
                    $value = $this->GetVOC();
                    break;
                case 'prmHACCO2Val':
                    $value = $this->GetCO2();
                    break;
                case 'prmPreheaterDutyCycle':
                    $value = $this->GetPreheaterPower();
                    break;
                case 'prmRamIdxUnitMode':
                    $value = $this->GetOperatingState();
                    break;
                case 'prmLastActiveAlarm':
                    $value = $this->GetAlarmState();

                    if($this->ReadPropertyInteger("AlarmArchive") == 1) {
                        $trigger = "";
                        $alarmArchive = IPS_GetObjectIDByIdent("AlarmArchive", $this->InstanceID);
                        if($value != GetValue($var)) {
                            $AlarmProfileAssociations = IPS_GetVariableProfile("PLUGGIT.AlarmState")["Associations"];
                            foreach($AlarmProfileAssociations as $association) {
                                if($association['Value'] == $value)
                                    $trigger = $association['Name'];
                            }

                            if($value != 0)
                                $trigger = "Störung: '".$trigger."'.";
                            else
                                $trigger = "Störung behoben.";

                            SetValue($alarmArchive, $trigger);
                        }
                    }
                    break;
                default:
                    break;
            }

            if($value != GetValue($var))
                SetValue($var, $value);
        }
    }

    public function GetNetworkIPAddress() {
        $address = array(40030, 40029);
        $seperator = ".";

        $return = "";

        $i = 1;
        foreach($address as $a) {
            $result = $this->SendReadRequest($a-40001, 1);
            if($result != false) {
                $return .= implode($seperator, $result);

                if($i < count($address))
                    $return .= $seperator;

                $i++;
            }
            else
                return false;

        }

        return $return;
    }

    public function GetNetworkNetmask() {
        $address = array(40034, 40033);
        $seperator = ".";

        $return = "";

        $i = 1;
        foreach($address as $a) {
            $result = $this->SendReadRequest($a-40001, 1);
            if($result != false) {
                $return .= implode($seperator, $result);

                if($i < count($address))
                    $return .= $seperator;

                $i++;
            }
            else
                return false;
        }

        return $return;
    }

    public function GetNetworkGateway() {
        $address = array(40038, 40037);
        $seperator = ".";

        $return = "";

        $i = 1;
        foreach($address as $a) {
            $result = $this->SendReadRequest($a-40001, 1);
            if($result != false) {
                $return .= implode($seperator, $result);

                if($i < count($address))
                    $return .= $seperator;

                $i++;
            } 
            else
                return false;
        }

        return $return;
    }

    public function GetMACAddress() {
        $address = array(40041, 40044, 40043);
        $seperator = ":";

        $return = "";

        foreach($address as $a) {
            $result = $this->SendReadRequest($a-40001, 1);
            if($result != false) {
                foreach($result as $r) {
                    $return .= str_pad(dechex($r), 2, "0", STR_PAD_LEFT);

                    $return .= $seperator;
                }
            } 
            else
                $return = false;
        }

        return substr($return, 0, -1);
    }

    public function GetDeviceSerialnumber() {
        $address = array(40008, 40007, 40006, 40005);

        $return = "";

        foreach($address as $a) {
            $result = $this->SendReadRequest($a-40001, 1);
            if($result != false) {
                foreach($result as $r) {
                    $return .= str_pad(decbin($r), 8, "0", STR_PAD_LEFT);
                }
            } 
            else
                return false;
        }

        $return = bindec($return);

        return $return;
    }

    public function GetFirmwareVersion() {
        $address = array(40026, 40025);
        $seperator = ".";

        $return = "";

        foreach($address as $a) {
            $result = $this->SendReadRequest($a-40001, 1);
            if($result != false) {
                foreach($result as $r) {
                    if($r != "0")
                        $return .= $r.$seperator;
                }
            } 
            else
                return false;
        }

        return substr($return, 0, -1);
    }

    public function GetDHCPStatus() {
        $address = 40027;

        $result = (bool)$this->GetSingleValue($address);

        return $result;
    }

    public function GetAirTemperatureOutdoor() {
        $address = 40133;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=round(PhpType::bytes2float($bytes), 1);
        }

        return $output;
    }

    public function GetAirTemperatureSupply() {
        $address = 40135;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=round(PhpType::bytes2float($bytes), 1);
        }

        return $output;
    }

    public function GetAirTemperatureExtract() {
        $address = 40137;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=round(PhpType::bytes2float($bytes), 1);
        }

        return $output;
    }

    public function GetAirTemperatureExhaust() {
        $address = 40139;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=round(PhpType::bytes2float($bytes), 1);
        }

        return $output;
    }

    public function GetTemperatureRemotecontrol() {
        $address = 40141;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=round(PhpType::bytes2float($bytes), 1);
        }

        return $output;
    }

    public function GetHumidity() {
        $address = 40197;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=round(PhpType::bytes2float($bytes), 2);
        }

        return $output;
    }

    public function GetFanSpeedLevel() {
        $address = 40325;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=round(PhpType::bytes2signedInt($bytes), 1);
        }

        return $output;
    }

    public function SetFanSpeedLevel(?int $value) {
        $this->SetTimerInterval("ResetFanSpeedLevel", 0);

        if($value >= 0 && $value <= 4) {
            if($this->GetOperatingState() != 1)
                $this->SetOperatingState(1);

            $address = 40325;
            $data = array($value);
            $dataTypes = array("DINT");
            $this->SendWriteRequest(($address-40001), $data, $dataTypes);

            if($value == 0 || $value == 4) {
                $this->SetTimerInterval("ResetFanSpeedLevel", $this->ReadPropertyInteger("ResetFanSpeedLevel")*3600000);
            }
        }
    }

    public function GetFan1RPM() {
        $address = 40101;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=round(PhpType::bytes2float($bytes), 0);
        }

        return $output;
    }

    public function GetFan2RPM() {
        $address = 40103;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=round(PhpType::bytes2float($bytes), 0);
        }

        return $output;
    }

    public function GetFilterRemainingDays() {
        $address = 40555;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=PhpType::bytes2signedInt($bytes);
        }

        return $output;
    }

    public function ResetFilterRemainingDays() {
        $address = 40559;
        $data = array(1);
        $dataTypes = array("DINT");
        $this->SendWriteRequest(($address-40001), $data, $dataTypes);
    }

    public function GetBypassState() {
        $address = 40199;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=PhpType::bytes2signedInt($bytes);
        }

        return $output;
    }

    public function SetBypassState(?int $value) {
        if($value == 128 || $value == 32896) {
            $this->SetOperatingState($value);
        }
    }

    public function GetOperatingState() {
        $address = 40473;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=PhpType::bytes2signedInt($bytes);
        }

        return $output;
    }

    public function SetOperatingState(?int $value) {
        $address = 40169;

        switch ($value) {
            case 1: $datax = 4; break;              // Manual
            case 16: $datax = 32; break;            // Night
            case 2: $datax = 2; break;              // Demand/Bedarfsgesteuert
            case 3: $datax = 8; break;              // Week
            case 5: $datax = 16; break;             // StartAway
            case 50: $datax = 32784; break;         // EndAway
            case 6: $datax = 2048; break;           // StartSummer
            case 60: $datax = 34816; break;         // EndSummer
            case 9: $datax = 64; break;             // StartFirePlace
            case 90: $datax = 32832; break;         // EndFirePlace
            case 128: $datax = $value; break;       // OpenManualBypass
            case 32896: $datax = $value; break;     // OpenManualBypass Deaktiviert
            default: $datax = null; break;          // default
        }

        if($datax != null) {
            $data = array($datax);
            $dataTypes = array("DINT");
            $this->SendWriteRequest(($address-40001), $data, $dataTypes);
        }
    }

    public function GetAlarmState() {
        $address = 40517;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=PhpType::bytes2signedInt($bytes);
        }

        return $output;   
    }

    public function GetAlarmStateAsText() {
        $return = "";

        $alarmState = $this->GetAlarmState();
        $AlarmProfileAssociations = IPS_GetVariableProfile("PLUGGIT.AlarmState")["Associations"];
        foreach($AlarmProfileAssociations as $association) {
            if($association['Value'] == $alarmState)
                $return = $association['Name'];
        }

        return $return;   
    }

    public function GetVOC() {
        $address = 40431;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=round(PhpType::bytes2float($bytes), 2);
        }

        return $output;
    }

    public function GetCO2() {
        $address = 40575;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=PhpType::bytes2signedInt($bytes);
        }

        return $output;
    }

    public function GetPreheaterPower() {
        $address = 40161;
        $result = $this->SendReadRequest(($address-40001), 2);

        $output = null;

        if($result != false) {
            $values = array_chunk($result, 4);
            foreach($values as $bytes)
                $output=PhpType::bytes2signedInt($bytes);
        }

        return $output;
    }

    // public function GetInstalledComponents() {
    //     $address = array(40004);

    //     $return = array();

    //     $components_bin = "";

    //     foreach($address as $a) {
    //         $result = $this->SendReadRequest($a-40001, 2);
    //         if($result != false) {
    //             $values = array_chunk($result, 4);
    //             foreach($values as $r) {
    //                 $components_bin .= str_pad(decbin($r), 8, "0", STR_PAD_LEFT);
    //             }
    //         } 
    //         else
    //             return false;
    //     }

    //     $components_bin = str_split($components_bin);

    //     $i = 1;
    //     foreach ($components_bin as $component) {   
    //         $i=$i*2;
    //     }

    //     $return = $components_bin;

    //     return $return;
    // }


    // PRIVATE FUNCTIONS
    protected function GetSingleValue($addr) {
        $return = false;

        $result = $this->SendReadRequest(($addr-40001), 2);
        if($result != false)
            $return = $result;

        return $return;
    }


    protected function SendReadRequest($address, $length) {
        $return = false;

        if(strlen(trim($this->ReadPropertyString("IP"))) > 0) {
            $modbus = new ModbusMaster($this->ReadPropertyString("IP"), "TCP");            

            try {
                $return = $modbus->readMultipleRegisters(1, $address, $length);
            } catch (Exception $e) {
                // $this->ModuleLogMessage("Modbus fehler: ".$e);
            }
        }  

        return $return;
    }

    protected function SendWriteRequest($address, $data, $dataTypes) {
        $return = false;

        if(strlen(trim($this->ReadPropertyString("IP"))) > 0) {
            $modbus = new ModbusMaster($this->ReadPropertyString("IP"), "TCP");            

            try {
                $return = $modbus->writeMultipleRegister(1, $address, $data, $dataTypes);
            } catch (Exception $e) {
                // $this->ModuleLogMessage("Modbus fehler: ".$e);
            }
        }  

        return $return;
    }

    // HELPER FUNCTIONS
    protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 1)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
        
    }

    protected function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }

    protected function RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 0);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 0)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);  
    }
    
    protected function RegisterProfileBooleanEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }

    protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
    }

    protected function GetParentForInstance($id)
    {
        $instance = IPS_GetInstance($id);
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
    }

    protected function CreateVariableProfiles() {
        // Start create profiles
        $profileName = "PLUGGIT.FANSpeedRPM";
        if(!IPS_VariableProfileExists($profileName)) {
            IPS_CreateVariableProfile($profileName, 2);
        }
        IPS_SetVariableProfileText($profileName, "", " U/min");

        $profileName = "PLUGGIT.FilterRemainingTime";
        if(!IPS_VariableProfileExists($profileName)) {
            IPS_CreateVariableProfile($profileName, 1);
        }
        IPS_SetVariableProfileAssociation($profileName, 1, "0 Tage", "", null);
        IPS_SetVariableProfileAssociation($profileName, 10, "Reset", "", 0x74A3FA);

        $profileName = "PLUGGIT.FANSpeedLevels";
        if(!IPS_VariableProfileExists($profileName)) {
            IPS_CreateVariableProfile($profileName, 1);
        }
        IPS_SetVariableProfileAssociation($profileName, 0, "Aus", "", null);
        IPS_SetVariableProfileAssociation($profileName, 1, "Stufe 1", "", 0x8a94a1);
        IPS_SetVariableProfileAssociation($profileName, 2, "Stufe 2", "", 0x00d900);
        IPS_SetVariableProfileAssociation($profileName, 3, "Stufe 3", "", 0x00d900);
        IPS_SetVariableProfileAssociation($profileName, 4, "Stufe 4", "", 0xef9418);

        $profileName = "PLUGGIT.BypassState";
        if(!IPS_VariableProfileExists($profileName)) {
            IPS_CreateVariableProfile($profileName, 1);
        }

        $profileName = "PLUGGIT.UnitMode";
        if(!IPS_VariableProfileExists($profileName)) {
            IPS_CreateVariableProfile($profileName, 1);
        }
        IPS_SetVariableProfileAssociation($profileName, 0,      "Standby", "", null);
        IPS_SetVariableProfileAssociation($profileName, 1,      "Manuell*", "", 0xdfce00);
        IPS_SetVariableProfileAssociation($profileName, 2,      "Bedarfsgesteuert*", "", null);
        IPS_SetVariableProfileAssociation($profileName, 3,      "Wochenplan*", "", null);
        IPS_SetVariableProfileAssociation($profileName, 4,      "Servo-flow", "", null);
        IPS_SetVariableProfileAssociation($profileName, 5,      "Abwesenheit*", "", null);
        IPS_SetVariableProfileAssociation($profileName, 6,      "Sommer*", "", null);
        IPS_SetVariableProfileAssociation($profileName, 7,      "DI Override", "", null);
        IPS_SetVariableProfileAssociation($profileName, 8,      "Hygrostat Override", "", null);
        IPS_SetVariableProfileAssociation($profileName, 9,      "Feuerstätte*", "", null);
        IPS_SetVariableProfileAssociation($profileName, 10,     "Einrichtung", "", 0xef9418);
        IPS_SetVariableProfileAssociation($profileName, 11,     "Failsafe", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 12,     "Failsafe2", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 13,     "Fail Off", "", null);
        IPS_SetVariableProfileAssociation($profileName, 14,     "Abtauen Ende", "", null);
        IPS_SetVariableProfileAssociation($profileName, 15,     "Abtauen", "", null);
        IPS_SetVariableProfileAssociation($profileName, 16,     "Nachtabsenkung*", "", 0x74A3FA);

        $profileName = "PLUGGIT.AlarmState";
        if(!IPS_VariableProfileExists($profileName)) {
            IPS_CreateVariableProfile($profileName, 1);
        }
        IPS_SetVariableProfileAssociation($profileName, 0,      "keine", "", 0x00d900);
        IPS_SetVariableProfileAssociation($profileName, 1,      "Abluft Lüfter", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 2,      "Zuluft Lüfter", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 3,      "Bypass Status", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 4,      "Temperatur Außenluft", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 5,      "Temperatur Zuluft", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 6,      "Temperatur Abluft", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 7,      "Temperatur Fortluft", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 8,      "Temperatur Raumluft (Fernbedienung)", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 9,      "Luftfeuchte", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 10,     "Outdoor13", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 11,     "Supply5", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 12,     "Feuer", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 13,     "Kommunikation", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 14,     "Feuer Thermostat", "", 0xFF0000);
        IPS_SetVariableProfileAssociation($profileName, 15,     "Wasserstand", "", 0xFF0000);
    }

    protected function ModuleLogMessage($message) {
        IPS_LogMessage(IPS_GetObject($this->InstanceID)['ObjectName'], $message);
    }
}

?>
