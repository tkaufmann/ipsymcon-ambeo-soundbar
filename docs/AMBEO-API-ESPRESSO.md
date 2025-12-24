# Sennheiser AMBEO Soundbar API - Espresso API (Max)

**Gilt für:** AMBEO Soundbar Max

Die Espresso API ist die spezifische Implementierung für das AMBEO Soundbar Max Modell. Sie unterscheidet sich in mehreren Aspekten von der Popcorn API (Plus/Mini).

## Besonderheiten der Espresso API

- **Debounce-Modus:** Unterstützt (wichtig für Player-Updates)
- **Volume-Schritt:** `0.02` (2%)
- **Subwoofer-Bereich:** `-12` bis `+12`
- **Standby-Funktion:** Verfügbar

## Capabilities

```php
class EspressoCapabilities {
    const STANDBY = true;
    const MAX_LOGO = true;
    const MAX_DISPLAY = true;
    const VOICE_ENHANCEMENT_LEVEL = true;
    const CENTER_SPEAKER_LEVEL = true;
    const SIDE_FIRING_LEVEL = true;
    const UP_FIRING_LEVEL = true;
    const RESET_EXPERT_SETTINGS = true;
    const SUBWOOFER = true;
}
```

## Audio-Features

### Night Mode

#### Abrufen

```http
GET /api/getData?path=espresso:nightModeUi&roles=value&_nocache={timestamp}
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
GET /api/setData?path=espresso:nightModeUi&roles=value&value={"type":"bool_","bool_":true}&_nocache={timestamp}
```

### AMBEO Mode (3D-Virtualisierung)

#### Abrufen

```http
GET /api/getData?path=espresso:ambeoModeUi&roles=value&_nocache={timestamp}
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
GET /api/setData?path=espresso:ambeoModeUi&roles=value&value={"type":"bool_","bool_":true}&_nocache={timestamp}
```

### Sound Feedback

#### Abrufen

```http
GET /api/getData?path=settings:/espresso/soundFeedback&roles=value&_nocache={timestamp}
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
GET /api/setData?path=settings:/espresso/soundFeedback&roles=value&value={"type":"bool_","bool_":false}&_nocache={timestamp}
```

### Voice Enhancement Level

**Besonderheit:** Bei Espresso ist dies ein stufenloser Pegel (Level), nicht nur An/Aus.

#### Abrufen

```http
GET /api/getData?path=ui:/mydevice/voiceEnhanceLevel&roles=value&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "i16_": 5
  }
}
```

**Hinweis:** Werte-Bereich unklar, vermutlich 0-10 oder ähnlich.

#### Setzen

```http
GET /api/setData?path=ui:/mydevice/voiceEnhanceLevel&roles=value&value={"type":"i16_","i16_":7}&_nocache={timestamp}
```

### Expert Audio Settings (Soundbar Max spezifisch)

#### Center Speaker Level

**Abrufen:**
```http
GET /api/getData?path=ui:/settings/audio/centerSettings&roles=value&_nocache={timestamp}
```

**Setzen:**
```http
GET /api/setData?path=ui:/settings/audio/centerSettings&roles=value&value={"type":"i16_","i16_":3}&_nocache={timestamp}
```

#### Side Firing Level

**Abrufen:**
```http
GET /api/getData?path=ui:/settings/audio/widthSettings&roles=value&_nocache={timestamp}
```

**Setzen:**
```http
GET /api/setData?path=ui:/settings/audio/widthSettings&roles=value&value={"type":"i16_","i16_":5}&_nocache={timestamp}
```

#### Up Firing Level

**Abrufen:**
```http
GET /api/getData?path=ui:/settings/audio/heightSettings&roles=value&_nocache={timestamp}
```

**Setzen:**
```http
GET /api/setData?path=ui:/settings/audio/heightSettings&roles=value&value={"type":"i16_","i16_":4}&_nocache={timestamp}
```

#### Reset Expert Settings

```http
GET /api/setData?path=ui:/settings/audio/resetExpertSettings&roles=activate&value={"type":"bool_","bool_":true}&_nocache={timestamp}
```

## Eingänge/Quellen (Sources)

### Aktuelle Quelle abrufen

```http
GET /api/getData?path=espresso:audioInputID&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "i32_": 0
  }
}
```

**Hinweis:** Der Wert ist ein Index (Integer), der auf eine Quelle in der Source-Liste verweist.

### Alle Quellen abrufen

Benötigt **zwei Requests:**

#### 1. Input-Namen abrufen

```http
GET /api/getRows?path=settings:/espresso/inputNames&roles=@all&from=0&to=20&_nocache={timestamp}
```

**Response:**
```json
{
  "rows": [
    {
      "title": "hdmi1",
      "value": {
        "string_": "TV"
      }
    },
    {
      "title": "hdmi2",
      "value": {
        "string_": "Blu-ray"
      }
    },
    {
      "title": "optical",
      "value": {
        "string_": "Optical"
      }
    },
    {
      "title": "bluetooth",
      "value": {
        "string_": "Bluetooth"
      }
    }
  ]
}
```

#### 2. Input-IDs abrufen

```http
GET /api/getRows?path=espresso:&roles=@all&from=0&to=20&_nocache={timestamp}
```

**Response:**
```json
{
  "rows": [
    {"title": "hdmi1"},
    {"title": "hdmi2"},
    {"title": "optical"},
    {"title": "bluetooth"},
    {"title": "aes"}
  ]
}
```

