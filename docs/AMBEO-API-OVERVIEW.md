# Sennheiser AMBEO Soundbar API - Übersicht

**Reverse Engineering der API basierend auf:** https://github.com/faizpuru/ha-ambeo_soundbar
**Analysedatum:** 2025-12-23
**Analysiert von:** Tim Kaufmann

## Zusammenfassung

Die Sennheiser AMBEO Soundbar verfügt über eine HTTP-basierte REST-ähnliche API, die lokal über das Netzwerk angesprochen werden kann. Es existieren **zwei unterschiedliche API-Implementierungen**, die je nach Soundbar-Modell verwendet werden:

- **Espresso API** - für AMBEO Soundbar Max
- **Popcorn API** - für AMBEO Soundbar Plus und Mini

Beide APIs teilen eine gemeinsame Basis-Architektur, unterscheiden sich aber in Pfaden, Datentypen und verfügbaren Features.

## Unterstützte Geräte

| Modell | API-Implementierung | Codename |
|--------|---------------------|----------|
| AMBEO Soundbar Max | Espresso API | `espresso` |
| AMBEO Soundbar Plus | Popcorn API | `popcorn` |
| AMBEO Soundbar Mini | Popcorn API | `popcorn` |

## API-Basis-Struktur

### Endpoint

```
http://{ip}:{port}/api
```

**Standard-Port:** `80`
**Timeout:** 5 Sekunden (empfohlen)

### Modell-Erkennung

Die API-Factory erkennt automatisch das Modell und wählt die passende Implementierung:

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

Mögliche Werte:
- `"AMBEO Soundbar Max"` → Espresso API
- `"AMBEO Soundbar Plus"` → Popcorn API
- `"AMBEO Soundbar Mini"` → Popcorn API

## Haupt-Funktionen der API

Die API bietet drei Hauptoperationen:

### 1. `getData` - Daten abrufen

Liest einzelne Werte oder komplexe Datenstrukturen aus.

**Format:**
```
GET /api/getData?path={path}&roles={role}&_nocache={timestamp}
```

### 2. `setData` - Daten setzen

Schreibt Werte oder führt Aktionen aus.

**Format:**
```
GET /api/setData?path={path}&roles={role}&value={json_value}&_nocache={timestamp}
```

### 3. `getRows` - Listen/Zeilen abrufen

Liest Array-artige Datenstrukturen (z.B. Quellen, Presets).

**Format:**
```
GET /api/getRows?path={path}&roles={role}&from={start_index}&to={end_index}&_nocache={timestamp}
```

## Gemeinsame Parameter

| Parameter | Beschreibung | Beispiel |
|-----------|--------------|----------|
| `path` | Pfad zur Ressource | `player:volume` |
| `roles` | Rollen-Filter, meist `@all` oder `value` | `@all` |
| `value` | JSON-kodierter Wert (nur bei setData) | `{"type":"bool_","bool_":true}` |
| `from` | Start-Index (nur bei getRows) | `0` |
| `to` | End-Index (nur bei getRows) | `10` |
| `_nocache` | Timestamp zum Cache-Busting | `1703347200000` |

## Datentypen

Die API verwendet verschiedene typisierte Datenformate:

| Typ | Beschreibung | Beispiel |
|-----|--------------|----------|
| `bool_` | Boolean-Wert | `{"type":"bool_","bool_":true}` |
| `i32_` | 32-bit Integer | `{"type":"i32_","i32_":50}` |
| `i16_` | 16-bit Integer | `{"type":"i16_","i16_":5}` |
| `double_` | Double/Float | `{"type":"double_","double_":5.5}` |
| `string_` | String | `{"type":"string_","string_":"Test"}` |
| `powerTarget` | Power-Status (komplex) | `{"target":"online"}` |
| `espressoBrightness` | Brightness-Objekt (Espresso) | `{"ambeologo":100,"display":50}` |
| `popcornInputId` | Input-ID (Popcorn) | String-basiert |
| `popcornAudioPreset` | Audio-Preset (Popcorn) | String-basiert |
| `playLogicData` | Player-Daten | Komplexes Objekt |
| `bluetoothState` | Bluetooth-Status | `{"pairable":true}` |
| `popcornSubwooferList` | Subwoofer-Liste | Array |

## Capabilities (Feature-Matrix)

Nicht alle Features sind auf allen Modellen verfügbar. Die API verwendet ein Capability-System:

### Espresso API (Max) Capabilities

- `STANDBY` - Standby/Wake-Funktionen
- `MAX_LOGO` - Ambeo Logo Helligkeit
- `MAX_DISPLAY` - Display Helligkeit
- `VOICE_ENHANCEMENT_LEVEL` - Sprach-Verbesserungs-Level (stufenlos)
- `CENTER_SPEAKER_LEVEL` - Center-Lautsprecher-Pegel
- `SIDE_FIRING_LEVEL` - Seitliche Lautsprecher-Pegel
- `UP_FIRING_LEVEL` - Nach-oben-gerichtete Lautsprecher-Pegel
- `RESET_EXPERT_SETTINGS` - Experten-Einstellungen zurücksetzen
- `SUBWOOFER` - Subwoofer-Unterstützung

