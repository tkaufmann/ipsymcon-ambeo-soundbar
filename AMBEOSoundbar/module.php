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

    // Cache for source and preset lists (loaded once at startup)
    private $cachedSources = null;
    private $cachedPresets = null;

    /**
     * Create() - Called when instance is created
     */
    public function Create(): void
    {
        parent::Create();

        // Register properties
        $this->RegisterPropertyString('Host', '');
        $this->RegisterPropertyInteger('UpdateInterval', 10);

        // Register custom input names
        $this->RegisterPropertyString('CustomName_hdmiarc', '');
        $this->RegisterPropertyString('CustomName_hdmi1', '');
        $this->RegisterPropertyString('CustomName_hdmi2', '');
        $this->RegisterPropertyString('CustomName_spdif', '');
        $this->RegisterPropertyString('CustomName_aux', '');
        $this->RegisterPropertyString('CustomName_bluetooth', '');

        // Register timer
        $this->RegisterTimer('UpdateStatus', 0, 'AMB_UpdateStatus($_IPS[\'TARGET\']);');
    }

    /**
     * GetConfigurationForm() - Called when configuration form is opened
     */
    public function GetConfigurationForm(): string
    {
        $jsonForm = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        // Try to get current model from API
        $host = $this->ReadPropertyString('Host');
        if (!empty($host)) {
            $productName = $this->apiGetData('settings:/system/productName');
            if ($productName !== null && isset($productName['value']['string_'])) {
                $model = $productName['value']['string_'];

                // Update DetectedModel label in form
                foreach ($jsonForm['elements'] as &$element) {
                    if (isset($element['name']) && $element['name'] === 'DetectedModel') {
                        $element['label'] = $model;
                        break;
                    }
                }
            }
        }

        return json_encode($jsonForm);
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
        $this->UpdateFormField('DetectedModel', 'label', $model);

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

        // 1. Register variables WITHOUT profiles (profile via RegisterVariable sets
        //    VariableProfile, but Mobile App needs VariableCustomProfile)
        $this->RegisterVariableInteger('Volume', 'Lautstärke', '~Intensity.100', 10);
        $this->RegisterVariableBoolean('Mute', 'Stumm', '~Switch', 20);
        $this->RegisterVariableInteger('Source', 'Eingang', '', 30);
        $this->RegisterVariableInteger('Preset', 'Audio-Preset', '', 40);
        $this->RegisterVariableBoolean('NightMode', 'Nachtmodus', '~Switch', 50);
        $this->RegisterVariableBoolean('AMBEOMode', 'AMBEO Modus', '~Switch', 60);
        $this->RegisterVariableBoolean('VoiceEnhancement', 'Sprachverbesserung', '~Switch', 70);
        $this->RegisterVariableBoolean('SoundFeedback', 'Sound-Feedback', '~Switch', 80);

        // 2. Enable actions for all variables
        $this->EnableAction('Volume');
        $this->EnableAction('Mute');
        $this->EnableAction('Source');
        $this->EnableAction('Preset');
        $this->EnableAction('NightMode');
        $this->EnableAction('AMBEOMode');
        $this->EnableAction('VoiceEnhancement');
        $this->EnableAction('SoundFeedback');

        // 3. Create profiles and set VariableCustomProfile (for Mobile App) +
        //    CustomPresentation (for Desktop WebFront)
        if ($isPopcorn) {
            $this->SetupPopcornPresentations();
        } else {
            $this->SetupEspressoPresentations();
        }

        // 4. Initial status update
        $this->UpdateStatus();
    }

    /**
     * Setup presentations for Popcorn API (Plus/Mini)
     *
     * Creates profiles and sets both VariableCustomProfile (for Mobile App)
     * and CustomPresentation (for Desktop WebFront).
     */
    private function SetupPopcornPresentations(): void
    {
        // Load source list from API
        $this->cachedSources = $this->apiGetRows('ui:/inputs');
        if ($this->cachedSources !== null && isset($this->cachedSources['rows'])) {
            $options = [];
            $index = 0;
            foreach ($this->cachedSources['rows'] as $row) {
                if (isset($row['disabled']) && $row['disabled']) {
                    continue;
                }
                $inputId = $row['id'] ?? '';
                $customName = $this->ReadPropertyString("CustomName_{$inputId}");
                $displayName = !empty($customName) ? $customName : $row['title'];

                $options[] = ['Value' => $index, 'Caption' => $displayName];
                $index++;
            }
            $this->SetupVariablePresentation('Source', $options);
        }

        // Load preset list from API
        $this->cachedPresets = $this->apiGetRows('settings:/popcorn/audio/audioPresetValues');
        if ($this->cachedPresets !== null && isset($this->cachedPresets['rows'])) {
            $options = [];
            $index = 0;
            foreach ($this->cachedPresets['rows'] as $row) {
                $options[] = ['Value' => $index, 'Caption' => $row['title']];
                $index++;
            }
            $this->SetupVariablePresentation('Preset', $options);
        }
    }

    /**
     * Setup presentations for Espresso API (Max)
     */
    private function SetupEspressoPresentations(): void
    {
        $sourceOptions = [
            ['Value' => 0, 'Caption' => 'HDMI 1'],
            ['Value' => 1, 'Caption' => 'HDMI 2'],
            ['Value' => 2, 'Caption' => 'Optical'],
            ['Value' => 3, 'Caption' => 'Bluetooth']
        ];
        $this->SetupVariablePresentation('Source', $sourceOptions);

        $presetOptions = [
            ['Value' => 0, 'Caption' => 'Neutral'],
            ['Value' => 1, 'Caption' => 'Movies'],
            ['Value' => 2, 'Caption' => 'Sport'],
            ['Value' => 3, 'Caption' => 'News'],
            ['Value' => 4, 'Caption' => 'Music']
        ];
        $this->SetupVariablePresentation('Preset', $presetOptions);
    }

    /**
     * Setup variable presentation with Mobile App compatibility
     *
     * Creates a legacy variable profile and assigns it via IPS_SetVariableCustomProfile
     * (sets VariableCustomProfile, which Mobile App needs), plus sets
     * IPS_SetVariableCustomPresentation for Desktop WebFront.
     *
     * IMPORTANT: Order matters! IPS_SetVariableCustomPresentation() clears VariableCustomProfile,
     * so we must call SetCustomPresentation FIRST, then SetVariableCustomProfile.
     */
    private function SetupVariablePresentation(string $ident, array $options): void
    {
        $varID = @$this->GetIDForIdent($ident);
        if ($varID === false || $varID === 0) {
            return;
        }

        $profileName = "AMBEOSoundbar.{$ident}." . $this->InstanceID;

        // 1. Create/update legacy profile
        if (IPS_VariableProfileExists($profileName)) {
            IPS_DeleteVariableProfile($profileName);
        }
        IPS_CreateVariableProfile($profileName, VARIABLETYPE_INTEGER);

        foreach ($options as $option) {
            IPS_SetVariableProfileAssociation(
                $profileName,
                $option['Value'],
                $option['Caption'],
                '',
                -1
            );
        }

        // 2. FIRST set CustomPresentation for Desktop WebFront
        //    (MUST be before SetVariableCustomProfile, because SetCustomPresentation clears CustomProfile!)
        $presentationOptions = array_map(function ($opt) {
            return [
                'Value' => $opt['Value'],
                'Caption' => $opt['Caption'],
                'IconActive' => false,
                'IconValue' => ''
            ];
        }, $options);

        IPS_SetVariableCustomPresentation($varID, [
            'PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION,
            'OPTIONS' => json_encode($presentationOptions)
        ]);

        // 3. THEN assign profile via CustomProfile (sets VariableCustomProfile for Mobile App)
        //    This must come AFTER SetCustomPresentation!
        IPS_SetVariableCustomProfile($varID, $profileName);
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

        // Get current source and preset (using cached lists)
        $currentSource = $this->apiGetData('popcorn:inputChange/selected');
        if ($currentSource !== null && $this->cachedSources !== null) {
            // Map source ID to index using cached list
            $sourceId = $currentSource['value']['popcornInputId'];
            if (isset($this->cachedSources['rows'])) {
                $index = 0;
                foreach ($this->cachedSources['rows'] as $row) {
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
        if ($currentPreset !== null && $this->cachedPresets !== null) {
            // Map preset ID to index using cached list
            $presetId = $currentPreset['value']['popcornAudioPreset'];
            if (isset($this->cachedPresets['rows'])) {
                $index = 0;
                foreach ($this->cachedPresets['rows'] as $row) {
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
        // Get source path from cached list
        if ($this->cachedSources === null || !isset($this->cachedSources['rows'])) {
            return;
        }

        $currentIndex = 0;
        $path = null;
        foreach ($this->cachedSources['rows'] as $row) {
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
        // Get preset ID from cached list
        if ($this->cachedPresets === null || !isset($this->cachedPresets['rows'])) {
            return;
        }

        if (isset($this->cachedPresets['rows'][$index])) {
            $presetId = $this->cachedPresets['rows'][$index]['value']['popcornAudioPreset'];
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
     * Resolve hostname to IP address
     * AMBEO Soundbar requires IP in Host header, not hostname (returns 403 otherwise)
     */
    private function resolveHost(string $host): string
    {
        // If already an IP, return as-is
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $host;
        }

        // Try to resolve hostname to IP
        $ip = gethostbyname($host);

        // gethostbyname returns the input if resolution fails
        if ($ip === $host) {
            $this->SendDebug('ResolveHost', "Failed to resolve hostname: {$host}", 0);
            return $host; // Return original, let connection attempt fail with meaningful error
        }

        $this->SendDebug('ResolveHost', "Resolved {$host} → {$ip}", 0);
        return $ip;
    }

    /**
     * API: getData
     */
    private function apiGetData(string $path, string $role = '@all'): ?array
    {
        $host = $this->resolveHost($this->ReadPropertyString('Host'));
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
        $host = $this->resolveHost($this->ReadPropertyString('Host'));
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
        $host = $this->resolveHost($this->ReadPropertyString('Host'));
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
