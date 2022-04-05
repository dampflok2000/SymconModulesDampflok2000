# Abfallkalender (generisch)
Dieses IP-Symcon-Modul erstellt einen generischen Abfallkalender, bei dem die Daten der Müllabfuhr manuell gepflegt werden müssen. Also primär für alle diejenigen, die einen Abfallkalender in IP-Symcon nutzen möchten, jedoch keine Möglichkeit haben, die Daten vom jeweiligen Abfallunternehmen parsen zu können.

## Inhaltsverzeichnis
1. [Funktionen](#funktionen)
2. [Voraussetzungen](#voraussetzungen)
3. [Installation](#installation)
4. [Changelog](#changelog)
5. [To Do's](#to-dos)

## Funktionen
* HTML-Box welche die nächsten Abfalltermine anzeigt
* Option für die Aktivierung der Push-Benachrichtigung (Modulkonfiguration)
* Auswahl folgender Müllarten:
    * Restmüll
    * Verpackungsmüll
    * Pappe/Papier
    * Biotonne
    * Schadstoffe
* Die String-Variablen der Abfuhrtermine können selbstverständlich auch von jedem Skript, welches Abfuhrtermine einer Webseite parsed, automatisch "befüllt" werden. Hierbei ist nur wichtig, dass nach jedem Termin ein "New-Line" folgt.  

## Voraussetzungen
* IP-Symcon ab Version 5.0

## Installation
Das Modul ist über den Module-Store von IP-Symcon, als auch über das Modul-Control mit folgender URL installierbar.  
`https://github.com/dampflok2000/SymconModulesDampflok2000`

Anschließend eine neue Instanz erstellen:

Hersteller         | Gerät       | 
------------ | --------- | 
(dampflok2000)       | Abfallkalender   | 

## Changelog
* 1.3.0
    * FIX: HTML-Font-Attribute wurden durch CSS ersetzt. ACHTUNG: Die HTML-Schriftgröße ist ab sofort in Prozent!
    * FIX: Gelber Sack in Verpackungsmüll umbenannt
    * FIX: Debug-Log schreibt jetzt alle Timer-Zeiten in Sekunden und nicht mehr gemischt in Millisekunden oder Sekunden.
    * FEATURE: Neue Müllart "Schadstoffe" hinzugefügt
* 1.2.0
    * Änderung der Schriftfarben für den HTML-Output möglich
    * Neuer Timer zum Zurücksetzen der Schriftfarbe des "Heute"-Termins
    * Möglichkeit zur Anzeige des Wochentags im HTML-Output
* 1.1.0
    * Lokalisation für Englisch und Deutsch hinzugefügt
* 1.0.0
    * Möglichkeit zur Anpassung der HTML-Font-Size hinzugefügt
    * Zusätzliche Variablen für den jeweils nächsten Abfuhrtermin erstellt
* 0.9.5
    * Biotonne hinzugefügt
    * E-Mail-Benachrichtigung hinzugefügt
* 0.9.0
    * Initiale Erstellung des Moduls
