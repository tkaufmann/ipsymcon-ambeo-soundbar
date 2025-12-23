<?php

declare(strict_types=1);

/**
 * AMBEO Soundbar Module
 *
 * IP-Symcon module for controlling Sennheiser AMBEO Soundbars
 * Supports: AMBEO Soundbar Plus, Mini, Max
 *
 * @author Tim Kaufmann
 * @version 1.0
 */
class AMBEOSoundbar extends IPSModuleStrict
{
    private const API_PORT = 80;
    private const API_TIMEOUT = 5;

    /**
     * Create() - Called when instance is created
     */
    public function Create(): void
    {
        parent::Create();

        // Register properties
        $this->RegisterPropertyString('Host', '');
        $this->RegisterPropertyInteger('UpdateInterval', 5);

        // Register timer
        $this->RegisterTimer('UpdateStatus', 0, 'AMB_UpdateStatus($_IPS[\'TARGET\']);');
    }

    /**
     * ApplyChanges() - Called when configuration changes
     */
    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        // Validate configuration
        $host = $this->ReadPropertyString('Host');
        if (empty($host)) {
            $this->SetStatus(200); // Host not configured
            $this->SetTimerInterval('UpdateStatus', 0); // Stop timer
            return;
        }

        // Try to connect and detect model
        $productName = $this->apiGetData('settings:/system/productName');
        if ($productName === null) {
            $this->SetStatus(201); // Connection failed
            $this->SetTimerInterval('UpdateStatus', 0);
            return;
        }

        $model = $productName['value']['string_'] ?? 'Unknown';
        $this->SendDebug('AMBEO', "Detected model: {$model}", 0);

        // Store detected model for form display
        $this->UpdateFormField('DetectedModel', 'caption', $model);

        // Initialize module based on model
        $this->InitializeModule($model);

        // Start timer
        $interval = $this->ReadPropertyInteger('UpdateInterval');
        $this->SetTimerInterval('UpdateStatus', $interval * 1000);

