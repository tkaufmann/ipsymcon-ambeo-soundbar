# Sennheiser AMBEO Soundbar API - Beispiele & Use Cases

Dieses Dokument enthält praktische Beispiele für die Verwendung der AMBEO Soundbar API in verschiedenen Programmiersprachen.

## PHP 8.4 Beispiele

### Basis HTTP-Client

```php
<?php

declare(strict_types=1);

namespace AmbeoApi;

readonly class AmbeoClient
{
    public function __construct(
        private string $host,
        private int $port = 80,
        private int $timeout = 5
    ) {}

    private function generateNocache(): int
    {
        return (int)(microtime(true) * 1000);
    }

    private function buildUrl(
        string $function,
        string $path,
        ?string $role = null,
        ?string $value = null,
        ?int $from = null,
        ?int $to = null
    ): string {
        $params = [
            'path' => $path,
            '_nocache' => $this->generateNocache(),
        ];

        if ($role !== null) {
            $params['roles'] = $role;
        }

        if ($value !== null) {
            $params['value'] = $value;
        }

        if ($from !== null) {
            $params['from'] = $from;
        }

        if ($to !== null) {
            $params['to'] = $to;
        }

        $query = http_build_query($params);
        return "http://{$this->host}:{$this->port}/api/{$function}?{$query}";
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getData(string $path, string $role = '@all'): ?array
    {
        $url = $this->buildUrl('getData', $path, $role);
        return $this->executeRequest($url);
    }

    public function setData(string $path, string $dataType, mixed $value, string $role = 'value'): bool
    {
        $jsonValue = json_encode([
            'type' => $dataType,
            $dataType => $value
        ], JSON_THROW_ON_ERROR);

        $url = $this->buildUrl('setData', $path, $role, $jsonValue);
        $result = $this->executeRequest($url);

        return $result !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRows(string $path, int $from = 0, int $to = 10, string $role = '@all'): ?array
    {
        $url = $this->buildUrl('getRows', $path, $role, null, $from, $to);
        return $this->executeRequest($url);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function executeRequest(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        try {
            return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractValue(array $data, string $key): mixed
    {
        return $data['value'][$key] ?? null;
    }

    public function getStringValue(string $path): ?string
    {
        $data = $this->getData($path);
        return $data !== null ? $this->extractValue($data, 'string_') : null;
    }

    public function getIntValue(string $path): ?int
    {
        $data = $this->getData($path);
        return $data !== null ? $this->extractValue($data, 'i32_') : null;
    }

    public function getBoolValue(string $path): ?bool
    {
        $data = $this->getData($path);
        return $data !== null ? $this->extractValue($data, 'bool_') : null;
    }
}
```

### Verwendung des Basis-Clients

```php
<?php

use AmbeoApi\AmbeoClient;

// Client initialisieren
$client = new AmbeoClient(host: '192.168.1.100');

// Modell abrufen
$model = $client->getStringValue('settings:/system/productName');
echo "Modell: {$model}\n";

// Lautstärke abrufen
$volume = $client->getIntValue('player:volume');
echo "Aktuelle Lautstärke: {$volume}\n";

// Lautstärke setzen
$client->setData('player:volume', 'i32_', 75);

// Mute-Status prüfen
$isMuted = $client->getBoolValue('settings:/mediaPlayer/mute');
echo "Gemutet: " . ($isMuted ? 'Ja' : 'Nein') . "\n";

// Mute aktivieren
$client->setData('settings:/mediaPlayer/mute', 'bool_', true);
```

### Espresso API (Max) - High-Level Wrapper

