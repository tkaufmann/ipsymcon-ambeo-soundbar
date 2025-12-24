# Sennheiser AMBEO Soundbar API - Popcorn API (Plus/Mini)

**Gilt für:** AMBEO Soundbar Plus, AMBEO Soundbar Mini

Die Popcorn API ist die spezifische Implementierung für die AMBEO Soundbar Plus und Mini Modelle. Sie unterscheidet sich in mehreren Aspekten von der Espresso API (Max).

## Besonderheiten der Popcorn API

- **Debounce-Modus:** Nicht unterstützt
- **Volume-Schritt:** `0.01` (1%)
- **Subwoofer-Bereich:** `-10` bis `+10`
- **Standby-Funktion:** Nicht verfügbar (nur Network Standby)

## Capabilities

```php
class PopcornCapabilities {
    const AMBEO_LOGO = true;
    const LED_BAR = true;
    const CODEC_LED = true;
    const VOICE_ENHANCEMENT_TOGGLE = true;
    const BLUETOOTH_PAIRING = true;
    const SUBWOOFER = true;
    const ECO_MODE = true;
}
```

## Audio-Features

### Night Mode

#### Abrufen

```http
GET /api/getData?path=settings:/popcorn/audio/nightModeStatus&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "bool_": true
  }
}
```

#### Setzen

```http
GET /api/setData?path=settings:/popcorn/audio/nightModeStatus&roles=value&value={"type":"bool_","bool_":false}&_nocache={timestamp}
```

### Voice Enhancement

**Besonderheit:** Bei Popcorn ist dies ein einfacher Toggle (An/Aus), **kein** stufenloser Level.

#### Abrufen

```http
GET /api/getData?path=settings:/popcorn/audio/voiceEnhancement&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "bool_": false
  }
}
```

#### Setzen

```http
GET /api/setData?path=settings:/popcorn/audio/voiceEnhancement&roles=value&value={"type":"bool_","bool_":true}&_nocache={timestamp}
```

### AMBEO Mode (3D-Virtualisierung)

#### Abrufen

```http
GET /api/getData?path=settings:/popcorn/audio/ambeoModeStatus&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "bool_": true
  }
}
```

#### Setzen

```http
GET /api/setData?path=settings:/popcorn/audio/ambeoModeStatus&roles=value&value={"type":"bool_","bool_":false}&_nocache={timestamp}
```

### Sound Feedback

#### Abrufen

```http
GET /api/getData?path=settings:/popcorn/ux/soundFeedbackStatus&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "bool_": true
  }
}
```

#### Setzen

```http
GET /api/setData?path=settings:/popcorn/ux/soundFeedbackStatus&roles=value&value={"type":"bool_","bool_":false}&_nocache={timestamp}
```

### Eco Mode

**Besonderheit:** Nur in Popcorn API verfügbar.

#### Abrufen

```http
GET /api/getData?path=uipopcorn:ecoModeState&roles=value&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "bool_": false
  }
}
```

**Hinweis:** Setzen-Funktion nicht im Code gefunden - möglicherweise read-only oder über anderen Pfad.

## Eingänge/Quellen (Sources)

### Aktuelle Quelle abrufen

```http
GET /api/getData?path=popcorn:inputChange/selected&roles=@all&_nocache={timestamp}
```

**Response (verifiziert):**
```json
{
  "path": "popcorn:inputChange/selected",
  "timestamp": 1766503361452,
  "value": {
    "popcornInputId": "hdmi1",
    "type": "popcornInputId"
  },
  "modifiable": true,
  "type": "value"
}
```

**Hinweis:** Bei Popcorn sind die IDs **Strings**, nicht Integers wie bei Espresso!

### Alle Quellen abrufen

```http
GET /api/getRows?path=ui:/inputs&roles=@all&from=0&to=20&_nocache={timestamp}
```