        $this->SetStatus(102); // Active
    }

    /**
     * Initialize module - create variables and presentations
     */
    private function InitializeModule(string $model): void
    {
        $isPopcorn = str_contains($model, 'Plus') || str_contains($model, 'Mini');

        // Register variables
        $this->RegisterVariableInteger('Volume', 'Lautstärke', '~Intensity.100', 10);
        $this->RegisterVariableBoolean('Mute', 'Stumm', '~Switch', 20);
        $this->RegisterVariableInteger('Source', 'Eingang', '', 30);
        $this->RegisterVariableInteger('Preset', 'Audio-Preset', '', 40);
        $this->RegisterVariableBoolean('NightMode', 'Nachtmodus', '~Switch', 50);
        $this->RegisterVariableBoolean('AMBEOMode', 'AMBEO Modus', '~Switch', 60);
        $this->RegisterVariableBoolean('VoiceEnhancement', 'Sprachverbesserung', '~Switch', 70);
        $this->RegisterVariableBoolean('SoundFeedback', 'Sound-Feedback', '~Switch', 80);

        // Enable actions for all variables
        $this->EnableAction('Volume');
        $this->EnableAction('Mute');
        $this->EnableAction('Source');
        $this->EnableAction('Preset');
        $this->EnableAction('NightMode');
        $this->EnableAction('AMBEOMode');
        $this->EnableAction('VoiceEnhancement');
        $this->EnableAction('SoundFeedback');

        // Create variable presentations (dropdowns for Source and Preset)
        if ($isPopcorn) {
            $this->CreatePopcornPresentations();
        } else {
            $this->CreateEspressoPresentations();
        }

        // Initial status update
        $this->UpdateStatus();
    }

    /**
     * Create variable presentations for Popcorn API (Plus/Mini)
     */
    private function CreatePopcornPresentations(): void
    {
        // Get available sources
        $sources = $this->apiGetRows('ui:/inputs');
        if ($sources !== null && isset($sources['rows'])) {
            $options = [];
            $index = 0;
            foreach ($sources['rows'] as $row) {
                if (isset($row['disabled']) && $row['disabled']) {
                    continue; // Skip disabled sources (e.g., Spotify)
                }
                $options[] = [
                    'Value' => $index,
                    'Caption' => $row['title'],
                    'IconActive' => false,
                    'IconValue' => ''
                ];
                $index++;
            }

            $this->SetVariablePresentation('Source', VARIABLE_PRESENTATION_ENUMERATION, $options);
        }

        // Get available presets
        $presets = $this->apiGetRows('settings:/popcorn/audio/audioPresetValues');
        if ($presets !== null && isset($presets['rows'])) {
            $options = [];
            $index = 0;
            foreach ($presets['rows'] as $row) {
                $options[] = [
                    'Value' => $index,
                    'Caption' => $row['title'],
                    'IconActive' => false,
                    'IconValue' => ''
                ];
                $index++;
            }

            $this->SetVariablePresentation('Preset', VARIABLE_PRESENTATION_ENUMERATION, $options);
        }
    }

    /**
     * Create variable presentations for Espresso API (Max)
     */
    private function CreateEspressoPresentations(): void
    {
        // Espresso uses integer IDs for sources
        // For now, use placeholders - would need actual getRows implementation
        $options = [
            ['Value' => 0, 'Caption' => 'HDMI 1', 'IconActive' => false, 'IconValue' => ''],
            ['Value' => 1, 'Caption' => 'HDMI 2', 'IconActive' => false, 'IconValue' => ''],
            ['Value' => 2, 'Caption' => 'Optical', 'IconActive' => false, 'IconValue' => ''],
            ['Value' => 3, 'Caption' => 'Bluetooth', 'IconActive' => false, 'IconValue' => '']
        ];
        $this->SetVariablePresentation('Source', VARIABLE_PRESENTATION_ENUMERATION, $options);

        // Espresso presets (hardcoded)
        $options = [
            ['Value' => 0, 'Caption' => 'Neutral', 'IconActive' => false, 'IconValue' => ''],
            ['Value' => 1, 'Caption' => 'Movies', 'IconActive' => false, 'IconValue' => ''],
            ['Value' => 2, 'Caption' => 'Sport', 'IconActive' => false, 'IconValue' => ''],
            ['Value' => 3, 'Caption' => 'News', 'IconActive' => false, 'IconValue' => ''],
            ['Value' => 4, 'Caption' => 'Music', 'IconActive' => false, 'IconValue' => '']
        ];
        $this->SetVariablePresentation('Preset', VARIABLE_PRESENTATION_ENUMERATION, $options);
    }

    /**
     * Set variable custom presentation
     */
    private function SetVariablePresentation(string $ident, string $presentation, array $options): void
    {
        $varID = @$this->GetIDForIdent($ident);

        if ($varID === false || $varID === 0) {
            $this->SendDebug('SetVariablePresentation', "Variable '{$ident}' not found", 0);
            return;
        }

        // IPS_SetVariableCustomPresentation expects:
        // - PRESENTATION as GUID constant
        // - OPTIONS as JSON-encoded string
        $presentationConfig = [
            'PRESENTATION' => $presentation,
            'OPTIONS' => json_encode($options)
        ];

        IPS_SetVariableCustomPresentation($varID, $presentationConfig);
    }

    /**
     * RequestAction - Called when variable is changed from WebFront
     */
    public function RequestAction($Ident, $Value): void
    {
        $this->SendDebug('RequestAction', "Ident: {$Ident}, Value: {$Value}", 0);

        switch ($Ident) {
            case 'Volume':
                $this->SetVolume((int)$Value);
                break;

            case 'Mute':
                $this->SetMute((bool)$Value);
                break;

            case 'Source':
                $this->SetSource((int)$Value);
                break;

            case 'Preset':
                $this->SetPreset((int)$Value);
                break;

            case 'NightMode':
                $this->SetNightMode((bool)$Value);
                break;

            case 'AMBEOMode':
                $this->SetAMBEOMode((bool)$Value);
                break;

            case 'VoiceEnhancement':
                $this->SetVoiceEnhancement((bool)$Value);
                break;

            case 'SoundFeedback':
                $this->SetSoundFeedback((bool)$Value);
                break;

            default:
                $this->SendDebug('RequestAction', "Unknown ident: {$Ident}", 0);
        }
    }

    /**
     * Public method: Update status from timer or manual call
     */
    public function UpdateStatus(): void
    {
        // Get current values from soundbar
        $volume = $this->apiGetData('player:volume');
        if ($volume !== null) {
            $this->SetValue('Volume', $volume['value']['i32_']);
        }

        $mute = $this->apiGetData('settings:/mediaPlayer/mute');
        if ($mute !== null) {
            $this->SetValue('Mute', $mute['value']['bool_']);
        }

        // Popcorn-specific paths
        $nightMode = $this->apiGetData('settings:/popcorn/audio/nightModeStatus');
        if ($nightMode !== null) {
            $this->SetValue('NightMode', $nightMode['value']['bool_']);
        }

        $ambeoMode = $this->apiGetData('settings:/popcorn/audio/ambeoModeStatus');
        if ($ambeoMode !== null) {
            $this->SetValue('AMBEOMode', $ambeoMode['value']['bool_']);
        }

        $voiceEnhancement = $this->apiGetData('settings:/popcorn/audio/voiceEnhancement');
        if ($voiceEnhancement !== null) {
            $this->SetValue('VoiceEnhancement', $voiceEnhancement['value']['bool_']);
        }

        $soundFeedback = $this->apiGetData('settings:/popcorn/ux/soundFeedbackStatus');
        if ($soundFeedback !== null) {
            $this->SetValue('SoundFeedback', $soundFeedback['value']['bool_']);
        }

        // Get current source and preset
        $currentSource = $this->apiGetData('popcorn:inputChange/selected');
        if ($currentSource !== null) {
            // Map source ID to index
            $sourceId = $currentSource['value']['popcornInputId'];
            $sources = $this->apiGetRows('ui:/inputs');
            if ($sources !== null && isset($sources['rows'])) {
                $index = 0;
                foreach ($sources['rows'] as $row) {
                    if (isset($row['disabled']) && $row['disabled']) {
                        continue;
                    }
                    if ($row['id'] === $sourceId) {
                        $this->SetValue('Source', $index);
                        break;
                    }
                    $index++;
                }
            }
        }

        $currentPreset = $this->apiGetData('settings:/popcorn/audio/audioPresets/audioPreset');
        if ($currentPreset !== null) {
            // Map preset ID to index
            $presetId = $currentPreset['value']['popcornAudioPreset'];
            $presets = $this->apiGetRows('settings:/popcorn/audio/audioPresetValues');
            if ($presets !== null && isset($presets['rows'])) {
                $index = 0;
                foreach ($presets['rows'] as $row) {
                    if ($row['value']['popcornAudioPreset'] === $presetId) {
                        $this->SetValue('Preset', $index);
                        break;
                    }
                    $index++;
                }
            }
        }
    }

    /**
     * Set volume
     */
    private function SetVolume(int $volume): void
    {
        $success = $this->apiSetData('player:volume', 'i32_', $volume);
        if ($success) {
            $this->SetValue('Volume', $volume);
        }
    }

    /**
     * Set mute
     */
    private function SetMute(bool $mute): void
    {
        $success = $this->apiSetData('settings:/mediaPlayer/mute', 'bool_', $mute);
        if ($success) {
            $this->SetValue('Mute', $mute);
        }
    }

    /**
     * Set source
     */
    private function SetSource(int $index): void
    {
        // Get source path from index
        $sources = $this->apiGetRows('ui:/inputs');
        if ($sources === null || !isset($sources['rows'])) {
            return;
        }

        $currentIndex = 0;
        $path = null;
        foreach ($sources['rows'] as $row) {
            if (isset($row['disabled']) && $row['disabled']) {
                continue;
            }
            if ($currentIndex === $index) {
                $path = $row['path'];
                break;
            }
            $currentIndex++;
        }

        if ($path !== null) {
            // Use activate role for source switching
            $this->apiSetData($path, 'bool_', true, 'activate');
            sleep(1); // Wait for soundbar to process
            $this->SetValue('Source', $index);
        }
    }

    /**
     * Set preset
     */
    private function SetPreset(int $index): void
    {
        // Get preset ID from index
        $presets = $this->apiGetRows('settings:/popcorn/audio/audioPresetValues');
        if ($presets === null || !isset($presets['rows'])) {
            return;
        }

        if (isset($presets['rows'][$index])) {
            $presetId = $presets['rows'][$index]['value']['popcornAudioPreset'];
            $success = $this->apiSetData('settings:/popcorn/audio/audioPresets/audioPreset', 'popcornAudioPreset', $presetId);
            if ($success) {
                $this->SetValue('Preset', $index);
            }
        }
    }

    /**
     * Set night mode
     */
    private function SetNightMode(bool $enabled): void
    {
        $success = $this->apiSetData('settings:/popcorn/audio/nightModeStatus', 'bool_', $enabled);
        if ($success) {
            $this->SetValue('NightMode', $enabled);
        }
    }

    /**
     * Set AMBEO mode
     */
    private function SetAMBEOMode(bool $enabled): void
    {
        $success = $this->apiSetData('settings:/popcorn/audio/ambeoModeStatus', 'bool_', $enabled);
        if ($success) {
            $this->SetValue('AMBEOMode', $enabled);
        }
    }

    /**
     * Set voice enhancement
     */
    private function SetVoiceEnhancement(bool $enabled): void
    {
        $success = $this->apiSetData('settings:/popcorn/audio/voiceEnhancement', 'bool_', $enabled);
        if ($success) {
            $this->SetValue('VoiceEnhancement', $enabled);
        }
    }

    /**
     * Set sound feedback
     */
    private function SetSoundFeedback(bool $enabled): void
    {
        $success = $this->apiSetData('settings:/popcorn/ux/soundFeedbackStatus', 'bool_', $enabled);
        if ($success) {
            $this->SetValue('SoundFeedback', $enabled);
        }
    }

    // ==================== API Methods ====================

    /**
     * API: getData
     */
    private function apiGetData(string $path, string $role = '@all'): ?array
    {
        $host = $this->ReadPropertyString('Host');
        $nocache = (int)(microtime(true) * 1000);

        $url = "http://{$host}:" . self::API_PORT . "/api/getData?path=" . urlencode($path) . "&roles=" . urlencode($role) . "&_nocache={$nocache}";

        $response = $this->httpGet($url);
        if ($response === null) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || isset($data['error'])) {
            return null;
        }

        return $data;
    }

    /**
     * API: getRows
     */
    private function apiGetRows(string $path, int $from = 0, int $to = 20, string $role = '@all'): ?array
    {
        $host = $this->ReadPropertyString('Host');
        $nocache = (int)(microtime(true) * 1000);

        $url = "http://{$host}:" . self::API_PORT . "/api/getRows?path=" . urlencode($path) . "&roles=" . urlencode($role) . "&from={$from}&to={$to}&_nocache={$nocache}";

        $response = $this->httpGet($url);
        if ($response === null) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || isset($data['error'])) {
            return null;
        }

        return $data;
    }

    /**
     * API: setData
     */
    private function apiSetData(string $path, string $dataType, mixed $value, string $role = 'value'): bool
    {
        $host = $this->ReadPropertyString('Host');
        $nocache = (int)(microtime(true) * 1000);

        $payload = [
            'type' => $dataType,
            $dataType => $value
        ];

        $url = "http://{$host}:" . self::API_PORT . "/api/setData?path=" . urlencode($path) . "&roles=" . urlencode($role) . "&value=" . urlencode(json_encode($payload)) . "&_nocache={$nocache}";

        $response = $this->httpGet($url);

        // For activate role, response might be null but action still succeeded
        if ($role === 'activate') {
            return true; // Assume success for activate actions
        }

        return $response !== null;
    }

    /**
     * HTTP GET request
     */
    private function httpGet(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => self::API_TIMEOUT,
                'ignore_errors' => true
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $this->SendDebug('HTTP', "Request failed: {$url}", 0);
            return null;
        }

        return $response;
    }
}