```php
<?php

declare(strict_types=1);

namespace AmbeoApi;

readonly class EspressoApi
{
    public function __construct(
        private AmbeoClient $client
    ) {}

    // Night Mode
    public function getNightMode(): ?bool
    {
        return $this->client->getBoolValue('espresso:nightModeUi');
    }

    public function setNightMode(bool $enabled): bool
    {
        return $this->client->setData('espresso:nightModeUi', 'bool_', $enabled);
    }

    // AMBEO Mode
    public function getAmbeoMode(): ?bool
    {
        return $this->client->getBoolValue('espresso:ambeoModeUi');
    }

    public function setAmbeoMode(bool $enabled): bool
    {
        return $this->client->setData('espresso:ambeoModeUi', 'bool_', $enabled);
    }

    // Voice Enhancement Level
    public function getVoiceEnhancementLevel(): ?int
    {
        $data = $this->client->getData('ui:/mydevice/voiceEnhanceLevel');
        return $data['value']['i16_'] ?? null;
    }

    public function setVoiceEnhancementLevel(int $level): bool
    {
        return $this->client->setData('ui:/mydevice/voiceEnhanceLevel', 'i16_', $level);
    }

    // Standby
    public function standby(): bool
    {
        return $this->client->setData('espresso:appRequestedStandby', 'bool_', true);
    }

    public function wake(): bool
    {
        return $this->client->setData('espresso:appRequestedOnline', 'bool_', true);
    }

    // Display & Logo Brightness
    public function getBrightness(): ?array
    {
        $data = $this->client->getData('settings:/espresso/brightnessSensor');
        return $data['value']['espressoBrightness'] ?? null;
    }

    public function setDisplayBrightness(int $brightness): bool
    {
        $current = $this->getBrightness();
        if ($current === null) {
            return false;
        }

        $value = [
            'ambeologo' => $current['ambeologo'],
            'display' => $brightness
        ];

        $jsonValue = json_encode([
            'type' => 'espressoBrightness',
            'espressoBrightness' => $value
        ], JSON_THROW_ON_ERROR);

        $url = "http://{$this->client->host}:{$this->client->port}/api/setData?" . http_build_query([
            'path' => 'settings:/espresso/brightnessSensor',
            'roles' => 'value',
            'value' => $jsonValue,
            '_nocache' => (int)(microtime(true) * 1000)
        ]);

        return file_get_contents($url) !== false;
    }

    public function setLogoBrightness(int $brightness): bool
    {
        $current = $this->getBrightness();
        if ($current === null) {
            return false;
        }

        $value = [
            'ambeologo' => $brightness,
            'display' => $current['display']
        ];

        $jsonValue = json_encode([
            'type' => 'espressoBrightness',
            'espressoBrightness' => $value
        ], JSON_THROW_ON_ERROR);

        $url = "http://{$this->client->host}:{$this->client->port}/api/setData?" . http_build_query([
            'path' => 'settings:/espresso/brightnessSensor',
            'roles' => 'value',
            'value' => $jsonValue,
            '_nocache' => (int)(microtime(true) * 1000)
        ]);

        return file_get_contents($url) !== false;
    }

    // Sources
    /**
     * @return array<int, array{id: int, title: string}>
     */
    public function getSources(): array
    {
        $inputNames = $this->client->getRows('settings:/espresso/inputNames', 0, 20);
        $inputs = $this->client->getRows('espresso:', 0, 20);

        if ($inputNames === null || $inputs === null) {
            return [];
        }

        $inputIndexMap = [];
        foreach ($inputs['rows'] as $index => $input) {
            $inputIndexMap[$input['title']] = $index;
        }

        $sources = [];
        $excludeSources = ['aes'];

        foreach ($inputNames['rows'] as $name) {
            $title = strtolower($name['title']);
            if (!in_array($title, $excludeSources, true)) {
                $sources[] = [
                    'id' => $inputIndexMap[$name['title']],
                    'title' => $name['value']['string_']
                ];
            }
        }

        return $sources;
    }

    public function getCurrentSource(): ?int
    {
        return $this->client->getIntValue('espresso:audioInputID');
    }

    public function setSource(int $sourceId): bool
    {
        return $this->client->setData('espresso:audioInputID', 'i32_', $sourceId);
    }

    // Presets
    /**
     * @return array<int, array{id: int, title: string}>
     */
    public function getPresets(): array
    {
        return [
            ['id' => 0, 'title' => 'Neutral'],
            ['id' => 1, 'title' => 'Movies'],
            ['id' => 2, 'title' => 'Sport'],
            ['id' => 3, 'title' => 'News'],
            ['id' => 4, 'title' => 'Music']
        ];
    }

    public function getCurrentPreset(): ?int
    {
        return $this->client->getIntValue('settings:/espresso/equalizerPreset');
    }

    public function setPreset(int $presetId): bool
    {
        return $this->client->setData('settings:/espresso/equalizerPreset', 'i32_', $presetId);
    }

    // Subwoofer
    public function hasSubwoofer(): bool
    {
        $data = $this->client->getData('ui:/settings/subwoofer/enabled');
        return ($data['modifiable'] ?? false) === true;
    }

    public function getSubwooferStatus(): ?bool
    {
        return $this->client->getBoolValue('ui:/settings/subwoofer/enabled');
    }

    public function setSubwooferStatus(bool $enabled): bool
    {
        return $this->client->setData('ui:/settings/subwoofer/enabled', 'bool_', $enabled);
    }

    public function getSubwooferVolume(): ?int
    {
        $data = $this->client->getData('ui:/settings/subwoofer/volume');
        return $data['value']['i16_'] ?? null;
    }

    public function setSubwooferVolume(int $volume): bool
    {
        if ($volume < -12 || $volume > 12) {
            throw new \InvalidArgumentException('Subwoofer volume must be between -12 and +12');
        }
        return $this->client->setData('ui:/settings/subwoofer/volume', 'i16_', $volume);
    }
}
```

