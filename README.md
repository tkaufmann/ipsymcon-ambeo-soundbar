# AMBEO Soundbar Module für IP-Symcon

Steuert Sennheiser AMBEO Soundbars (Plus, Mini, Max) über die lokale HTTP-API.

## Voraussetzungen

- IP-Symcon 8.0+
- AMBEO Soundbar Plus, Mini oder Max im lokalen Netzwerk
- Port 80 (HTTP) zur Soundbar offen

Getestet mit AMBEO Soundbar Plus. Andere Modelle sollten funktionieren.

## Installation

1. Im Module Store nach "AMBEO" suchen
2. Modul installieren
3. Instanz erstellen: Objekte → Instanz hinzufügen → Sennheiser → AMBEOSoundbar
4. Hostname oder IP-Adresse der Soundbar eintragen
5. Übernehmen

## Konfiguration

| Einstellung | Beschreibung | Standard |
|-------------|--------------|----------|
| Hostname / IP-Adresse | IP oder Hostname der Soundbar | - |
| Update-Intervall | Aktualisierung in Sekunden (1-60) | 10 |

### Eingänge umbenennen

Optional unter "Namen der Eingänge überschreiben". Leer = Standardname.

## Funktionen

- Lautstärke (0-100)
- Stummschaltung
- Eingangsquellen (HDMI TV, HDMI 1/2, Optisch, AUX, Bluetooth)
- Audio-Presets (Neutral, Movies, Sport, News, Music)
- Nachtmodus
- AMBEO Modus
- Sprachverbesserung
- Sound-Feedback

Alle Variablen sind im WebFront und der Mobile App steuerbar.

## Skript-Beispiele

```php
$instanceId = 12345; // Instanz-ID anpassen

// Lautstärke setzen
RequestAction(IPS_GetObjectIDByIdent('Volume', $instanceId), 50);

// Stummschalten
RequestAction(IPS_GetObjectIDByIdent('Mute', $instanceId), true);

// Eingang wechseln (0=HDMI TV, 1=HDMI 1, 2=HDMI 2, 3=Optisch, 4=AUX, 5=Bluetooth)
RequestAction(IPS_GetObjectIDByIdent('Source', $instanceId), 1);

// Audio-Preset (0=Neutral, 1=Movies, 2=Sport, 3=News, 4=Music)
RequestAction(IPS_GetObjectIDByIdent('Preset', $instanceId), 1);

// AMBEO Modus ein/aus
RequestAction(IPS_GetObjectIDByIdent('AMBEOMode', $instanceId), true);

// Status manuell aktualisieren
AMB_UpdateStatus($instanceId);
```

## Einschränkungen

- Nur Plus/Mini/Max Serie (ältere AMBEO Modelle haben andere API)
- Statusänderungen werden im Update-Intervall erkannt, nicht sofort
- Custom-Namen gelten nur in IP-Symcon, nicht in der AMBEO App

## Lizenz

MIT License

## Credits

Basiert auf dem [Home Assistant AMBEO Soundbar Projekt](https://github.com/faizpuru/ha-ambeo_soundbar) von faizpuru.
