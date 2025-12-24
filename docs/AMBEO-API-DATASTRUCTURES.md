# Sennheiser AMBEO Soundbar API - Datenstrukturen & Typen

Dieses Dokument beschreibt alle identifizierten Datentypen und -strukturen der AMBEO API.

## Primitive Datentypen

Die API verwendet typisierte Werte mit expliziter Type-Angabe:

### Boolean (`bool_`)

```json
{
  "type": "bool_",
  "bool_": true
}
```

**Verwendung:**
- Mute Status
- Night Mode
- AMBEO Mode
- Sound Feedback
- Subwoofer Status (Ein/Aus)
- Logo Status (Ein/Aus)

### 32-bit Integer (`i32_`)

```json
{
  "type": "i32_",
  "i32_": 50
}
```

**Verwendung:**
- Volume (0-100)
- LED Brightness (Popcorn)
- Source ID (Espresso)
- Preset ID (Espresso)

**Wertebereich:** -2147483648 bis 2147483647 (theoretisch, praktisch eingeschränkt)

### 16-bit Integer (`i16_`)

```json
{
  "type": "i16_",
  "i16_": 5
}
```

**Verwendung:**
- Voice Enhancement Level (Espresso)
- Center/Side/Up Firing Levels (Espresso)
- Subwoofer Volume (Espresso)

**Wertebereich:** -32768 bis 32767 (theoretisch, praktisch eingeschränkt)

### Double/Float (`double_`)

```json
{
  "type": "double_",
  "double_": 5.5
}
```

**Verwendung:**
- Subwoofer Volume (Popcorn): -10.0 bis +10.0

### String (`string_`)

```json
{
  "type": "string_",
  "string_": "AMBEO Soundbar Max"
}
```

**Verwendung:**
- Geräte-Name
- Modell-Name
- Seriennummer
- Firmware-Version

## Komplexe Datentypen

### Power Target (`powerTarget`)

```json
{
  "type": "powerTarget",
  "powerTarget": {
    "target": "online"
  }
}
```

**Mögliche Werte für `target`:**
- `"online"` - Gerät ist eingeschaltet
- `"networkStandby"` - Gerät ist im Standby
- `"playing"` - Wiedergabe läuft (?)
- `"paused"` - Wiedergabe pausiert (?)
- `"stopped"` - Wiedergabe gestoppt (?)

**Hinweis:** Die genaue Semantik ist unklar - möglicherweise Überlappung mit Player-States.

### Espresso Brightness (`espressoBrightness`)

**Nur Espresso API (Max)**

```json
{
  "type": "espressoBrightness",
  "espressoBrightness": {
    "ambeologo": 100,
    "display": 80
  }
}
```

**Felder:**
- `ambeologo`: Helligkeit des AMBEO Logos (1-118)
- `display`: Helligkeit des Displays (1-126)

**Besonderheit:** Beide Werte müssen **immer zusammen** gesetzt werden!

### Popcorn Input ID (`popcornInputId`)

**Nur Popcorn API (Plus/Mini)**

```json
{
  "type": "popcornInputId",
  "popcornInputId": "hdmi1"
}
```

**Mögliche Werte (abhängig vom Modell):**
- `"hdmi1"`, `"hdmi2"`, `"hdmi3"`
- `"optical"`
- `"bluetooth"`
- `"googlecast"` (virtuell?)
- `"airplay"` (virtuell?)

### Popcorn Audio Preset (`popcornAudioPreset`)

**Nur Popcorn API (Plus/Mini)**

```json
{
  "type": "popcornAudioPreset",
  "popcornAudioPreset": "movies"
}
```

**Bekannte Werte:**
- `"neutral"`
- `"movies"`
- `"music"`
- `"news"`
- `"sport"`

**Hinweis:** Die genauen Werte können über `getRows` abgerufen werden.

### Bluetooth State (`bluetoothState`)

**Nur Popcorn API (Plus/Mini)**

```json
{
  "type": "bluetoothState",
  "bluetoothState": {
    "pairable": false
  }
}
```

**Felder:**
- `pairable`: Boolean - Ob sich das Gerät im Pairing-Modus befindet

**Vermutete weitere Felder (nicht dokumentiert):**
- `connected`: Verbindungsstatus?
- `devices`: Liste verbundener Geräte?

### Popcorn Subwoofer List (`popcornSubwooferList`)

**Nur Popcorn API (Plus/Mini)**