### Popcorn API (Plus/Mini) - High-Level Wrapper

```php
<?php

declare(strict_types=1);

namespace AmbeoApi;

readonly class PopcornApi
{
    public function __construct(
        private AmbeoClient $client
    ) {}

    // Night Mode
    public function getNightMode(): ?bool
    {
        return $this->client->getBoolValue('settings:/popcorn/audio/nightModeStatus');
    }

    public function setNightMode(bool $enabled): bool
    {
        return $this->client->setData('settings:/popcorn/audio/nightModeStatus', 'bool_', $enabled);
    }

    // Voice Enhancement
    public function getVoiceEnhancement(): ?bool
    {
        return $this->client->getBoolValue('settings:/popcorn/audio/voiceEnhancement');
    }

    public function setVoiceEnhancement(bool $enabled): bool
    {
        return $this->client->setData('settings:/popcorn/audio/voiceEnhancement', 'bool_', $enabled);
    }

    // AMBEO Mode
    public function getAmbeoMode(): ?bool
    {
        return $this->client->getBoolValue('settings:/popcorn/audio/ambeoModeStatus');
    }

    public function setAmbeoMode(bool $enabled): bool
    {
        return $this->client->setData('settings:/popcorn/audio/ambeoModeStatus', 'bool_', $enabled);
    }

    // Bluetooth Pairing
    public function getBluetoothPairingState(): ?bool
    {
        $data = $this->client->getData('bluetooth:state');
        return $data['value']['bluetoothState']['pairable'] ?? null;
    }

    public function setBluetoothPairingState(bool $pairable): bool
    {
        return $this->client->setData('bluetooth:deviceList/discoverable', 'bool_', $pairable, 'activate');
    }

    // LED Controls
    public function getLogoState(): ?bool
    {
        return $this->client->getBoolValue('settings:/popcorn/ui/ledStatus');
    }

    public function setLogoState(bool $enabled): bool
    {
        return $this->client->setData('settings:/popcorn/ui/ledStatus', 'bool_', $enabled);
    }

    public function getLogoBrightness(): ?int
    {
        return $this->client->getIntValue('ui:/settings/interface/ambeoSection/brightness');
    }

    public function setLogoBrightness(int $brightness): bool
    {
        return $this->client->setData('ui:/settings/interface/ambeoSection/brightness', 'i32_', $brightness);
    }

    public function getLedBarBrightness(): ?int
    {
        return $this->client->getIntValue('ui:/settings/interface/ledBrightness');
    }

    public function setLedBarBrightness(int $brightness): bool
    {
        return $this->client->setData('ui:/settings/interface/ledBrightness', 'i32_', $brightness);
    }

    public function getCodecLedBrightness(): ?int
    {
        return $this->client->getIntValue('ui:/settings/interface/codecLedBrightness');
    }

    public function setCodecLedBrightness(int $brightness): bool
    {
        return $this->client->setData('ui:/settings/interface/codecLedBrightness', 'i32_', $brightness);
    }

    // Sources
    /**
     * @return array<int, array{id: string, title: string}>
     */
    public function getSources(): array
    {
        $data = $this->client->getRows('ui:/inputs', 0, 10);

        if ($data === null) {
            return [];
        }

        $sources = $data['rows'] ?? [];

        // Add virtual sources
        $sources[] = ['id' => 'googlecast', 'title' => 'Google Cast'];
        $sources[] = ['id' => 'airplay', 'title' => 'AirPlay'];

        return $sources;
    }

    public function getCurrentSource(): ?string
    {
        $data = $this->client->getData('popcorn:inputChange/selected');
        return $data['value']['popcornInputId'] ?? null;
    }

    public function setSource(string $sourceId): bool
    {
        $jsonValue = json_encode(['type' => 'bool_', 'bool_' => true], JSON_THROW_ON_ERROR);

        $url = "http://{$this->client->host}:{$this->client->port}/api/setData?" . http_build_query([
            'path' => "ui:/inputs/{$sourceId}",
            'roles' => 'activate',
            'value' => $jsonValue,
            '_nocache' => (int)(microtime(true) * 1000)
        ]);

        return file_get_contents($url) !== false;
    }

    // Presets
    /**
     * @return array<int, array{id: string, title: string}>
     */
    public function getPresets(): array
    {
        $data = $this->client->getRows('settings:/popcorn/audio/audioPresetValues', 0, 10);

        if ($data === null) {
            return [];
        }

        $presets = [];
        foreach ($data['rows'] as $row) {
            $presets[] = [
                'id' => $row['value']['popcornAudioPreset'],
                'title' => $row['title']
            ];
        }

        return $presets;
    }

    public function getCurrentPreset(): ?string
    {
        $data = $this->client->getData('settings:/popcorn/audio/audioPresets/audioPreset');
        return $data['value']['popcornAudioPreset'] ?? null;
    }

    public function setPreset(string $presetId): bool
    {
        $jsonValue = json_encode([
            'type' => 'popcornAudioPreset',
            'popcornAudioPreset' => $presetId
        ], JSON_THROW_ON_ERROR);

        $url = "http://{$this->client->host}:{$this->client->port}/api/setData?" . http_build_query([
            'path' => 'settings:/popcorn/audio/audioPresets/audioPreset',
            'roles' => 'value',
            'value' => $jsonValue,
            '_nocache' => (int)(microtime(true) * 1000)
        ]);

        return file_get_contents($url) !== false;
    }

    // Subwoofer
    public function hasSubwoofer(): bool
    {
        $data = $this->client->getData('settings:/popcorn/subwoofer/list');
        $list = $data['value']['popcornSubwooferList'] ?? [];
        return count($list) > 0;
    }

    public function getSubwooferStatus(): ?bool
    {
        return $this->client->getBoolValue('ui:/settings/subwoofer/enabled');
    }

    public function setSubwooferStatus(bool $enabled): bool
    {
        return $this->client->setData('ui:/settings/subwoofer/enabled', 'bool_', $enabled);
    }

    public function getSubwooferVolume(): ?float
    {
        $data = $this->client->getData('ui:/settings/subwoofer/volume');
        return $data['value']['double_'] ?? null;
    }

    public function setSubwooferVolume(float $volume): bool
    {
        if ($volume < -10.0 || $volume > 10.0) {
            throw new \InvalidArgumentException('Subwoofer volume must be between -10.0 and +10.0');
        }
        return $this->client->setData('ui:/settings/subwoofer/volume', 'double_', $volume);
    }

    // Eco Mode
    public function getEcoMode(): ?bool
    {
        return $this->client->getBoolValue('uipopcorn:ecoModeState');
    }
}
```