**Response (verifiziert mit AMBEO Plus):**
```json
{
  "roles": {
    "path": "ui:/inputs",
    "title": "Eingangsquellen",
    "containerType": "none",
    "icon": "skin:iconInputs",
    "type": "container"
  },
  "rowsCount": 7,
  "rows": [
    {
      "path": "ui:/inputs/hdmiTv",
      "id": "hdmiarc",
      "title": "HDMI TV",
      "type": "action"
    },
    {
      "path": "ui:/inputs/hdmi1",
      "id": "hdmi1",
      "title": "HDMI 1",
      "type": "action"
    },
    {
      "path": "ui:/inputs/hdmi2",
      "id": "hdmi2",
      "title": "HDMI 2",
      "type": "action"
    },
    {
      "path": "ui:/inputs/optical",
      "id": "spdif",
      "title": "Optisch",
      "type": "action"
    },
    {
      "path": "ui:/inputs/aux",
      "id": "aux",
      "title": "AUX",
      "type": "action"
    },
    {
      "path": "ui:/inputs/bluetooth",
      "id": "bluetooth",
      "title": "Bluetooth",
      "description": "nsdk_status_description_not_connected",
      "type": "action"
    },
    {
      "path": "ui:/inputs/spotify",
      "id": "spotify",
      "title": "Spotify Connect",
      "disabled": true,
      "type": "action"
    }
  ],
  "rowsVersion": 0
}
```

**⚠️ WICHTIG - Source ID ≠ Path!**

Die `id` im Row-Objekt ist **NICHT** identisch mit dem letzten Segment des Pfads:

| Path (getRows) | ID (für setData) | Title |
|---------------|------------------|-------|
| `ui:/inputs/hdmiTv` | `hdmiarc` | HDMI TV (eARC/ARC) |
| `ui:/inputs/optical` | `spdif` | Optisch |

**Tatsächliche Source-IDs (verifiziert):**
- `hdmiarc` - HDMI TV (eARC/ARC Input)
- `hdmi1` - HDMI 1
- `hdmi2` - HDMI 2
- `spdif` - Optical (nicht "optical"!)
- `aux` - AUX Eingang
- `bluetooth` - Bluetooth
- `spotify` - Spotify Connect (disabled)

**Anmerkungen:**
- Google Cast und AirPlay wurden **NICHT** in der getRows-Response gefunden
- Spotify Connect ist als `disabled: true` markiert
- Die `description` bei Bluetooth zeigt den Verbindungsstatus

### Quelle setzen

```http
GET /api/setData?path=ui:/inputs/hdmi1&roles=activate&value={"type":"bool_","bool_":true}&_nocache={timestamp}
```

**Wichtig:** Der Pfad verwendet die **Path-Segmente** aus getRows, nicht die IDs!

## Audio-Presets

### Aktuelles Preset abrufen

```http
GET /api/getData?path=settings:/popcorn/audio/audioPresets/audioPreset&roles=@all&_nocache={timestamp}
```

**Response (verifiziert):**
```json
{
  "path": "settings:/popcorn/audio/audioPresets/audioPreset",
  "defaultValue": {
    "popcornAudioPreset": "adaptive",
    "type": "popcornAudioPreset"
  },
  "title": "Current audio preset",
  "timestamp": 1766504138673,
  "value": {
    "popcornAudioPreset": "adaptive",
    "type": "popcornAudioPreset"
  },
  "edit": {
    "enumPath": "settings:/popcorn/audio/audioPresetValues",
    "type": "enum_"
  },
  "modifiable": true,
  "type": "value"
}
```

**Hinweis:** Bei Popcorn sind Presets **Strings**, nicht Integers! Die `edit.enumPath` verweist auf die Liste der verfügbaren Presets.

### Preset setzen

```http
GET /api/setData?path=settings:/popcorn/audio/audioPresets/audioPreset&roles=value&value={"type":"popcornAudioPreset","popcornAudioPreset":"movie"}&_nocache={timestamp}
```

### Alle Presets abrufen

```http
GET /api/getRows?path=settings:/popcorn/audio/audioPresetValues&roles=@all&from=0&to=10&_nocache={timestamp}
```