```json
{
  "type": "popcornSubwooferList",
  "popcornSubwooferList": []
}
```

**Bei verbundenem Subwoofer (vermutet):**
```json
{
  "type": "popcornSubwooferList",
  "popcornSubwooferList": [
    {
      "id": "sub1",
      "name": "AMBEO Sub",
      "connected": true
    }
  ]
}
```

**Hinweis:** Die genaue Struktur der Array-Elemente ist unklar.

### Play Logic Data (`playLogicData`)

Komplexe Struktur für Player-Informationen.

```json
{
  "type": "playLogicData",
  "playLogicData": {
    "state": "playing",
    "trackRoles": {
      "title": "Song Title",
      "icon": "http://example.com/cover.jpg",
      "mediaData": {
        "metaData": {
          "artist": "Artist Name",
          "album": "Album Name"
        }
      }
    }
  }
}
```

**Felder:**

- **`state`**: String
  - `"playing"` - Wiedergabe läuft
  - `"paused"` - Pausiert
  - `"stopped"` - Gestoppt
  - `"transitioning"` - Übergang (ignorieren)

- **`trackRoles`**: Objekt
  - `title`: String - Titel des Tracks
  - `icon`: String (URL) - Cover-Bild URL
  - `mediaData`: Objekt
    - `metaData`: Objekt
      - `artist`: String - Künstler
      - `album`: String - Album

**Hinweis:** Diese Daten sind nur bei bestimmten Quellen verfügbar (z.B. Bluetooth, Streaming).

## Response-Strukturen

### getData Response

```json
{
  "value": {
    "{datatype}": <value>
  },
  "modifiable": true
}
```

**Felder:**
- `value`: Objekt mit dem typisierten Wert
- `modifiable`: Boolean (optional) - Ob der Wert änderbar ist

**Hinweis:** `modifiable` wird z.B. bei Subwoofer-Verfügbarkeit verwendet.

### getRows Response

```json
{
  "rows": [
    {
      "id": "...",
      "title": "...",
      "value": {
        "{datatype}": <value>
      }
    }
  ]
}
```

**Felder:**
- `rows`: Array von Objekten
  - `id`: String oder Integer - Eindeutige ID
  - `title`: String - Anzeigename
  - `value`: Objekt mit typisiertem Wert

**Verwendung:**
- Sources (Eingänge)
- Presets
- Input Names

### setData Response

```json
{}
```

oder

```json
{
  "success": true
}
```

**Hinweis:** Die genaue Response-Struktur bei setData ist unklar. Im Code wird nur auf HTTP 200 geprüft.

## Spezielle Objekte

### Source Object (Espresso)

```json
{
  "id": 0,
  "title": "TV"
}
```

**Nach Verarbeitung von zwei API-Calls:**

**Felder:**
- `id`: Integer - Index des Eingangs
- `title`: String - Benutzerfreundlicher Name

### Source Object (Popcorn)

```json
{
  "id": "hdmi1",
  "title": "HDMI 1"
}
```

**Direkt aus getRows + virtuelle Quellen:**

**Felder:**
- `id`: String - ID des Eingangs
- `title`: String - Benutzerfreundlicher Name

### Preset Object (Espresso)

```json
{
  "id": 1,
  "title": "Movies"
}
```

**Hardcodiert:**

**Felder:**
- `id`: Integer - Preset-Index (0-4)
- `title`: String - Preset-Name

**Verfügbare Presets:**
0. Neutral
1. Movies
2. Sport
3. News
4. Music

### Preset Object (Popcorn)

```json
{
  "id": "movies",
  "title": "Movies"
}
```

**Aus getRows:**

**Felder:**
- `id`: String - Preset-ID
- `title`: String - Preset-Name

## Control-Objekte

### Player Control

```json
{
  "control": "next"
}
```

oder

```json
{
  "control": "previous"
}
```

**Verwendung bei:**
```http
GET /api/setData?path=player:player/control&roles=activate&value={"control":"next"}
```

**Mögliche Werte:**
- `"next"` - Nächster Track
- `"previous"` - Vorheriger Track

**Vermutete weitere Werte (nicht dokumentiert):**
- `"play"`?
- `"pause"`?
- `"stop"`?

## Type-Mapping für PHP

