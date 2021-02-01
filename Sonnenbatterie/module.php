<?php
    // Klassendefinition
    class Sonnenbatterie extends IPSModule {
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            // Diese Zeile nicht löschen.
            parent::Create();

            //Update-Timer erstellen:
            $this->RegisterTimer("TimerGetData", 0, "SOB_GetData(".$this->InstanceID.");");

            $this->RegisterPropertyString("IPAddress","z. B. 192.168.1.25");
            $this->RegisterPropertyInteger("IntervalUpdateTimerSeconds", 0);
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();

            $ModulInfo = IPS_GetInstance($this->InstanceID);
            $ModulName = $ModulInfo['ModuleInfo']['ModuleName'];

            $IntervalTimerGetData = $this->ReadPropertyInteger("IntervalUpdateTimerSeconds") * 1000;
            $this->SendDebug($ModulName, "TimerIntervall: ".$IntervalTimerGetData, 0);
            $this->SetTimerInterval("TimerGetData", $IntervalTimerGetData);
            

            //Variablen anzeigen:
            $this->RegisterVariableInteger("USOC", "Ladezustand");
            $this->RegisterVariableInteger("Consumption", "Gesamtverbrauch");
            $this->RegisterVariableInteger("PAC_total", "Batterieverbrauch");
            $this->RegisterVariableInteger("GridFeedIn", "Stromnetzeinspeisung");
            $this->RegisterVariableInteger("Production", "Stromerzeugung");
            $this->RegisterVariableBoolean("BatteryCharging", "Batterie wird geladen");
            $this->RegisterVariableBoolean("BatteryDischarging", "Batterie wird entladen");
            $this->RegisterVariableBoolean("FlowConsumptionBattery", "Stromfluss aus Batterie");
            $this->RegisterVariableBoolean("FlowConsumptionGrid", "Stromfluss aus Stromnetz");
            $this->RegisterVariableBoolean("FlowConsumptionProduction", "Stromfluss aus Photovolataik");
            $this->RegisterVariableBoolean("FlowGridBattery", "Stromfluss aus Stromnetz in Batterie");
            $this->RegisterVariableBoolean("FlowProductionBattery", "Stromfluss aus PV in Batterie");
            $this->RegisterVariableBoolean("FlowProductionGrid", "Stromfluss aus PV ins Stromnetz");
            $this->RegisterVariableBoolean("FlowProductionBattery", "Stromfluss aus PV in Batterie");
            $this->RegisterVariableString("SystemStatus", "Systemstatus");
        }
 
        //Modulfunktionen:
        public function GetData() {

            //Get variables
            $SOBHostAddress = $this->ReadPropertyString("IPAddress");
            $ModulInfo = IPS_GetInstance($this->InstanceID);
            $ModulName = $ModulInfo['ModuleInfo']['ModuleName'];

            //Get Data
            $json = Sys_GetURLContent("http://".$SOBHostAddress.":8080/api/v1/status");
            if ($json == false) {
                $this->SetStatus(230);
                $this->SendDebug($ModulName, "Cannot get data from Sonnenbatterie!", 0);
                $this->SendDebug($ModulName, "http://".$SOBHostAddress.":8080/api/v1/status", 0);
            }
            else {
                $objData = json_decode($json);
                $this->SetStatus(102);
                $this->SendDebug($ModulName, "Successfully got data from Sonnenbatterie!", 0);
                $this->SendDebug($ModulName, $json, 0);
                
                SetValueInteger(IPS_GetObjectIDByIdent("Consumption", $this->InstanceID),intval($objData->Consumption_W));
                SetValueInteger(IPS_GetObjectIDByIdent("PAC_total", $this->InstanceID),intval($objData->Pac_total_W));
                SetValueInteger(IPS_GetObjectIDByIdent("GridFeedIn", $this->InstanceID),intval($objData->GridFeedIn_W));
                SetValueInteger(IPS_GetObjectIDByIdent("Production", $this->InstanceID),intval($objData->Production_W));
                SetValueInteger(IPS_GetObjectIDByIdent("USOC", $this->InstanceID),intval($objData->USOC));
                SetValueBoolean(IPS_GetObjectIDByIdent("BatteryCharging", $this->InstanceID),boolval($objData->BatteryCharging));
                SetValueBoolean(IPS_GetObjectIDByIdent("BatteryDischarging", $this->InstanceID),boolval($objData->BatteryDischarging));
                SetValueBoolean(IPS_GetObjectIDByIdent("FlowConsumptionBattery", $this->InstanceID),boolval($objData->FlowConsumptionBattery));
                SetValueBoolean(IPS_GetObjectIDByIdent("FlowConsumptionGrid", $this->InstanceID),boolval($objData->FlowConsumptionGrid));
                SetValueBoolean(IPS_GetObjectIDByIdent("FlowConsumptionProduction", $this->InstanceID),boolval($objData->FlowConsumptionProduction));
                SetValueBoolean(IPS_GetObjectIDByIdent("FlowGridBattery", $this->InstanceID),boolval($objData->FlowGridBattery));
                SetValueBoolean(IPS_GetObjectIDByIdent("FlowProductionBattery", $this->InstanceID),boolval($objData->FlowProductionBattery));
                SetValueBoolean(IPS_GetObjectIDByIdent("FlowProductionGrid", $this->InstanceID),boolval($objData->FlowProductionGrid));
                SetValueString(IPS_GetObjectIDByIdent("SystemStatus", $this->InstanceID),$objData->SystemStatus);

            }
        }
    }
    ?>