<?
    if (!defined('IPS_BASE')) {
        define("IPS_BASE", 10000);
    }
    if (!defined('VM_UPDATE')) {
        define("VM_UPDATE", IPS_BASE + 603);
    }
    // Klassendefinition
    class AlarmSystem extends IPSModule {

        public function Destroy() {
            //Never delete this line!
            parent::Destroy();
        }

        public function Create()
		{
			//Never delete this line!
            parent::Create();
            
            $this->RegisterPropertyString("SensorsINT", "[]");
			$this->RegisterPropertyString("SensorsEXT", "[]");
			$this->RegisterPropertyString("SensorsSMOKE", "[]");
            $this->RegisterPropertyString("Targets", "[]");

			$this->RegisterPropertyInteger("PushInstanceID", 0);
			$this->RegisterPropertyInteger("AlarmDelayScriptID", 0);
			$this->RegisterPropertyInteger("AlarmDelaySeconds", 60);
		}

        public function ApplyChanges() {

            //Never delete this line!
            parent::ApplyChanges();

            //$ModulInfo = IPS_GetInstance($this->InstanceID);
            //$ModulName = $ModulInfo['ModuleInfo']['ModuleName'];

            //$this->SendDebug("AlarmSystem", "Translation: ". $this->Translate("Active External"), 0);

            $this->RegisterVariableBoolean("ActiveEXT", $this->Translate("Active External"), "~Switch", 0 );
            $this->EnableAction("ActiveEXT");
            $this->RegisterVariableBoolean("ActiveINT", $this->Translate("Active Internal"), "~Switch", 0 );
			$this->EnableAction("ActiveINT");
			$this->RegisterVariableBoolean("ActiveSMOKE", $this->Translate("Active Smoke"), "~Switch", 0 );
            $this->EnableAction("ActiveSMOKE");
			$AlertVarID = $this->RegisterVariableBoolean("Alert", $this->Translate("Alert"), "~Alert", 0);
			$this->EnableAction("Alert");
            //Disable default action for alert variable, because this shouldn't be switch on web frontend
            IPS_SetVariableCustomAction($AlertVarID, 1);
            $this->RegisterVariableString("LastTriggeredObject", $this->Translate("Last Trigger"));

			$sensorsINT = json_decode($this->ReadPropertyString("SensorsINT"));
			foreach ($sensorsINT as $sensor) {
				$this->RegisterMessage($sensor->ID, VM_UPDATE);	
            }
            $sensorsEXT = json_decode($this->ReadPropertyString("SensorsEXT"));
			foreach ($sensorsEXT as $sensor) {
				$this->RegisterMessage($sensor->ID, VM_UPDATE);	
			}
			$sensorsSMOKE = json_decode($this->ReadPropertyString("SensorsSMOKE"));
			foreach ($sensorsSMOKE as $sensor) {
				$this->SendDebug("SmokerSensor", $sensor->ID, 0);
				$this->RegisterMessage($sensor->ID, VM_UPDATE);	
			}
        }

        public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
			
            if((GetValue($this->GetIDForIdent("ActiveINT"))) && (!GetValue($this->GetIDForIdent("ActiveEXT")))) {
				$sensorsINT = json_decode($this->ReadPropertyString("SensorsINT"));
                foreach ($sensorsINT as $sensor) {
                    if($sensor->ID == $SenderID) {
                        $this->TriggerAlert($sensor->ID, GetValue($sensor->ID), true);
                        return;
                    }
                }
            }
            elseif(GetValue($this->GetIDForIdent("ActiveEXT"))) {
                $sensorsINT = json_decode($this->ReadPropertyString("SensorsINT"));
                foreach ($sensorsINT as $sensor) {
                    if($sensor->ID == $SenderID) {
                        $this->TriggerAlert($sensor->ID, GetValue($sensor->ID), true);
                        return;
                    }
                }
                $sensorsEXT = json_decode($this->ReadPropertyString("SensorsEXT"));
                foreach ($sensorsEXT as $sensor) {
                    if($sensor->ID == $SenderID) {
                        $this->TriggerAlert($sensor->ID, GetValue($sensor->ID), true);
                        return;
                    }
                }
			}
			if(GetValue($this->GetIDForIdent("ActiveSMOKE"))) {
				$sensorsSMOKE = json_decode($this->ReadPropertyString("SensorsSMOKE"));
				foreach ($sensorsSMOKE as $sensor) {
                    if($sensor->ID == $SenderID) {
						$this->SendDebug("MessageSink", "AlertSourceID: ". $sensor->ID, 0);
                        $this->TriggerAlert($sensor->ID, GetValue($sensor->ID), true);
                        return;
                    }
                }
            }
		}

		public function TriggerAlert(int $SourceID, int $SourceValue, bool $AlarmZoneInternal) {
			$this->SendDebug("TriggerAlert", "SenderID: ". $SourceID, 0);
    		switch($this->GetProfileName(IPS_GetVariable($SourceID))) {
				case "~Window.Hoppe":
					if($SourceValue == 0 || $SourceValue == 2) {
						$this->SetAlert(true, $SourceID);
					}
					break;
				case "~Window.HM":
					if($SourceValue == 1 || $SourceValue == 2) {
						$this->SetAlert(true, $SourceID);
					}
					break;
				case "~Lock.Reversed":
				case "~Battery.Reversed":
				case "~Presence.Reversed":
				case "~Window.Reversed":
					if(!$SourceValue) {
						$this->SetAlert(true, $SourceID);
					}
					break;
				default:
					if($SourceValue) {
						$this->SetAlert(true, $SourceID);
					}
					break;
			}
		}
		
		public function SetAlert(bool $Status, int $SourceID = 0) {

			$targets = json_decode($this->ReadPropertyString("Targets"));
			$DelayScriptID = $this->ReadPropertyInteger("AlarmDelayScriptID");
			$DelayScriptWaitTime = $this->ReadPropertyInteger("AlarmDelaySeconds");
			
			//Lets notify all target devices
			foreach($targets as $targetID) {
				//only allow links
				if (IPS_VariableExists($targetID->ID)) {
					$o = IPS_GetObject($targetID->ID);
					$v = IPS_GetVariable($targetID->ID);

					$actionID = $this->GetProfileAction($v);
					$profileName = $this->GetProfileName($v);

					//If we somehow do not have a profile take care that we do not fail immediately
					if ($profileName != "") {
						//If we are enabling analog devices we want to switch to the maximum value (e.g. 100%)
						if ($Status) {
							$actionValue = IPS_GetVariableProfile($profileName)['MaxValue'];
						} else {
							$actionValue = 0;
						}
						//Reduce to boolean if required
						if ($v['VariableType'] == 0) {
							$actionValue = $actionValue > 0;
						}
					} else {
						$actionValue = $Status;
					}

					if (IPS_InstanceExists($actionID)) {
						IPS_RequestAction($actionID, $o['ObjectIdent'], $actionValue);
					} else {
						if (IPS_ScriptExists($actionID)) {
							echo IPS_RunScriptWaitEx($actionID, Array("VARIABLE" => $targetID->ID, "VALUE" => $actionValue));
						}
					}
				}
			}
			SetValue($this->GetIDForIdent("Alert"), $Status);

			if ($DelayScriptID > 0) {
				echo IPS_RunScriptEx($DelayScriptID, Array("Status" => $Status, "SourceID" => $SourceID, "WaitTimeSec" => $DelayScriptWaitTime));

			}

            //Get name for parent object of last triggered object
            If($SourceID > 0)
            {
                $LastTriggeredName = IPS_GetName(IPS_GetParent($SourceID));
            }
            else {
                $LastTriggeredName = "Keiner";
            }
            SetValue($this->GetIDForIdent("LastTriggeredObject"), $LastTriggeredName);

            If($Status)
            {
                $PushInstanceID = $this->ReadPropertyInteger("PushInstanceID");
                if ($this->ReadPropertyInteger("PushInstanceID") > 0) {
                    $PushIsActive = true;
                }
                else {
                    $PushIsActive = false;
                }
                If($PushIsActive)
                {
                    WFC_PushNotification($PushInstanceID, "Alarm im Haus", "Der Melder " . $LastTriggeredName . " hat ausgelöst!", "alarm", 0);
                }
            }
		}

        public function SetActiveINT(bool $Value) {
			
			SetValue($this->GetIDForIdent("ActiveINT"), $Value);
			
			if(!$Value) {
				$this->SetAlert(false);
			}
        }
        public function SetActiveEXT(bool $Value) {
            
            //$this->SendDebug("AlarmSystem", "Value von SetActiveExt: ". $Value , 0);
            SetValue($this->GetIDForIdent("ActiveEXT"), $Value);
            SetValue($this->GetIDForIdent("ActiveINT"), $Value);
			
			if(!$Value) {
				$this->SetAlert(false);
			}
		}
		public function SetActiveSMOKE(bool $Value) {
			
			SetValue($this->GetIDForIdent("ActiveSMOKE"), $Value);
			
			if(!$Value) {
				$this->SetAlert(false);
			}
        }

		public function RequestAction($Ident, $Value) {
			
			switch($Ident) {
				case "ActiveEXT":
					$this->SetActiveEXT($Value);
                    break;
                case "ActiveINT":
                	$this->SetActiveINT($Value);
					break;
				case "ActiveSMOKE":
					$this->SetActiveSMOKE($Value);
                    break;
				case "Alert":
					$this->SetAlert($Value);
					break;
				default:
					throw new Exception("Invalid ident");
			}
        }
        
        private function GetProfileName($Variable) {
			
			if($Variable['VariableCustomProfile'] != "")
				return $Variable['VariableCustomProfile'];
			else
				return $Variable['VariableProfile'];
		}

		private function GetProfileAction($Variable) {
			
			if($Variable['VariableCustomAction'] != "")
				return $Variable['VariableCustomAction'];
			else
				return $Variable['VariableAction'];
		}

		private function GetActionForVariable($Variable) {
			
			$v = IPS_GetVariable($Variable);

			if ($v['VariableCustomAction'] > 0) {
				return $v['VariableCustomAction'];
			} else {
				return $v['VariableAction'];
			}
		}

		private function CreateVariableByIdent($id, $ident, $name, $type, $profile = "") {
			
			 $vid = @IPS_GetObjectIDByIdent($ident, $id);
			 if($vid === false)
			 {
				 $vid = IPS_CreateVariable($type);
				 IPS_SetParent($vid, $id);
				 IPS_SetName($vid, $name);
				 IPS_SetIdent($vid, $ident);
				 if($profile != "")
					IPS_SetVariableCustomProfile($vid, $profile);
			 }
			 return $vid;
		}

		// public function GetConfigurationForm() {

        //     $formdata = json_decode(file_get_contents(__DIR__ . "/form.json"));

		// 	//Annotate existing elements
		// 	$sensorsEXT = json_decode($this->ReadPropertyString("SensorsEXT"));
		// 	foreach($sensorsEXT as $sensor) {
		// 		//We only need to add annotations. Remaining data is merged from persistance automatically.
		// 		//Order is determinted by the order of array elements
		// 		if(IPS_ObjectExists($sensor->ID) && $sensor->ID !== 0) {
		// 			$status = "OK";
		// 			$rowColor = "";
		// 			if (!IPS_VariableExists($sensor->ID)) {
		// 				$status = $this->Translate("Not a variable");
		// 				$rowColor = "#FFC0C0";
		// 			}				
						
		// 			$formdata->elements[3]->values[] = Array(
		// 				"Name" => IPS_GetName($sensor->ID),
		// 				"Status" => $status,
		// 			);
					
		// 		} else {
		// 			$formdata->elements[3]->values[] = Array(
		// 				"Name" => $this->Translate("Not found!"),
		// 				"rowColor" => "#FFC0C0",
		// 			);
		// 		}
        //     }
            
		// 	$sensorsINT = json_decode($this->ReadPropertyString("SensorsINT"));
		// 	// $test01 = $formdata->elements[0];
		// 	// foreach($test01 as $test)
		// 	// {
		// 	// $this->SendDebug("AlarmSystem", $test , 0);
		// 	// }
		// 	foreach($sensorsINT as $sensor) {
		// 		//We only need to add annotations. Remaining data is merged from persistance automatically.
		// 		//Order is determinted by the order of array elements
		// 		if(IPS_ObjectExists($sensor->ID) && $sensor->ID !== 0) {
		// 			$status = "OK";
		// 			$rowColor = "";
		// 			if (!IPS_VariableExists($sensor->ID)) {
		// 				$status = $this->Translate("Not a variable");
		// 				$rowColor = "#FFC0C0";
		// 			}				
						
		// 			$formdata->elements[0]->values[] = Array(
		// 				"Name" => "IPS_GetName($sensor->ID)",
		// 				"Status" => $status,
		// 			);
					
		// 		} else {
		// 			$formdata->elements[0]->values[] = Array(
		// 				"Name" => $this->Translate("Not found!"),
		// 				"rowColor" => "#FFC0C0",
		// 			);
		// 		}
		// 	}

		// 	$sensorsSMOKE = json_decode($this->ReadPropertyString("SensorsSMOKE"));
		// 	foreach($sensorsSMOKE as $sensor) {
		// 		//We only need to add annotations. Remaining data is merged from persistance automatically.
		// 		//Order is determinted by the order of array elements
		// 		if(IPS_ObjectExists($sensor->ID) && $sensor->ID !== 0) {
		// 			$status = "OK";
		// 			$rowColor = "";
		// 			if (!IPS_VariableExists($sensor->ID)) {
		// 				$status = $this->Translate("Not a variable");
		// 				$rowColor = "#FFC0C0";
		// 			}				
						
		// 			$formdata->elements[7]->values[] = Array(
		// 				"Name" => IPS_GetName($sensor->ID),
		// 				"Status" => $status,
		// 			);
					
		// 		} else {
		// 			$formdata->elements[7]->values[] = Array(
		// 				"Name" => $this->Translate("Not found!"),
		// 				"rowColor" => "#FFC0C0",
		// 			);
		// 		}
		// 	}

		// 	//Annotate existing elements
		// 	$targets = json_decode($this->ReadPropertyString("Targets"));
		// 	foreach($targets as $target) {
		// 		//We only need to add annotations. Remaining data is merged from persistance automatically.
		// 		//Order is determinted by the order of array elements
		// 		if(IPS_ObjectExists($target->ID) && $target->ID !== 0) {
		// 			$status = "OK";
		// 			$rowColor = "";
		// 			if (!IPS_VariableExists($target->ID)) {
		// 				$status = $this->Translate("Not a variable");
		// 				$rowColor = "#FFC0C0";
		// 			} else if ($this->GetActionForVariable($target->ID) <= 10000){
		// 				$status = $this->Translate("No action set");
		// 				$rowColor = "#FFC0C0";
		// 			}

		// 			$formdata->elements[5]->values[] = Array(
		// 				"Name" => IPS_GetName($target->ID),
		// 				"Status" => $status,
		// 				"rowColor" => $rowColor,
		// 			);
		// 		} else {
		// 			$formdata->elements[5]->values[] = Array(
		// 				"Name" => $this->Translate("Not found!"),
		// 				"rowColor" => "#FFC0C0",
		// 			);
		// 		}
		// 	}
		// 	return json_encode($formdata);
		// }
	}
?>