**Response (verifiziert mit AMBEO Plus):**
```json
{
  "rowsCount": 6,
  "rows": [
    {
      "path": "settings:/popcorn/audio/audioPresetValues/0-adaptive",
      "defaultValue": {
        "popcornAudioPreset": "adaptive",
        "type": "popcornAudioPreset"
      },
      "title": "Adaptive",
      "value": {
        "popcornAudioPreset": "adaptive",
        "type": "popcornAudioPreset"
      },
      "type": "value"
    },
    {
      "path": "settings:/popcorn/audio/audioPresetValues/1-music",
      "title": "Musik",
      "value": {
        "popcornAudioPreset": "music",
        "type": "popcornAudioPreset"
      },
      "type": "value"
    },
    {
      "path": "settings:/popcorn/audio/audioPresetValues/2-movie",
      "title": "Film",
      "value": {
        "popcornAudioPreset": "movie",
        "type": "popcornAudioPreset"
      },
      "type": "value"
    },
    {
      "path": "settings:/popcorn/audio/audioPresetValues/3-news",
      "title": "Nachrichten",
      "value": {
        "popcornAudioPreset": "news",
        "type": "popcornAudioPreset"
      },
      "type": "value"
    },
    {
      "path": "settings:/popcorn/audio/audioPresetValues/4-neutral",
      "title": "Neutral",
      "value": {
        "popcornAudioPreset": "neutral",
        "type": "popcornAudioPreset"
      },
      "type": "value"
    },
    {
      "path": "settings:/popcorn/audio/audioPresetValues/5-sports",
      "title": "Sport",
      "value": {
        "popcornAudioPreset": "sports",
        "type": "popcornAudioPreset"
      },
      "type": "value"
    }
  ],
  "rowsVersion": 0
}
```

**Verfügbare Presets (verifiziert):**
- `adaptive` - **Adaptive** (NEU - war nicht dokumentiert!)
- `music` - Musik
- `movie` - Film (nicht "movies"!)
- `news` - Nachrichten
- `neutral` - Neutral
- `sports` - Sport (nicht "sport"!)

**Preset-Pfade:** Haben numerischen Index-Prefix: `settings:/popcorn/audio/audioPresetValues/{index}-{preset_id}`

## Display & LEDs

Im Gegensatz zur Espresso API (Max) haben Plus/Mini **mehrere unabhängige** LED-Steuerungen.

### AMBEO Logo

#### Status (Ein/Aus) abrufen

```http
GET /api/getData?path=settings:/popcorn/ui/ledStatus&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "bool_": true
  }
}
```

#### Status setzen

```http
GET /api/setData?path=settings:/popcorn/ui/ledStatus&roles=value&value={"type":"bool_","bool_":false}&_nocache={timestamp}
```

#### Helligkeit abrufen

```http
GET /api/getData?path=ui:/settings/interface/ambeoSection/brightness&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "i32_": 75
  }
}
```

**Wertebereich:** `0` bis `100` (vermutlich)

#### Helligkeit setzen

```http
GET /api/setData?path=ui:/settings/interface/ambeoSection/brightness&roles=value&value={"type":"i32_","i32_":50}&_nocache={timestamp}
```

### LED Bar

**Nur bei Plus/Mini vorhanden.**

#### Helligkeit abrufen

```http
GET /api/getData?path=ui:/settings/interface/ledBrightness&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "i32_": 80
  }
}
```

#### Helligkeit setzen

```http
GET /api/setData?path=ui:/settings/interface/ledBrightness&roles=value&value={"type":"i32_","i32_":60}&_nocache={timestamp}
```

### Codec LED

**Nur bei Plus/Mini vorhanden.**

#### Helligkeit abrufen

```http
GET /api/getData?path=ui:/settings/interface/codecLedBrightness&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "i32_": 100
  }
}
```

#### Helligkeit setzen

```http
GET /api/setData?path=ui:/settings/interface/codecLedBrightness&roles=value&value={"type":"i32_","i32_":25}&_nocache={timestamp}
```

**Hinweis:** Codec LED zeigt den aktuell verwendeten Audio-Codec an (z.B. Dolby Atmos).