### Auto-Detection und Factory

```php
<?php

declare(strict_types=1);

namespace AmbeoApi;

readonly class AmbeoApiFactory
{
    public static function create(string $host, int $port = 80): EspressoApi|PopcornApi
    {
        $client = new AmbeoClient($host, $port);

        $model = $client->getStringValue('settings:/system/productName');

        return match ($model) {
            'AMBEO Soundbar Max' => new EspressoApi($client),
            'AMBEO Soundbar Plus', 'AMBEO Soundbar Mini' => new PopcornApi($client),
            default => throw new \RuntimeException("Unsupported model: {$model}")
        };
    }
}
```

### Verwendungsbeispiel

```php
<?php

use AmbeoApi\AmbeoApiFactory;

// Auto-detect und API erstellen
$api = AmbeoApiFactory::create('192.168.1.100');

// Funktioniert mit beiden API-Typen
$api->setNightMode(true);
$api->setAmbeoMode(true);

// Espresso-spezifisch (nur wenn Max)
if ($api instanceof \AmbeoApi\EspressoApi) {
    $api->setVoiceEnhancementLevel(7);
    $api->setDisplayBrightness(80);
}

// Popcorn-spezifisch (nur wenn Plus/Mini)
if ($api instanceof \AmbeoApi\PopcornApi) {
    $api->setVoiceEnhancement(true);
    $api->setLedBarBrightness(50);
    $api->setBluetoothPairingState(true);
}
```

## Use Cases

