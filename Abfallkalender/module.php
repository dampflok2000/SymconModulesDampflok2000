<?php
    // Klassendefinition
    class Abfallkalender extends IPSModule {

        public function Destroy() {
            //Never delete this line!
            parent::Destroy();
        }

        public function Create()
		{
			//Never delete this line!
			parent::Create();
            
			$this->RegisterPropertyBoolean("cbxGS", true);
            $this->RegisterPropertyBoolean("cbxHM", true);
            $this->RegisterPropertyBoolean("cbxPP", true);
            $this->RegisterPropertyBoolean("cbxBO", false);
            $this->RegisterPropertyBoolean("cbxPT", false);
            $this->RegisterVariableString("RestTimesHTML", $this->Translate("Waste dates"), "~HTMLBox");

            $this->RegisterPropertyInteger("PushInstanceID", 0);
            $this->RegisterPropertyInteger("MailInstanceID", 0);

            $this->RegisterPropertyInteger("IntervalUpdateTimer", 0);
            $this->RegisterPropertyInteger("IntervalNotificationTimer", 19);
            $this->RegisterPropertyInteger("IntervalUpdateTimerMinute", 1);
            $this->RegisterPropertyInteger("IntervalHtmlResetColorTodayTimer", 9);
            $this->RegisterPropertyInteger("IntervalNotificationTimerMinute", 50);
            $this->RegisterPropertyInteger("TableFontSize", 100);

            //HTML Defaults:
            $this->RegisterPropertyInteger("selColHtmlDefault", 16777215);
            $this->RegisterPropertyInteger("selColHtmlPickupDayTomorrow", 16744448);
            $this->RegisterPropertyInteger("selColHtmlPickupDayToday", 16711680);
            $this->RegisterPropertyBoolean("cbxHtmlShowDay", false);
            $this->RegisterPropertyBoolean("cbxHtmlResetColorToday", false);

            //Create timers
            $this->RegisterTimer("UpdateTimer", 0, 'AFK_UpdateWasteTimes('.$this->InstanceID.', false);');
            $this->RegisterTimer("NotificationTimer", 0, 'AFK_UpdateWasteTimes('.$this->InstanceID.', false);');
            $this->RegisterTimer("ResetFontTimer", 0, 'AFK_UpdateWasteTimes('.$this->InstanceID.', true);');
		}

        public function ApplyChanges() {

            //Never delete this line!
            parent::ApplyChanges();

            $ModulInfo = IPS_GetInstance($this->InstanceID);
            $ModulName = $ModulInfo['ModuleInfo']['ModuleName'];

            $HourUpdateTimer = $this->ReadPropertyInteger("IntervalUpdateTimer");
            $HourNotificationTimer = $this->ReadPropertyInteger("IntervalNotificationTimer");
            $MinuteUpdateTimer = $this->ReadPropertyInteger("IntervalUpdateTimerMinute");
            $MinuteNotificationTimer = $this->ReadPropertyInteger("IntervalNotificationTimerMinute");
            $HourResetTimer = $this->ReadPropertyInteger("IntervalHtmlResetColorTodayTimer");
            $EnableResetTimer = $this->ReadPropertyBoolean("cbxHtmlResetColorToday");
            
            If ((($HourUpdateTimer > 23) || ($HourNotificationTimer > 23)) || (($HourUpdateTimer < 0) || ($HourNotificationTimer < 0)))
            {
                $this->SetStatus(202);
                $this->SendDebug($ModulName, $this->Translate("The hour of a time is wrong!"), 0);
            }
            else {
                $this->SetStatus(102);
            }

            $this->SetNewTimerInterval($HourUpdateTimer.":".$MinuteUpdateTimer.":17", "UpdateTimer");
            $this->SetNewTimerInterval($HourUpdateTimer.":".$MinuteNotificationTimer.":07", "NotificationTimer");
            If($EnableResetTimer)
            {
                $this->SetNewTimerInterval($HourResetTimer.":"."00".":14", "ResetFontTimer");
            }
            If ($this->ReadPropertyBoolean("cbxGS"))
            {
                $this->RegisterVariableString("YellowBagTime", $this->Translate("Packaging waste event"));
                $this->RegisterVariableString("YellowBagTimes", $this->Translate("Packaging waste"), "~TextBox");
                $this->EnableAction("YellowBagTimes");
            }
            Else
            {
                $this->UnregisterVariable("YellowBagTimes");
                $this->UnregisterVariable("YellowBagTime");
            }
            If ($this->ReadPropertyBoolean("cbxHM"))
            {
                $this->RegisterVariableString("WasteTime", $this->Translate("Household garbage event"));
                $this->RegisterVariableString("WasteTimes", $this->Translate("Household garbage"), "~TextBox");
                $this->EnableAction("WasteTimes");
            }
            Else
            {
                $this->UnregisterVariable("WasteTimes");
                $this->UnregisterVariable("WasteTime");
            }
            If ($this->ReadPropertyBoolean("cbxPP"))
            {
                $this->RegisterVariableString("PaperTime", $this->Translate("Cardboard bin event"));
                $this->RegisterVariableString("PaperTimes", $this->Translate("Cardboard bin"), "~TextBox");
                $this->EnableAction("PaperTimes");
            }
            Else
            {
                $this->UnregisterVariable("PaperTimes");
                $this->UnregisterVariable("PaperTime");
            }

            If ($this->ReadPropertyBoolean("cbxBO"))
            {
                $this->RegisterVariableString("BioTime", $this->Translate("Organic waste event"));
                $this->RegisterVariableString("BioTimes", $this->Translate("Organic waste"), "~TextBox");
                $this->EnableAction("BioTimes");
            }
            Else
            {
                $this->UnregisterVariable("BioTime");
                $this->UnregisterVariable("BioTimes");
            }
            If ($this->ReadPropertyBoolean("cbxPT"))
            {
                $this->RegisterVariableString("PollutantsTime", $this->Translate("Pollutants event"));
                $this->RegisterVariableString("PollutantsTimes", $this->Translate("Pollutants"), "~TextBox");
                $this->EnableAction("PollutantsTimes");
            }
            Else
            {
                $this->UnregisterVariable("PollutantsTimes");
                $this->UnregisterVariable("PollutantsTime");
            }
        }

        //Funktion für die Standardaktionen
        public function RequestAction($Ident, $Value)
        {
            SetValue($this->GetIDForIdent($Ident), $Value);
        }

        //refresh waste times
        public function UpdateWasteTimes($ResetFont = false)
        {
            $this->SetStatus(102);
            $ModulInfo = IPS_GetInstance($this->InstanceID);
            $ModulName = $ModulInfo['ModuleInfo']['ModuleName'];

            $HourUpdateTimer = $this->ReadPropertyInteger("IntervalUpdateTimer");
            $HourNotificationTimer = $this->ReadPropertyInteger("IntervalNotificationTimer");
            $MinuteUpdateTimer = $this->ReadPropertyInteger("IntervalUpdateTimerMinute");
            $MinuteNotificationTimer = $this->ReadPropertyInteger("IntervalNotificationTimerMinute");
            $HourResetTimer = $this->ReadPropertyInteger("IntervalHtmlResetColorTodayTimer");
            $TableFontSize = $this->ReadPropertyInteger("TableFontSize");

            $this->SendDebug($ModulName, $this->Translate("Starting updates of waste times.") , 0);
            //Settings-Variablen:
            $PushInstanceID = $this->ReadPropertyInteger("PushInstanceID");
            $MailInstanceID = $this->ReadPropertyInteger("MailInstanceID");
            if ($this->ReadPropertyInteger("PushInstanceID") > 0) {
                $PushIsActive = true;
            }
            else {
                $PushIsActive = false;
            }
            if ($this->ReadPropertyInteger("MailInstanceID") > 0) {
                $MailIsActive = true;
            }
            else {
                $MailIsActive = false;
            }
            $AbfallTermineHTMLID = IPS_GetObjectIDByIdent("RestTimesHTML", $this->InstanceID);
            
            function closest($dates, $findate)
            {
                $newDates = array();

                foreach($dates as $date)
                {
                    $newDates[] = new DateTime($date);
                }

                sort($newDates);
                foreach ($newDates as $a)
                {
                    if ($a >= $findate)
                        return $a;
                }
                return end($newDates);
            }
            //Check if NotificationTimer was triggered:
            If ($_IPS['SENDER'] == "TimerEvent")
            {
                $ActualHour = date('H');
                If ($HourNotificationTimer <> (int)$ActualHour)
                {
                    $PushIsActive = false;
                    $MailIsActive = false;
                }
            }

            //Hole Abfalldaten:
            $strGS = @GetValueString(IPS_GetObjectIDByIdent("YellowBagTimes", $this->InstanceID));
            $strHM = @GetValueString(IPS_GetObjectIDByIdent("WasteTimes", $this->InstanceID));
            $strPP = @GetValueString(IPS_GetObjectIDByIdent("PaperTimes", $this->InstanceID));
            $strBO = @GetValueString(IPS_GetObjectIDByIdent("BioTimes", $this->InstanceID));
            $strPT = @GetValueString(IPS_GetObjectIDByIdent("PollutantsTimes", $this->InstanceID));
            
            If ((empty($strGS) && $this->ReadPropertyBoolean("cbxGS")) || (empty($strHM) && $this->ReadPropertyBoolean("cbxHM")) || (empty($strPP)) && $this->ReadPropertyBoolean("cbxPP") || (empty($strBO)) && $this->ReadPropertyBoolean("cbxBO") || (empty($strPT)) && $this->ReadPropertyBoolean("cbxPT"))
            {
                $this->SetStatus(201);
                $this->SendDebug($ModulName, "One or more of the waste time strings are empty!", 0);
                exit;
            }

            $today = new DateTime('today midnight');
            $now = new DateTime();
            $PushDiffTimeInterval = $now->diff($today);
            $PushDiffHours = $PushDiffTimeInterval->format('%h');
            
            $nextTermine = array();

            If ($this->ReadPropertyBoolean("cbxGS")) {
                $arrGS = explode("\n", $strGS);
                $wasteTimesIdent = "YellowBagTimes";
                $wasteTimeIdent = "YellowBagTime";
                $timesObjectId = IPS_GetObjectIDByIdent($wasteTimeIdent, $this->InstanceID);
                $nameOfObject = IPS_GetName(IPS_GetObjectIDByIdent($wasteTimesIdent, $this->InstanceID));
                $nextTermine[$nameOfObject] = closest($arrGS, new DateTime('today midnight'));
                SetValueString($timesObjectId, $nextTermine[$nameOfObject]->format('d.m.Y'));
                $nextTermine[$this->Translate("Packaging waste")] = closest($arrGS, new DateTime('today midnight'));
                SetValueString(IPS_GetObjectIDByIdent("YellowBagTime", $this->InstanceID), $nextTermine[$this->Translate("Packaging waste")]->format('d.m.Y'));
            }
            If ($this->ReadPropertyBoolean("cbxHM")) {
                $arrHM = explode("\n", $strHM);
                $wasteTimesIdent = "WasteTimes";
                $wasteTimeIdent = "WasteTime";
                $timesObjectId = IPS_GetObjectIDByIdent($wasteTimeIdent, $this->InstanceID);
                $nameOfObject = IPS_GetName(IPS_GetObjectIDByIdent($wasteTimesIdent, $this->InstanceID));
                $nextTermine[$nameOfObject] = closest($arrHM, new DateTime('today midnight'));
                SetValueString($timesObjectId, $nextTermine[$nameOfObject]->format('d.m.Y'));
            }
            If ($this->ReadPropertyBoolean("cbxPP")) {
                $arrPP = explode("\n", $strPP);
                $wasteTimesIdent = "PaperTimes";
                $wasteTimeIdent = "PaperTime";
                $timesObjectId = IPS_GetObjectIDByIdent($wasteTimeIdent, $this->InstanceID);
                $nameOfObject = IPS_GetName(IPS_GetObjectIDByIdent($wasteTimesIdent, $this->InstanceID));
                $nextTermine[$nameOfObject] = closest($arrPP, new DateTime('today midnight'));
                SetValueString($timesObjectId, $nextTermine[$nameOfObject]->format('d.m.Y'));
            }
            If ($this->ReadPropertyBoolean("cbxBO")) {
                $arrBO = explode("\n", $strBO);
                $wasteTimesIdent = "BioTimes";
                $wasteTimeIdent = "BioTime";
                $timesObjectId = IPS_GetObjectIDByIdent($wasteTimeIdent, $this->InstanceID);
                $nameOfObject = IPS_GetName(IPS_GetObjectIDByIdent($wasteTimesIdent, $this->InstanceID));
                $nextTermine[$nameOfObject] = closest($arrBO, new DateTime('today midnight'));
                SetValueString($timesObjectId, $nextTermine[$nameOfObject]->format('d.m.Y'));
            }
            If ($this->ReadPropertyBoolean("cbxPT")) {
                $arrPT = explode("\n", $strPT);
                $wasteTimesIdent = "PollutantsTimes";
                $wasteTimeIdent = "PollutantsTime";
                $timesObjectId = IPS_GetObjectIDByIdent($wasteTimeIdent, $this->InstanceID);
                $nameOfObject = IPS_GetName(IPS_GetObjectIDByIdent($wasteTimesIdent, $this->InstanceID));
                $nextTermine[$nameOfObject] = closest($arrPT, new DateTime('today midnight'));
                SetValueString($timesObjectId, $nextTermine[$nameOfObject]->format('d.m.Y'));
            }
            
            asort($nextTermine);

            If ($TableFontSize != 100) {
                $HTMLBox = "<table style='border-spacing:10px; font-size:" . $TableFontSize . "%'>";
            }
            else {
                $HTMLBox = "<table style='border-spacing:10px;'>";
            }
            
            foreach ($nextTermine as $key => $value)
            {
                $WasteDayOfWeek = "";
                $ShowDay = $this->ReadPropertyBoolean("cbxHtmlShowDay");
                If ($ShowDay) {
                    $WasteDayOfWeek = $this->Translate($value->format('D'));
                }
                
                $HTMLBox.= "<tr><td>" . $key . ":</td>";
                $interval = $value->diff($today);
                $days = $interval->format('%d');
                If ($days == 1)
                {
                    $ColorHEX = dechex($this->ReadPropertyInteger("selColHtmlPickupDayTomorrow"));
                    If ($ShowDay) {
                        $HTMLBox.= "<td style='color:#" . $ColorHEX . "'>" . $WasteDayOfWeek . "</td><td>";
                    }
                    $HTMLBox.= "<td style='color:#" . $ColorHEX . "'>" . $this->Translate("TOMORROW") . "</td></tr>";
                    If ($PushIsActive)
                    {
                        $this->SendDebug($ModulName, $this->Translate("Push notification is sending now."), 0);
                        WFC_PushNotification($PushInstanceID, $ModulName, $this->Translate("Tomorrow will ").$key.$this->Translate(" picked up!"), "", 0);
                    }
                    If ($MailIsActive)
                    {
                        $this->SendDebug($ModulName, $this->Translate("Mail notification is sending now."), 0);
                        SMTP_SendMail($MailInstanceID, $ModulName, $this->Translate("Tomorrow will ").$key.$this->Translate(" picked up!"));
                    }
                }
                ElseIf ($days == 0)
                {
                    $ColorHEX = dechex($this->ReadPropertyInteger("selColHtmlPickupDayToday"));
                    If ($ShowDay) {
                        $HTMLBox.= "<td style='color:#" . $ColorHEX . "'>" . $WasteDayOfWeek . "</td><td>";
                    }
                    $HTMLBox.= "<td style='color:#" . $ColorHEX . "'>" . $this->Translate("TODAY") . "!</td></tr>";
                }
                Else
                {
                    $ColorHEX = dechex($this->ReadPropertyInteger("selColHtmlDefault"));
                    If ($ShowDay) {
                        $HTMLBox.= "<td style='color:#" . $ColorHEX . "'>" . $WasteDayOfWeek . "</td><td>";
                    }
                    $HTMLBox.= "<td style='color:#" . $ColorHEX . "'>" . $value->format('d.m.Y') . "</td></tr>";
                }
            }
            $HTMLBox.= "</table>";

            SetValueString($AbfallTermineHTMLID, $HTMLBox);

            sleep(1);
            $this->SendDebug($ModulName, $this->Translate("Next update time: ").$HourUpdateTimer.":".$MinuteUpdateTimer, 0);
            $this->SendDebug($ModulName, $this->Translate("Next notification time: ").$HourNotificationTimer.":".$MinuteNotificationTimer, 0);
            $this->SetNewTimerInterval($HourUpdateTimer.":".$MinuteUpdateTimer.":17", "UpdateTimer");
            $this->SetNewTimerInterval($HourNotificationTimer.":".$MinuteNotificationTimer.":07", "NotificationTimer");
        }

        //Demodaten setzen
        public function SetDemoData()
        {
            If ($this->ReadPropertyBoolean("cbxGS")) {
                $varGSID = IPS_GetObjectIDByIdent("YellowBagTimes", $this->InstanceID);
                $bolVarGS = SetValueString($varGSID,
                "04.01.2022\n17.01.2022\n31.01.2022\n14.02.2022\n28.02.2022\n14.03.2022\n28.03.2022\n11.04.2022\n25.04.2022\n09.05.2022\n24.05.2022\n06.06.2022\n20.06.2022\n04.07.2022\n18.07.2022\n01.08.2022\n15.08.2022\n29.08.2022\n12.09.2022\n26.09.2022\n10.10.2022\n24.10.2022\n07.11.2022\n21.11.2022\n05.12.2022\n19.12.2022");
            }
            If ($this->ReadPropertyBoolean("cbxHM")) {
                $varHMID = IPS_GetObjectIDByIdent("WasteTimes", $this->InstanceID);
                $bolVarHM = SetValueString($varHMID,
                "03.01.2022\n16.01.2022\n30.01.2022\n13.02.2022\n27.02.2022\n13.03.2022\n27.03.2022\n10.04.2022\n24.04.2022\n08.05.2022\n23.05.2022\n05.06.2022\n19.06.2022\n03.07.2022\n17.07.2022\n31.07.2022\n14.08.2022\n28.08.2022\n11.09.2022\n25.09.2022\n09.10.2022\n23.10.2022\n06.11.2022\n20.11.2022\n04.12.2022\n18.12.2022");
            }
            If ($this->ReadPropertyBoolean("cbxPP")) {
                $varPPID = IPS_GetObjectIDByIdent("PaperTimes", $this->InstanceID);
                $bolVarPP = SetValueString($varPPID,
                "24.01.2022\n21.02.2022\n21.03.2022\n18.04.2022\n16.05.2022\n13.06.2022\n11.07.2022\n08.08.2022\n05.09.2022\n04.10.2022\n01.11.2022\n28.11.2022\n27.12.2022");
            }
            If ($this->ReadPropertyBoolean("cbxBO")) {
                $varBOID = IPS_GetObjectIDByIdent("BioTimes", $this->InstanceID);
                $bolVarBO = SetValueString($varBOID,
                "25.01.2022\n22.02.2022\n22.03.2022\n19.04.2022\n17.05.2022\n14.06.2022\n11.07.2022\n09.08.2022\n06.09.2022\n04.10.2022\n01.11.2022\n27.11.2022\n27.12.2022");
            }
            If ($this->ReadPropertyBoolean("cbxPT")) {
                $varPTID = IPS_GetObjectIDByIdent("PollutantsTimes", $this->InstanceID);
                $bolVarPT = SetValueString($varPTID,
                "27.02.2022\n26.05.2022\n27.08.2022\n24.11.2022");
            }
            echo $this->Translate("Demo data were successfully stored.");
            $this->SetStatus(102);
        }

        /**
         * Create a timer interval.
         *
         * @access protected
         * @param  string $nextTime String for the next time to set.
         * @param  string $TimerName Name of timer to update.
         */
        protected function SetNewTimerInterval($nextTime, $TimerName)
        {
            $ModulInfo = IPS_GetInstance($this->InstanceID);
            $ModulName = $ModulInfo['ModuleInfo']['ModuleName'];

            $now = time();
            $today = date("Y-m-d");
            $nextTimerInterval = strtotime($today.$nextTime);
            $calTime = $nextTimerInterval - $now;
            If ($calTime > 0)
            {
                $this->SetTimerInterval($TimerName, $calTime * 1000);
                $this->SendDebug($ModulName, $this->Translate("Seconds for next timer ") . $TimerName . $this->Translate(" is ") . $calTime . ".", 0);
            }
            Else
            {
                $calTime = strtotime("+1 day " . $nextTime);
                $this->SetTimerInterval($TimerName, (($calTime - $now) * 1000));
                $this->SendDebug($ModulName, $this->Translate("Seconds for next timer ") . $TimerName . $this->Translate(" is ") . (($calTime - $now)), 0);
            }
        }
    }
?>