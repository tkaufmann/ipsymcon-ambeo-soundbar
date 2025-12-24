# AMBEO Soundbar Module für IP-Symcon

IP-Symcon Modul zur Steuerung von Sennheiser AMBEO Soundbars über die lokale HTTP-API.

## Getestet mit

- **AMBEO Soundbar Plus** (Firmware-Version siehe API-Doku)
- IP-Symcon 8.0+

Andere AMBEO Modelle (Mini, Max) sollten funktionieren, sind aber ungetestet.

## Features

- **Lautstärke-Steuerung** (0-100)
- **Stummschaltung**
- **Eingangsquellen** wählen (HDMI 1/2, HDMI TV, Optisch, AUX, Bluetooth)
- **Audio-Presets** (Neutral, Movies, Sport, News, Music)
- **Nachtmodus** Ein/Aus
- **AMBEO Modus** Ein/Aus
- **Sprachverbesserung** Ein/Aus
- **Sound-Feedback** Ein/Aus
- **Custom-Namen** für Eingänge (optional)
- **Automatische Updates** (konfigurierbar: 1-60 Sekunden)

## Installation

### 1. Repository klonen

```bash
cd /var/lib/symcon/modules/
git clone <repository-url> AMBEOSoundbar
```

### 2. In IP-Symcon Modul laden

1. IP-Symcon Konsole öffnen
2. **Module** → **Hinzufügen** → **Aus Git-Repository**
3. URL: `file:///var/lib/symcon/modules/AMBEOSoundbar`

Oder via Konsole:

```php
IPS_LogMessage('Module', 'Loading AMBEO Soundbar Module');
```

### 3. Instanz erstellen

1. **Objekte** → **Instanz hinzufügen**
2. **Hersteller:** Sennheiser → **AMBEOSoundbar**
3. **Hostname/IP-Adresse** eintragen (z.B. `192.168.1.100` oder `soundbar.local`)
4. **Übernehmen** klicken

## Konfiguration

### Basis-Einstellungen

| Einstellung | Beschreibung | Standard |
|------------|--------------|----------|
| **Hostname / IP-Adresse** | IP oder Hostname der Soundbar im lokalen Netz | - |
| **Update-Intervall** | Aktualisierungsrate in Sekunden | 5 |

### Custom-Namen für Eingänge

Optional können Eingänge umbenannt werden (z.B. "HDMI 1" → "Apple TV"):

1. **Namen der Eingänge überschreiben** aufklappen
2. Gewünschten Namen eintragen
3. Leer lassen für Standard-Namen

## Verwendung

### WebFront

Alle Variablen sind im WebFront steuerbar:

- **Lautstärke:** Schieberegler 0-100
- **Stumm:** Schalter
- **Eingang:** Dropdown-Liste
- **Audio-Preset:** Dropdown-Liste
- **Modi:** Schalter (Nachtmodus, AMBEO, Sprachverbesserung, Sound-Feedback)

### Skript-Beispiele

```php
// Lautstärke setzen
AMB_SetVolume(12345, 50);

// Stummschalten
AMB_SetMute(12345, true);

// Eingang wechseln (Index 0 = erster verfügbarer Eingang)
AMB_SetSource(12345, 1); // z.B. HDMI 2

// Audio-Preset wechseln
AMB_SetPreset(12345, 1); // Movies

// AMBEO Modus einschalten
AMB_SetAMBEOMode(12345, true);

// Status manuell aktualisieren
AMB_UpdateStatus(12345);
```

### Performance

- **Eingänge und Presets werden gecached:** Listen werden einmal beim Start geladen
- **Cache-Refresh:** Bei Neustart, Konfigurationsänderung oder IP-Symcon Neustart
- **API-Traffic:** Standard-Update-Intervall 10 Sekunden (~48 Requests/Minute)

## Netzwerk-Anforderungen

- **Soundbar muss im lokalen Netzwerk erreichbar sein**
- **Port 80** (HTTP) muss offen sein
- **Keine Authentifizierung** erforderlich (lokale API)

## Bekannte Einschränkungen

- **Nur Plus/Mini/Max Serie:** Ältere AMBEO Modelle verwenden andere API
- **Kein Volume-Feedback während Änderung:** Update-Intervall bestimmt Reaktionszeit
- **Custom-Namen nur in IP-Symcon:** AMBEO App zeigt weiterhin Original-Namen

## API-Dokumentation

Umfangreiche API-Dokumentation in `/docs`:

- `AMBEO-API-OVERVIEW.md` - API-Grundlagen
- `AMBEO-API-ENDPOINTS.md` - Verfügbare Endpunkte
- `AMBEO-API-POPCORN.md` - Popcorn API (Plus/Mini)
- `AMBEO-API-ESPRESSO.md` - Espresso API (Max)
- `AMBEO-API-EXAMPLES.md` - Beispiel-Code
- `AMBEO-API-DATASTRUCTURES.md` - Datenstrukturen

## Lizenz

MIT License - siehe LICENSE Datei

## Credits

Dieses Modul basiert auf der Vorarbeit des [Home Assistant AMBEO Soundbar Projekts](https://github.com/faizpuru/ha-ambeo_soundbar) von faizpuru.