### 1. Kino-Modus aktivieren

```php
<?php

function activateCinemaMode(EspressoApi|PopcornApi $api): void
{
    // Audio-Einstellungen
    $api->setPreset($api instanceof EspressoApi ? 1 : 'movies'); // Movies
    $api->setAmbeoMode(true); // 3D Sound aktivieren

    // Display dimmen
    if ($api instanceof EspressoApi) {
        $api->setDisplayBrightness(10); // Sehr dunkel
        $api->setLogoBrightness(10);
    } else {
        $api->setLogoBrightness(10);
        $api->setLedBarBrightness(0); // LED Bar aus
        $api->setCodecLedBrightness(10); // Codec LED sehr dunkel
    }

    echo "Kino-Modus aktiviert!\n";
}
```

### 2. Nacht-Modus für spätes Fernsehen

```php
<?php

function activateNightMode(EspressoApi|PopcornApi $api): void
{
    $api->setNightMode(true); // Dynamikbereich reduzieren
    $api->setVoiceEnhancement(true); // Sprache verstärken

    // Subwoofer runterregeln
    if ($api->hasSubwoofer()) {
        $currentVolume = $api->getSubwooferVolume();
        if ($currentVolume !== null) {
            $newVolume = max($currentVolume - 5, -10);
            $api->setSubwooferVolume($newVolume);
        }
    }

    echo "Nacht-Modus aktiviert - Nachbarn werden es danken!\n";
}
```

### 3. Status-Monitor

```php
<?php

function displayStatus(AmbeoClient $client): void
{
    echo "=== AMBEO Soundbar Status ===\n\n";

    echo "Modell: " . $client->getStringValue('settings:/system/productName') . "\n";
    echo "Firmware: " . $client->getStringValue('ui:settings/firmwareUpdate/currentVersion') . "\n";
    echo "Seriennummer: " . $client->getStringValue('settings:/system/serialNumber') . "\n\n";

    $volume = $client->getIntValue('player:volume');
    $muted = $client->getBoolValue('settings:/mediaPlayer/mute');

    echo "Lautstärke: {$volume}%" . ($muted ? " (GEMUTET)" : "") . "\n";

    $powerStatus = $client->getData('powermanager:target');
    echo "Power-Status: " . ($powerStatus['value']['powerTarget']['target'] ?? 'unknown') . "\n";
}
```

## Fehlerbehandlung

```php
<?php

use AmbeoApi\AmbeoClient;

function safeApiCall(callable $apiCall, string $operation): bool
{
    try {
        $result = $apiCall();
        if ($result === null || $result === false) {
            error_log("API-Aufruf fehlgeschlagen: {$operation}");
            return false;
        }
        return true;
    } catch (\Throwable $e) {
        error_log("Fehler bei {$operation}: " . $e->getMessage());
        return false;
    }
}

// Verwendung
$client = new AmbeoClient('192.168.1.100');

if (safeApiCall(fn() => $client->setData('player:volume', 'i32_', 50), 'Lautstärke setzen')) {
    echo "Lautstärke erfolgreich gesetzt!\n";
}
```

## Performance-Optimierung: Parallele Requests

```php
<?php

// Für PHP müsste man curl_multi verwenden oder async libraries wie Amp/ReactPHP
// Hier ein vereinfachtes Beispiel-Konzept:

function getAllStatusParallel(AmbeoClient $client): array
{
    // In der Praxis würde man hier curl_multi oder Guzzle mit concurrent requests nutzen
    $urls = [
        'volume' => $client->buildUrl('getData', 'player:volume', '@all'),
        'mute' => $client->buildUrl('getData', 'settings:/mediaPlayer/mute', '@all'),
        'source' => $client->buildUrl('getData', 'espresso:audioInputID', '@all'),
        'preset' => $client->buildUrl('getData', 'settings:/espresso/equalizerPreset', '@all'),
    ];

    // ... curl_multi Implementierung ...

    return []; // Platzhalter
}
```

## Zusammenfassung

Diese PHP-Beispiele zeigen:

1. ✅ Type-safe API-Clients mit PHP 8.4
2. ✅ Separate Implementierungen für Espresso und Popcorn
3. ✅ Auto-Detection des Modells
4. ✅ Praktische Use Cases
5. ✅ Fehlerbehandlung
6. ✅ Klare Typisierung und Dokumentation

Die API kann damit vollständig in PHP-Projekten verwendet werden!
