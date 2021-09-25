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
            $this->RegisterPropertyString("HostPort","8080");
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
            

            //region Register variables:
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
            //endregion
        }
 
        //region Modul Functions:
        public function GetData() {

            //region Get Basic Variables
            $SOBHostAddress = $this->ReadPropertyString("IPAddress");
            $SOBHostPort = $this->ReadPropertyString("HostPort");
            $ModulInfo = IPS_GetInstance($this->InstanceID);
            $ModulName = $ModulInfo['ModuleInfo']['ModuleName'];
            //endregion

            //region Manage Data
            $json = Sys_GetURLContent("http://".$SOBHostAddress.":".$SOBHostPort."/api/v1/status");
            if ($json == false) {
                $this->SetStatus(230);
                $this->SendDebug($ModulName, "Cannot get data from Sonnenbatterie!", 0);
                $this->SendDebug($ModulName, "http://".$SOBHostAddress.":".$SOBHostPort."/api/v1/status", 0);
            }
            else {
                $objData = json_decode($json);
                $this->SetStatus(102);
                $this->SendDebug($ModulName, "Successfully got data from Sonnenbatterie!", 0);
                $this->SendDebug($ModulName, $json, 0);
                
                //region Get current values
                $Consumption_W_Id = IPS_GetObjectIDByIdent("Consumption", $this->InstanceID);
                $Consumption_W_CurrentVal = GetValueInteger($Consumption_W_Id);
                $Pac_total_W_Id = IPS_GetObjectIDByIdent("PAC_total", $this->InstanceID);
                $Pac_total_W_CurrentVal = GetValueInteger($Pac_total_W_Id);
                $GridFeedIn_W_Id = IPS_GetObjectIDByIdent("GridFeedIn", $this->InstanceID);
                $GridFeedIn_W_CurrentVal = GetValueInteger($GridFeedIn_W_Id);
                $Production_W_Id = IPS_GetObjectIDByIdent("Production", $this->InstanceID);
                $Production_W_CurrentVal = GetValueInteger($Production_W_Id);
                $USOC_Id = IPS_GetObjectIDByIdent("USOC", $this->InstanceID);
                $USOC_CurrentVal = GetValueInteger($USOC_Id);
                $BatteryCharging_Id = IPS_GetObjectIDByIdent("BatteryCharging", $this->InstanceID);
                $BatteryCharging_CurrentVal = GetValueBoolean($BatteryCharging_Id);
                $BatteryDischarging_Id = IPS_GetObjectIDByIdent("BatteryDischarging", $this->InstanceID);
                $BatteryDischarging_CurrentVal = GetValueBoolean($BatteryDischarging_Id);
                $FlowConsumptionBattery_Id = IPS_GetObjectIDByIdent("FlowConsumptionBattery", $this->InstanceID);
                $FlowConsumptionBattery_CurrentVal = GetValueBoolean($FlowConsumptionBattery_Id);
                $FlowConsumptionGrid_Id = IPS_GetObjectIDByIdent("FlowConsumptionGrid", $this->InstanceID);
                $FlowConsumptionGrid_CurrentVal = GetValueBoolean($FlowConsumptionGrid_Id);
                $FlowConsumptionProduction_Id = IPS_GetObjectIDByIdent("FlowConsumptionProduction", $this->InstanceID);
                $FlowConsumptionProduction_CurrentVal = GetValueBoolean($FlowConsumptionProduction_Id);
                $FlowGridBattery_Id = IPS_GetObjectIDByIdent("FlowGridBattery", $this->InstanceID);
                $FlowGridBattery_CurrentVal = GetValueBoolean($FlowGridBattery_Id);
                $FlowProductionBattery_Id = IPS_GetObjectIDByIdent("FlowProductionBattery", $this->InstanceID);
                $FlowProductionBattery_CurrentVal = GetValueBoolean($FlowProductionBattery_Id);
                $FlowProductionGrid_Id = IPS_GetObjectIDByIdent("FlowProductionGrid", $this->InstanceID);
                $FlowProductionGrid_CurrentVal = GetValueBoolean($FlowProductionGrid_Id);
                $SystemStatus_Id = IPS_GetObjectIDByIdent("SystemStatus", $this->InstanceID);
                $SystemStatus_CurrentVal = GetValueString($SystemStatus_Id);
                //endregion

                //region Check if value is different
                If ($Consumption_W_CurrentVal <> $objData->Consumption_W) {
                    SetValueInteger($Consumption_W_Id,intval($objData->Consumption_W));
                }
                If ($PACTotal_CurrentVal <> $objData->Pac_total_W) {
                    SetValueInteger($PACTotal_Id,intval($objData->Pac_total_W));
                }
                If ($GridFeedIn_W_CurrentVal <> $objData->GridFeedIn_W) {
                    SetValueInteger($GridFeedIn_W_Id,intval($objData->GridFeedIn_W));
                }
                If ($Production_W_CurrentVal <> $objData->Production_W) {
                    SetValueInteger($Production_W_Id,intval($objData->Production_W));
                }
                If ($USOC_CurrentVal <> $objData->USOC) {
                    SetValueInteger($USOC_Id,intval($objData->USOC));
                }
                If ($BatteryCharging_CurrentVal <> $objData->BatteryCharging) {
                    SetValueBoolean($BatteryCharging_Id,intval($objData->BatteryCharging));
                }
                If ($BatteryDischarging_CurrentVal <> $objData->BatteryDischarging) {
                    SetValueBoolean($BatteryDischarging_Id,intval($objData->BatteryDischarging));
                }
                If ($FlowConsumptionBattery_CurrentVal <> $objData->FlowConsumptionBattery) {
                    SetValueBoolean($FlowConsumptionBattery_Id,intval($objData->FlowConsumptionBattery));
                }
                If ($FlowConsumptionGrid_CurrentVal <> $objData->FlowConsumptionGrid) {
                    SetValueBoolean($FlowConsumptionGrid_Id,intval($objData->FlowConsumptionGrid));
                }
                If ($FlowConsumptionProduction_CurrentVal <> $objData->FlowConsumptionProduction) {
                    SetValueBoolean($FlowConsumptionProduction_Id,intval($objData->FlowConsumptionProduction));
                }
                If ($FlowGridBattery_CurrentVal <> $objData->FlowGridBattery) {
                    SetValueBoolean($FlowGridBattery_Id,intval($objData->FlowGridBattery));
                }
                If ($FlowProductionBattery_CurrentVal <> $objData->FlowProductionBattery) {
                    SetValueBoolean($FlowProductionBattery_Id,intval($objData->FlowProductionBattery));
                }
                If ($FlowProductionGrid_CurrentVal <> $objData->FlowProductionGrid) {
                    SetValueBoolean($FlowProductionGrid_Id,intval($objData->FlowProductionGrid));
                }
                If ($SystemStatus_CurrentVal <> $objData->SystemStatus) {
                    SetValueString($SystemStatus_Id,intval($objData->SystemStatus));
                }
                //endregion
                
                //region Old commands
                /*
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
                */
                //endregion
            }
            //endregion
        }
        //endregion
    }
    ?>