### Popcorn API (Plus/Mini) Capabilities

- `AMBEO_LOGO` - Ambeo Logo (Ein/Aus + Helligkeit)
- `LED_BAR` - LED-Leiste Helligkeit
- `CODEC_LED` - Codec-LED Helligkeit
- `VOICE_ENHANCEMENT_TOGGLE` - Sprach-Verbesserung (An/Aus)
- `BLUETOOTH_PAIRING` - Bluetooth-Pairing-Modus
- `SUBWOOFER` - Subwoofer-Unterstützung
- `ECO_MODE` - Eco-Modus

## Systemdaten

Diese Endpunkte sind auf allen Modellen verfügbar:

| Funktion | Path | Datentyp | Beschreibung |
|----------|------|----------|--------------|
| **Geräte-Name** | `systemmanager:/deviceName` | `string_` | Name des Geräts |
| **Seriennummer** | `settings:/system/serialNumber` | `string_` | Seriennummer |
| **Firmware-Version** | `ui:settings/firmwareUpdate/currentVersion` | `string_` | Aktuelle Firmware |
| **Produktname** | `settings:/system/productName` | `string_` | Modellbezeichnung |
| **Power-Status** | `powermanager:target` | `powerTarget` | Aktueller Power-Status |

## Response-Format

Alle erfolgreichen Anfragen liefern JSON-Responses. Die Struktur variiert je nach Funktion:

### getData Response
```json
{
  "value": {
    "{datatype}": <actual_value>
  }
}
```

### getRows Response
```json
{
  "rows": [
    {
      "id": "...",
      "title": "...",
      "value": { ... }
    }
  ]
}
```

## Fehlerbehandlung

- **HTTP 200**: Erfolg
- **Andere Status-Codes**: Fehler (Client-Error oder Timeout)
- **Fehlende Keys in Response**: Werden als `None` behandelt
- **Timeout**: 5 Sekunden Standard

## Cache-Busting

Alle Anfragen sollten den `_nocache` Parameter mit einem aktuellen Unix-Timestamp in Millisekunden enthalten:

```javascript
const nocache = Date.now(); // JavaScript
```

```php
$nocache = (int)(microtime(true) * 1000); // PHP
```

## Besonderheiten

### Debounce-Modus (nur Espresso API)

Die Espresso API (Max) unterstützt einen Debounce-Modus für Player-Updates, um häufige State-Änderungen zu glätten. Dies ist besonders relevant bei `player:player/data/value`.

### Volume-Schritte

Die verschiedenen Modelle verwenden unterschiedliche Volume-Schritte:

- **Espresso (Max)**: `0.02` (2%)
- **Popcorn (Plus/Mini)**: `0.01` (1%)

### Subwoofer-Bereiche

- **Espresso (Max)**: `-12` bis `+12`
- **Popcorn (Plus/Mini)**: `-10` bis `+10`

## Weitere Dokumentation

- [API-Endpunkte Details](AMBEO-API-ENDPOINTS.md)
- [Espresso API (Max) Spezifikation](AMBEO-API-ESPRESSO.md)
- [Popcorn API (Plus/Mini) Spezifikation](AMBEO-API-POPCORN.md)
- [API-Beispiele und Use Cases](AMBEO-API-EXAMPLES.md)

## Offene Fragen / Unklarheiten

1. **Authentifizierung:** Es scheint keine Authentifizierung zu geben. Alle Anfragen sind unauthentifiziert.
2. **HTTPS:** Unklar, ob HTTPS unterstützt wird. Der Code nutzt nur HTTP.
3. **Rate Limiting:** Keine Hinweise auf Rate Limits gefunden.
4. **WebSocket/SSE:** Keine Hinweise auf Echtzeit-Updates via WebSocket oder Server-Sent Events.
5. **mDNS/Bonjour:** Standard-Hostname ist `ambeo.local`, vermutlich via mDNS/Bonjour.
6. **Firmware-Updates:** Der Pfad `ui:settings/firmwareUpdate/currentVersion` deutet auf ein Firmware-Update-Feature hin, aber die Implementierung ist nicht dokumentiert.
7. **Player Control "activate" Role:** Die genaue Bedeutung der `activate` Role bei `setData` ist unklar (z.B. bei `player:player/control`).

## Disclaimer

Diese Dokumentation basiert auf Reverse Engineering des Home Assistant Moduls. Die tatsächliche API könnte weitere, nicht dokumentierte Features enthalten. Sennheiser stellt keine offizielle API-Dokumentation zur Verfügung.