## Bluetooth

**Besonderheit:** Nur in Popcorn API verfügbar (Plus/Mini haben erweiterte Bluetooth-Features).

### Bluetooth State abrufen

```http
GET /api/getData?path=bluetooth:state&roles=@all&_nocache={timestamp}
```

**Response (verifiziert mit AMBEO Plus):**
```json
{
  "path": "bluetooth:state",
  "timestamp": 1766422364993,
  "value": {
    "bluetoothState": {
      "discoverable": false,
      "connectable": true,
      "connected": false,
      "name": "AMBEO Soundbar Plus",
      "discovering": false,
      "powered": true,
      "pairable": false,
      "address": "80:C3:BA:2B:D8:51"
    },
    "type": "bluetoothState"
  },
  "modifiable": true,
  "type": "value"
}
```

**Bluetooth State Felder (vollständig dokumentiert):**

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `discoverable` | Boolean | Gerät ist sichtbar für andere Bluetooth-Geräte |
| `connectable` | Boolean | Gerät kann Verbindungen annehmen |
| `connected` | Boolean | Mindestens ein Gerät ist verbunden |
| `name` | String | Bluetooth-Name der Soundbar |
| `discovering` | Boolean | Soundbar scannt nach Bluetooth-Geräten |
| `powered` | Boolean | Bluetooth ist eingeschaltet |
| `pairable` | Boolean | **Pairing-Modus aktiv** (Wichtig für neue Verbindungen!) |
| `address` | String | Bluetooth MAC-Adresse |

**Hinweis:** `pairable: true` bedeutet, dass sich das Gerät im Pairing-Modus befindet und neue Geräte gekoppelt werden können.

### Pairing-Modus aktivieren/deaktivieren

```http
GET /api/setData?path=bluetooth:deviceList/discoverable&roles=activate&value={"type":"bool_","bool_":true}&_nocache={timestamp}
```

**Hinweis:** Setzt das Gerät in den Pairing-Modus, sodass neue Bluetooth-Geräte es finden können.

## Subwoofer

### Subwoofer-Verfügbarkeit prüfen

```http
GET /api/getData?path=settings:/popcorn/subwoofer/list&roles=@all&_nocache={timestamp}
```

**Response (verifiziert ohne Subwoofer):**
```json
{
  "path": "settings:/popcorn/subwoofer/list",
  "defaultValue": {
    "popcornSubwooferList": [],
    "type": "popcornSubwooferList"
  },
  "title": "Subwoofer-Liste",
  "value": {
    "popcornSubwooferList": [],
    "type": "popcornSubwooferList"
  },
  "modifiable": true,
  "type": "value"
}
```

**Hinweis:** Wenn das Array **leer** ist, ist kein Subwoofer verbunden. Wenn Elemente vorhanden sind, ist ein Subwoofer verfügbar.

**Beispiel mit Subwoofer (nicht verifiziert):**
```json
{
  "value": {
    "popcornSubwooferList": [
      {
        "id": "sub1",
        "name": "AMBEO Sub"
      }
    ]
  }
}
```

**Status:** ⚠️ Struktur bei verbundenem Subwoofer nicht verifiziert (kein Subwoofer verfügbar)

### Subwoofer Status (Ein/Aus)

#### Abrufen

```http
GET /api/getData?path=ui:/settings/subwoofer/enabled&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "bool_": true
  }
}
```

#### Setzen

```http
GET /api/setData?path=ui:/settings/subwoofer/enabled&roles=value&value={"type":"bool_","bool_":false}&_nocache={timestamp}
```

### Subwoofer Lautstärke

#### Abrufen

```http
GET /api/getData?path=ui:/settings/subwoofer/volume&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "double_": 5.5
  }
}
```

**Hinweis:** Bei Popcorn wird `double_` verwendet, bei Espresso `i16_`!

**Wertebereich:** `-10.0` bis `+10.0`

#### Setzen

```http
GET /api/setData?path=ui:/settings/subwoofer/volume&roles=value&value={"type":"double_","double_":7.5}&_nocache={timestamp}
```

