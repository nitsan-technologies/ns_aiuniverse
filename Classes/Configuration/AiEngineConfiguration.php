<?php

declare(strict_types=1);

namespace NITSAN\NsAiUniverse\Configuration;

use NITSAN\NsAiUniverse\Utility\AiUniverseUtilityHelper;

final class AiEngineConfiguration
{
    private array $textGenerationAIEngines = [
      'openai' => [
        'gpt-5',
        'gpt-4.1',
        'gpt-4o',
        'gpt-4',
        'gpt-3.5-turbo',
      ],
      'claude' => [
        'claude-opus-4-6',
        'claude-sonnet-4-6',
        'claude-haiku-4-5',
      ],
      'gemini' => [
        'gemini-1.5-pro',
        'gemini-1.5-flash',
        'gemini-2.0-flash',
        'gemini-2.0-flash-lite',
        'gemini-2.0-pro-exp',
      ],
      'mistral' => [
        'mistral-large-latest',
      ],
    ];

    private string $extensionKey;

    public function __construct(string $extensionKey = 'ns_aiuniverse')
    {
        $this->extensionKey = $extensionKey;

        $extConf = AiUniverseUtilityHelper::getExtensionConf($this->extensionKey);
        if (isset($extConf['enable_custom_llm_model'])) {
            $this->textGenerationAIEngines['customllm'] = $this->getCustomLlmDomain();
        }
    }

    private function getCustomLlmDomain(): array
    {
        return [];
    }

    public function getTextGenerationAIEngines(bool $ignoreConfig = false): array
    {
        return $this->filterEnginesByConfig($this->textGenerationAIEngines, false, $ignoreConfig);
    }

    /**
     * Filters the given AI engines based on the extension configuration.
     *
     * @param array $engines
     * @param bool $useValueAsKey If true, the array value is used as the map key instead of the array key.
     * @return array
     */
    private function filterEnginesByConfig(array $engines, bool $useValueAsKey = false, bool $ignoreConfig = false): array
    {
        $extensionConfiguration = AiUniverseUtilityHelper::getExtensionConf($this->extensionKey);
        $mappedEngines = $this->mapEngines();
        if ($ignoreConfig) {
            return $engines;
        }
        foreach ($engines as $key => $value) {
            $mapKey = $useValueAsKey ? $value : $key;
            $configKey = $mappedEngines[$mapKey] ?? null;

            // Only filter if there's a mapping and the config value is empty
            if ($configKey !== null && empty($extensionConfiguration[$configKey])) {
                unset($engines[$key]);
            }
        }

        return $engines;
    }

    /**
     * Map the engines to the extension configuration keys
     * @return array
     */
    private function mapEngines(): array
    {
        return [
            'openai' => 'openai_api_key',
            'claude' => 'anthropic_api_key',
            'gemini' => 'gemini_api_key',
            'mistral' => 'mistral_api_key',
            'customllm' => 'custom_llm_api_key',
        ];
    }

    public function getAllAIEngines(bool $ignoreConfig = false): array
    {
        return $this->getTextGenerationAIEngines($ignoreConfig);
    }

    /**
     * Customize for usage in AI Log module
     * @return array
     */
    public function getAllEngines(): array
    {
        $aiEngines = [];
        foreach ($this->textGenerationAIEngines as $key => $textModule) {
            $aiEngines[$key] = $key;
        }
        return $aiEngines;
    }

    /**
     * Customize for the usage of AI Log Module
     */
    public function getTextGenerateEngines(): array
    {
        $aiEngines = [
          'mainModules' => [],
          'allModules' => $this->textGenerationAIEngines,
        ];
        $mainModules = [];
        foreach ($this->textGenerationAIEngines as $key => $engines) {
            $mainModules[$key] = $key;
        }
        array_push($aiEngines['mainModules'], $mainModules);
        return $aiEngines;
    }
}