**Verarbeitung:**
1. Erstelle Map: `title → index`
2. Filtere ausgeschlossene Quellen (z.B. `"aes"`)
3. Kombiniere Namen und IDs

**Ausgeschlossene Quellen:**
- `"aes"` (Advanced Encryption Standard - wird ausgefiltert)

**Resultierende Struktur:**
```json
[
  {"id": 0, "title": "TV"},
  {"id": 1, "title": "Blu-ray"},
  {"id": 2, "title": "Optical"},
  {"id": 3, "title": "Bluetooth"}
]
```

### Quelle setzen

```http
GET /api/setData?path=espresso:audioInputID&roles=value&value={"type":"i32_","i32_":1}&_nocache={timestamp}
```

## Audio-Presets

### Aktuelles Preset abrufen

```http
GET /api/getData?path=settings:/espresso/equalizerPreset&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "i32_": 1
  }
}
```

### Preset setzen

```http
GET /api/setData?path=settings:/espresso/equalizerPreset&roles=value&value={"type":"i32_","i32_":2}&_nocache={timestamp}
```

### Verfügbare Presets (hardcodiert)

Die Presets sind fest definiert:

```json
[
  {"id": 0, "title": "Neutral"},
  {"id": 1, "title": "Movies"},
  {"id": 2, "title": "Sport"},
  {"id": 3, "title": "News"},
  {"id": 4, "title": "Music"}
]
```

## Display & LEDs

### Display Brightness

Die Espresso API verwendet ein kombiniertes Objekt für Display und Logo.

#### Abrufen

```http
GET /api/getData?path=settings:/espresso/brightnessSensor&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "espressoBrightness": {
      "display": 100,
      "ambeologo": 80
    }
  }
}
```

**Hinweis:** Beide Werte sind in einem Objekt gespeichert!

#### Display Brightness setzen

**Wichtig:** Beim Setzen muss **immer** das gesamte Objekt gesendet werden!

```http
GET /api/setData?path=settings:/espresso/brightnessSensor&roles=value&value={"type":"espressoBrightness","espressoBrightness":{"display":75,"ambeologo":80}}&_nocache={timestamp}
```

**Workflow:**
1. Aktuellen Wert abrufen (beide: display + ambeologo)
2. Nur den gewünschten Wert ändern
3. Gesamtes Objekt zurücksenden

**Wertebereich Display:**
- Minimum: `1`
- Maximum: `126`
- Default: `128`

### Logo Brightness

#### Abrufen

Gleicher Endpunkt wie Display (siehe oben).

#### Setzen

```http
GET /api/setData?path=settings:/espresso/brightnessSensor&roles=value&value={"type":"espressoBrightness","espressoBrightness":{"display":100,"ambeologo":90}}&_nocache={timestamp}
```

**Wertebereich Logo:**
- Minimum: `1`
- Maximum: `118`

**Wichtig:** Logo und Display können **nicht unabhängig** gesetzt werden - immer das gesamte Objekt senden!

## Power-Management (Standby)

### Standby einschalten

```http
GET /api/setData?path=espresso:appRequestedStandby&roles=value&value={"type":"bool_","bool_":true}&_nocache={timestamp}
```

### Aufwachen (Wake)

```http
GET /api/setData?path=espresso:appRequestedOnline&roles=value&value={"type":"bool_","bool_":true}&_nocache={timestamp}
```

## Subwoofer

### Subwoofer-Verfügbarkeit prüfen

```http
GET /api/getData?path=ui:/settings/subwoofer/enabled&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "bool_": false
  },
  "modifiable": true
}
```

**Hinweis:** Der `modifiable` Key zeigt an, ob ein Subwoofer verfügbar ist (nicht der `bool_` Wert!).

### Subwoofer Status

#### Abrufen

```http
GET /api/getData?path=ui:/settings/subwoofer/enabled&roles=value&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "bool_": true
  }
}
```

#### Setzen (Ein/Aus)

```http
GET /api/setData?path=ui:/settings/subwoofer/enabled&roles=value&value={"type":"bool_","bool_":true}&_nocache={timestamp}
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
    "i16_": 5
  }
}
```

**Wertebereich:** `-12` bis `+12`

#### Setzen

```http
GET /api/setData?path=ui:/settings/subwoofer/volume&roles=value&value={"type":"i16_","i16_":8}&_nocache={timestamp}
```

**Hinweis:** Der Wert muss als Integer gesendet werden, auch bei negativen Werten:
```json
{"type":"i16_","i16_":-5}
```

## Zusammenfassung Pfad-Präfixe

In der Espresso API werden folgende Präfixe verwendet:

- `espresso:` - Espresso-spezifische Runtime-Daten
- `settings:/espresso/` - Espresso-spezifische Einstellungen
- `ui:/mydevice/` - UI-bezogene Device-Einstellungen
- `ui:/settings/` - Allgemeine UI-Einstellungen
- `player:` - Player-Steuerung (gemeinsam)
- `powermanager:` - Power-Management (gemeinsam)

## Offene Fragen (Espresso-spezifisch)

1. **Wertebereich Voice Enhancement Level:** Unklar, welche Werte erlaubt sind (vermutlich 0-10)
2. **Wertebereich Expert Settings:** Unklare Min/Max-Werte für Center/Side/Up Firing Levels
3. **Display Brightness Default:** Warum ist Default `128`, aber Maximum nur `126`?
4. **AES Input:** Warum wird die AES-Quelle ausgefiltert? Technische Limitation?
5. **Brightness Sensor:** Der Pfad heißt "brightnessSensor" - gibt es einen Auto-Modus basierend auf Sensor?