## Zusammenfassung Pfad-Präfixe

In der Popcorn API werden folgende Präfixe verwendet:

- `popcorn:` - Popcorn-spezifische Runtime-Daten
- `settings:/popcorn/` - Popcorn-spezifische Einstellungen
- `ui:/settings/` - UI-Einstellungen
- `ui:/inputs/` - Eingänge/Quellen
- `bluetooth:` - Bluetooth-Verwaltung
- `uipopcorn:` - UI-spezifisch für Popcorn
- `player:` - Player-Steuerung (gemeinsam)
- `powermanager:` - Power-Management (gemeinsam)

## Unterschiede zur Espresso API

| Feature | Espresso (Max) | Popcorn (Plus/Mini) |
|---------|----------------|---------------------|
| **Source IDs** | Integer (Index) | String (ID) |
| **Preset IDs** | Integer | String |
| **Subwoofer Volume** | `i16_` | `double_` |
| **Subwoofer Range** | -12 bis +12 | -10 bis +10 |
| **Voice Enhancement** | Level (i16_) | Toggle (bool_) |
| **LED Controls** | Logo + Display kombiniert | Logo, LED Bar, Codec LED separat |
| **Standby** | Ja | Nein |
| **Bluetooth Pairing** | Nein | Ja |
| **Eco Mode** | Nein | Ja |
| **Expert Audio Settings** | Ja (Center/Side/Up Firing) | Nein |

## Besondere Hinweise

### Source-Aktivierung

Bei Popcorn wird eine Quelle **nicht** über einen Set-Value-Befehl aktiviert, sondern durch Aktivierung des entsprechenden Pfades:

```http
GET /api/setData?path=ui:/inputs/{source_id}&roles=activate&value={"type":"bool_","bool_":true}
```

Dieser Mechanismus unterscheidet sich fundamental von Espresso!

### Zusätzliche Quellen

Google Cast und AirPlay werden **programmatisch** zur Quellenliste hinzugefügt, sind aber nicht in der `getRows`-Response enthalten. Es ist unklar, ob diese Quellen tatsächlich aktivierbar sind oder nur zur Anzeige dienen.

### LED-Unabhängigkeit

Im Gegensatz zu Espresso (wo Logo + Display kombiniert sind) können bei Popcorn alle LEDs **unabhängig** gesteuert werden.

## Geklärte Punkte (verifiziert mit AMBEO Plus)

✅ **LED Wertebereiche:** 0-100% (i32_) für alle LEDs
✅ **Sources:** `hdmiarc`, `hdmi1`, `hdmi2`, `spdif`, `aux`, `bluetooth`, `spotify` (disabled)
✅ **Presets:** `adaptive` (NEU!), `music`, `movie`, `news`, `neutral`, `sports`
✅ **Bluetooth State:** Vollständig dokumentiert (8 Felder inkl. MAC-Adresse)
✅ **Subwoofer ohne Sub:** List ist leer, enabled ist `disabled: true`
✅ **Response-Struktur:** Viel detaillierter als erwartet (edit, valueUnit, icons, etc.)
✅ **HTTPS:** Nicht verfügbar - nur HTTP Port 80

## Offene Fragen (Popcorn-spezifisch)

❓ **Eco Mode Setter:** Getter funktioniert (`uipopcorn:ecoModeState`), aber Setter nicht gefunden - read-only?
❓ **Google Cast / AirPlay:** Nicht in getRows gefunden - wurden als virtuelle Quellen im Code hinzugefügt?
❓ **Subwoofer List Details:** Struktur bei verbundenem Sub nicht verifizierbar (kein Sub vorhanden)
❓ **Bluetooth Device List:** Endpunkt für verbundene Geräte nicht gefunden
❓ **Player State:** `player:player` ist Container - keine Sub-Pfade gefunden
❓ **Power State:** Keine Power-State-Pfade gefunden (möglicherweise nur Espresso)
❓ **Firmware Version:** Kein funktionierender Pfad gefunden
