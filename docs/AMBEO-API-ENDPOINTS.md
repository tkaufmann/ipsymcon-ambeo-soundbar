# Sennheiser AMBEO Soundbar API - Endpunkt-Referenz

Dieses Dokument listet alle identifizierten API-Endpunkte mit ihren Pfaden, Parametern und Beispielen auf.

## Basis-URL

```
http://{ip}:80/api
```

**Wichtig:** Nur HTTP Port 80 wird unterstützt - HTTPS ist **nicht** verfügbar!

## Response-Struktur (verifiziert)

Alle `getData` Responses folgen dieser Struktur:

```json
{
  "path": "...",
  "title": "Deutscher oder englischer Titel",
  "timestamp": 1766422364930,
  "value": {
    "type": "...",
    "...": <wert>
  },
  "defaultValue": { ... },
  "modifiable": true,
  "type": "value"
}
```

**Zusätzliche optionale Felder:**
- `icon`: Skin-Referenz (z.B. `"skin:iconNight"`)
- `valueUnit`: Einheit als typisiertes Objekt (z.B. `{"string_": "%", "type": "string_"}`)
- `edit`: UI-Steuerungs-Metadaten für Slider/Enums
- `longDescription`: Ausführliche Beschreibung
- `disabled`: Boolean (true wenn Feature nicht verfügbar)
- `description`: Statusbeschreibung

**Edit-Metadaten Beispiele:**

Slider:
```json
{
  "edit": {
    "step": "1",
    "min": "0",
    "max": "100",
    "type": "slider"
  }
}
```

Enum (Auswahlliste):
```json
{
  "edit": {
    "enumPath": "settings:/popcorn/audio/audioPresetValues",
    "type": "enum_"
  }
}
```

**Error-Response bei ungültigen Pfaden:**
```json
{
  "error": {
    "name": "CMAbstractWorker::invalidPath",
    "message": "Node at path '...' does not exist",
    "title": "Error",
    "actionReply": []
  }
}
```

## Gemeinsame Endpunkte (Alle Modelle)

### System-Informationen

#### Geräte-Name abrufen

```http
GET /api/getData?path=systemmanager:/deviceName&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "string_": "Mein Ambeo"
  }
}
```

#### Seriennummer abrufen

```http
GET /api/getData?path=settings:/system/serialNumber&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "string_": "ABC123456789"
  }
}
```

#### Firmware-Version abrufen

```http
GET /api/getData?path=ui:settings/firmwareUpdate/currentVersion&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "string_": "3.6.11"
  }
}
```

#### Produktname/Modell abrufen

```http
GET /api/getData?path=settings:/system/productName&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "string_": "AMBEO Soundbar Max"
  }
}
```

### Audio-Steuerung

#### Lautstärke abrufen

```http
GET /api/getData?path=player:volume&roles=@all&_nocache={timestamp}
```

**Response (verifiziert):**
```json
{
  "path": "player:volume",
  "title": "Volume",
  "timestamp": 1766503401605,
  "value": {
    "type": "i32_",
    "i32_": 24
  },
  "edit": {
    "step": "1",
    "min": "0",
    "max": "100",
    "type": "slider"
  },
  "modifiable": true,
  "type": "value"
}
```

**Hinweise:**
- Lautstärke ist ein Integer-Wert von 0-100
- `edit` Metadaten für UI-Steuerung enthalten (step, min, max, type)
- `timestamp` ist Unix-Timestamp in Millisekunden

#### Lautstärke setzen

```http
GET /api/setData?path=player:volume&roles=value&value={"type":"i32_","i32_":75}&_nocache={timestamp}
```

**Hinweis:** Der `value` Parameter muss URL-encoded sein:
```
value=%7B%22type%22%3A%22i32_%22%2C%22i32_%22%3A75%7D
```

#### Mute-Status abrufen

```http
GET /api/getData?path=settings:/mediaPlayer/mute&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "bool_": false
  }
}
```

#### Mute setzen/aufheben

```http
GET /api/setData?path=settings:/mediaPlayer/mute&roles=value&value={"type":"bool_","bool_":true}&_nocache={timestamp}
```

### Power-Management

#### Power-Status abrufen

```http
GET /api/getData?path=powermanager:target&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "powerTarget": {
      "target": "online"
    }
  }
}
```

**Mögliche Werte:**
- `"online"` - Gerät ist eingeschaltet
- `"networkStandby"` - Gerät ist im Standby (abhängig von Capabilities)
- `"playing"` - Wiedergabe läuft
- `"paused"` - Wiedergabe pausiert
- `"stopped"` - Wiedergabe gestoppt

### Player-Steuerung

#### Play/Pause

```http
GET /api/setData?path=popcorn:multiPurposeButtonActivate&roles=activate&value={"type":"bool_","bool_":true}&_nocache={timestamp}
```

**Hinweis:** Dieser Endpunkt dient sowohl für Play als auch Pause (Toggle-Funktion).