```php
<?php

enum AmbeoDataType: string
{
    case BOOL = 'bool_';
    case INT32 = 'i32_';
    case INT16 = 'i16_';
    case DOUBLE = 'double_';
    case STRING = 'string_';
    case POWER_TARGET = 'powerTarget';
    case ESPRESSO_BRIGHTNESS = 'espressoBrightness';
    case POPCORN_INPUT_ID = 'popcornInputId';
    case POPCORN_AUDIO_PRESET = 'popcornAudioPreset';
    case BLUETOOTH_STATE = 'bluetoothState';
    case POPCORN_SUBWOOFER_LIST = 'popcornSubwooferList';
    case PLAY_LOGIC_DATA = 'playLogicData';

    public function encode(mixed $value): array
    {
        return [
            'type' => $this->value,
            $this->value => $value
        ];
    }

    public static function decode(array $data, self $expectedType): mixed
    {
        return $data['value'][$expectedType->value] ?? null;
    }
}
```

## Value Constraints (bekannt)

| Typ | Path | Min | Max | Default |
|-----|------|-----|-----|---------|
| i32_ | player:volume | 0 | 100 | - |
| i32_ | ui:/settings/interface/ambeoSection/brightness | 0 | 100 | 50 |
| i32_ | ui:/settings/interface/ledBrightness | 0 | 100 | 50 |
| i32_ | ui:/settings/interface/codecLedBrightness | 0 | 100 | 50 |
| i16_ | ui:/settings/subwoofer/volume (Espresso) | -12 | +12 | 0 |
| double_ | ui:/settings/subwoofer/volume (Popcorn) | -10.0 | +10.0 | 0.0 |
| i16_ | espressoBrightness.display | 1 | 126 | 128* |
| i16_ | espressoBrightness.ambeologo | 1 | 118 | ? |

*Warum Default 128, aber Max 126? → Offene Frage!

## Unbekannte Datentypen

Diese könnten existieren, wurden aber nicht im Code gefunden:

- **Audio-Format-Informationen:** Aktueller Codec (Dolby Atmos, DTS:X, etc.)
- **HDMI-CEC-Daten:** TV-Steuerung, ARC-Status
- **Netzwerk-Konfiguration:** IP, WLAN-SSID, etc.
- **Firmware-Update-Status:** Download-Progress, verfügbare Version
- **Kalibrierungs-Daten:** Raum-Analyse-Ergebnisse
- **Error-Logs:** System-Fehler, Debug-Informationen

## Naming Conventions

### Path-Struktur

Pfade folgen einem hierarchischen Schema:

```
{namespace}:{category}/{subcategory}/{setting}
```

**Beispiele:**
- `player:volume` - Player-Namespace, direkter Zugriff auf Volume
- `settings:/popcorn/audio/nightModeStatus` - Settings-Namespace, verschachtelter Pfad
- `ui:/settings/interface/ledBrightness` - UI-Namespace, tief verschachtelter Pfad

### Namespace-Präfixe

| Präfix | Bedeutung | Verwendung |
|--------|-----------|------------|
| `player:` | Player-Steuerung | Volume, Mute, Playback |
| `settings:/` | Persistente Einstellungen | Audio-Modi, Konfiguration |
| `ui:/` | UI-bezogene Einstellungen | Display, Interface |
| `espresso:` | Espresso Runtime | Max-spezifische Funktionen |
| `popcorn:` | Popcorn Runtime | Plus/Mini-spezifische Funktionen |
| `systemmanager:/` | System-Verwaltung | Device-Name, System-Info |
| `powermanager:` | Power-Management | Standby, Wake |
| `bluetooth:` | Bluetooth-Verwaltung | Pairing, Devices |
| `uipopcorn:` | Popcorn UI | UI-spezifische Werte |

### Type-Suffix Konvention

Alle Datentyp-Namen enden mit `_` (z.B. `bool_`, `i32_`), vermutlich um Kollisionen mit reservierten Keywords zu vermeiden.

## Offene Fragen zu Datenstrukturen

1. **Power Target vs. Player State:** Warum zwei ähnliche State-Systeme?
2. **Espresso Brightness Default:** Technischer Grund für 128 vs. 126?
3. **Bluetooth State:** Welche weiteren Felder existieren?
4. **Subwoofer List:** Vollständige Struktur bei verbundenem Sub?
5. **Play Logic Data:** Welche Felder sind optional/pflicht?
6. **Modifiable Flag:** Bei welchen Werten wird dieser verwendet?
7. **Error Responses:** Wie sehen Fehler-Responses aus?
8. **Missing Values:** Was passiert bei fehlenden Pfaden?