#### Nächster Titel

```http
GET /api/setData?path=player:player/control&roles=activate&value={"control":"next"}&_nocache={timestamp}
```

**Value (URL-encoded):**
```json
{"control": "next"}
```

#### Vorheriger Titel

```http
GET /api/setData?path=player:player/control&roles=activate&value={"control":"previous"}&_nocache={timestamp}
```

**Value (URL-encoded):**
```json
{"control": "previous"}
```

#### Player-Daten abrufen

```http
GET /api/getData?path=player:player/data/value&roles=@all&_nocache={timestamp}
```

**Response:**
```json
{
  "value": {
    "playLogicData": {
      "state": "playing",
      "trackRoles": {
        "title": "Song Title",
        "icon": "http://...",
        "mediaData": {
          "metaData": {
            "artist": "Artist Name",
            "album": "Album Name"
          }
        }
      }
    }
  }
}
```

**Mögliche States:**
- `"playing"` - Wiedergabe läuft
- `"paused"` - Pausiert
- `"stopped"` - Gestoppt
- `"transitioning"` - Übergang (sollte ignoriert werden)

### System-Aktionen

#### Neustart

```http
GET /api/setData?path=ui:/settings/system/restart&roles=activate&value={"type":"bool_","bool_":true}&_nocache={timestamp}
```

**Hinweis:** Startet die Soundbar neu.

## API-spezifische Endpunkte

Die folgenden Endpunkte unterscheiden sich zwischen Espresso (Max) und Popcorn (Plus/Mini) APIs:

- [Espresso API (Max) Endpunkte](AMBEO-API-ESPRESSO.md)
- [Popcorn API (Plus/Mini) Endpunkte](AMBEO-API-POPCORN.md)

## HTTP-Methode

**Wichtig:** Alle Anfragen verwenden die **GET**-Methode, auch für Schreiboperationen (`setData`). Dies ist ungewöhnlich für eine REST API, aber so implementiert.

## Fehlerbehandlung

- **Status 200:** Erfolg
- **Status ≠ 200:** Fehler (Details im Response-Body, falls vorhanden)
- **Timeout:** Nach 5 Sekunden (empfohlen)
- **Connection Error:** Gerät nicht erreichbar

## Request-Header

Keine speziellen Header erforderlich. Standard HTTP-Header reichen aus:

```http
GET /api/getData?path=player:volume&roles=@all&_nocache=1703347200000 HTTP/1.1
Host: 192.168.1.100:80
User-Agent: YourClient/1.0
Accept: application/json
```

## Response-Header

Typische Response-Header:

```http
HTTP/1.1 200 OK
Content-Type: application/json
Content-Length: 123
```

## Concurrent Requests

Die API scheint concurrent requests zu unterstützen. Im Home Assistant Modul werden mehrere Anfragen parallel (mit `asyncio.gather`) ausgeführt.

**Beispiel (konzeptionell):**
```
Parallel abrufen:
- Volume
- Mute
- Source
- Preset
- State
- Player Data
```

## Cache-Busting Details

Der `_nocache` Parameter ist kritisch für aktuelle Daten:

```javascript
// JavaScript
const nocache = Date.now();
const url = `/api/getData?path=player:volume&roles=@all&_nocache=${nocache}`;
```

```php
// PHP
$nocache = (int)(microtime(true) * 1000);
$url = "/api/getData?path=player:volume&roles=@all&_nocache={$nocache}";
```

```python
# Python
import time
nocache = int(time.time() * 1000)
url = f"/api/getData?path=player:volume&roles=@all&_nocache={nocache}"
```

## URL-Encoding von Value-Parameter

Der `value` Parameter bei `setData` muss korrekt URL-encoded sein:

**Original:**
```json
{"type":"bool_","bool_":true}
```

**URL-encoded:**
```
%7B%22type%22%3A%22bool_%22%2C%22bool_%22%3Atrue%7D
```

**Beispiel in verschiedenen Sprachen:**

```javascript
// JavaScript
const value = JSON.stringify({type: "bool_", bool_: true});
const encoded = encodeURIComponent(value);
```

```php
// PHP
$value = json_encode(['type' => 'bool_', 'bool_' => true]);
$encoded = urlencode($value);
```

```python
# Python
import json
from urllib.parse import quote
value = json.dumps({"type": "bool_", "bool_": True})
encoded = quote(value)
```

## Wichtige Hinweise

1. **Keine POST-Requests:** Auch Schreiboperationen nutzen GET mit Query-Parametern
2. **Typisierung:** Jeder Wert hat einen expliziten Typ (`bool_`, `i32_`, `string_`, etc.)
3. **Roles:** Meist `@all` zum Lesen, `value` oder `activate` zum Schreiben
4. **Nocache:** Immer verwenden für aktuelle Daten
5. **Paths:** Sind API-spezifisch (Espresso vs. Popcorn)
