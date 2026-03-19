<?php

declare(strict_types=1);

namespace NITSAN\NsAiUniverse\Helper;

/**
 * AiUniverseChartHelper
 *
 * Helper class for processing AI usage statistics and generating chart configurations
 */
class AiUniverseChartHelper
{
    public const BACKGROUND_COLORS = [
        'rgba(255, 135, 0, 0.9)',
        'rgba(255, 154, 51, 0.9)',
        'rgba(255, 178, 102, 0.9)',
        'rgba(231, 111, 0, 0.9)',
        'rgba(204, 109, 0, 0.9)',
        'rgba(184, 115, 51, 0.9)',
        'rgba(217, 100, 0, 0.9)',
        'rgba(255, 127, 63, 0.9)',
        'rgba(183, 65, 14, 0.9)',
    ];

    public const BORDER_COLORS = [
        'rgba(255, 135, 0, 1)',
        'rgba(255, 154, 51, 1)',
        'rgba(255, 178, 102, 1)',
        'rgba(231, 111, 0, 1)',
        'rgba(204, 109, 0, 1)',
        'rgba(184, 115, 51, 1)',
        'rgba(217, 100, 0, 1)',
        'rgba(255, 127, 63, 1)',
        'rgba(183, 65, 14, 1)',
    ];

    /**
     * Process OpenAI usage data for chart display
     *
     * @param array $apiData Raw API response data
     * @return array Processed data with chart-ready format
     */
    public function processOpenAiUsageData(array $apiData): array
    {
        $numberOfRequestData = $contextTokenData = $generatedTokenData = [];
        $totalRequests = $totalTokens = 0;

        if (!isset($apiData['data']) || !is_array($apiData['data'])) {
            return [
                'numberOfRequestData' => [],
                'contextTokenData' => [],
                'generatedTokenData' => [],
                'totalTokenData' => [],
                'totalRequests' => 0,
                'totalTokens' => 0
            ];
        }

        foreach ($apiData['data'] as $bucket) {
            if (!isset($bucket['results']) || !is_array($bucket['results'])) {
                continue;
            }

            foreach ($bucket['results'] as $result) {
                $modelId = (string)($result['model'] ?? '');
                if ($modelId === '') {
                    continue;
                }

                $numRequests = (int)($result['num_model_requests'] ?? 0);
                $inputTokens = (int)($result['input_tokens'] ?? 0);
                $outputTokens = (int)($result['output_tokens'] ?? 0);

                if ($numRequests === 0 && $inputTokens === 0 && $outputTokens === 0) {
                    continue;
                }

                // Aggregate by model using null coalescing for cleaner code
                $numberOfRequestData[$modelId] = ($numberOfRequestData[$modelId] ?? 0) + $numRequests;
                $contextTokenData[$modelId] = ($contextTokenData[$modelId] ?? 0) + $inputTokens;
                $generatedTokenData[$modelId] = ($generatedTokenData[$modelId] ?? 0) + $outputTokens;
                $totalRequests += $numRequests;
            }
        }

        // Calculate total tokens in single pass
        $totalTokenData = [];
        foreach ($contextTokenData as $key => $contextData) {
            $totalTokenData[$key] = $contextData + ($generatedTokenData[$key] ?? 0);
            $totalTokens += $totalTokenData[$key];
        }

        return [
            'numberOfRequestData' => $numberOfRequestData,
            'contextTokenData' => $contextTokenData,
            'generatedTokenData' => $generatedTokenData,
            'totalTokenData' => $totalTokenData,
            'totalRequests' => $totalRequests,
            'totalTokens' => $totalTokens
        ];
    }

    /**
     * Get chart configuration for Chart.js
     *
     * @param array $configData Data to display in chart
     * @param string $chartType Chart type (bar, doughnut, line, etc.)
     * @param string $title Chart title
     * @param array $options Additional chart options
     * @return string JSON encoded chart configuration
     */
    public function getChartConfig(
        array $configData,
        string $chartType,
        string $title,
        array $options = []
    ): string {
        if (empty($configData)) {
            $configData = ['No Data' => 0];
        }

        arsort($configData);

        $labels = [];
        $values = [];
        $colors = [];
        $borderColors = [];
        $bgColorCount = count(self::BACKGROUND_COLORS);
        $borderColorCount = count(self::BORDER_COLORS);

        foreach ($configData as $key => $data) {
            $labels[] = $this->formatModelLabel($key);
            $values[] = $data;

            $colorIndex = count($labels) - 1;
            $colors[] = self::BACKGROUND_COLORS[$colorIndex % $bgColorCount];
            $borderColors[] = self::BORDER_COLORS[$colorIndex % $borderColorCount];
        }

        $defaultOptions = [
            'responsive' => true,
            'maintainAspectRatio' => true,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => $chartType === 'doughnut' ? 'bottom' : 'top',
                    'labels' => [
                        'padding' => 15,
                        'usePointStyle' => true,
                        'font' => [
                            'size' => 11
                        ],
                        'boxWidth' => 12,
                        'boxHeight' => 12
                    ]
                ],
                'tooltip' => [
                    'enabled' => true,
                    'padding' => 10,
                    'displayColors' => true,
                    'titleFont' => [
                        'size' => 13,
                        'weight' => 'bold'
                    ],
                    'bodyFont' => [
                        'size' => 12
                    ]
                ]
            ]
        ];

        // Chart-specific options
        if ($chartType === 'bar') {
            $defaultOptions['scales'] = [
                'y' => [
                    'min' => 0,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)'
                    ],
                    'ticks' => [
                        'padding' => 10
                    ]
                ],
                'x' => [
                    'grid' => [
                        'display' => false
                    ],
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 0,
                        'padding' => 10
                    ]
                ]
            ];
        } elseif ($chartType === 'doughnut') {
            $defaultOptions['plugins']['tooltip']['bodySpacing'] = 5;
            $defaultOptions['cutout'] = '60%';
        }

        // Merge with provided options
        if (!empty($options)) {
            $defaultOptions = array_merge_recursive($defaultOptions, $options);
        }

        $preparedData = [
            'type' => $chartType,
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => $title,
                        'data' => $values,
                        'backgroundColor' => $colors,
                        'borderColor' => $borderColors,
                        'borderWidth' => 1,
                        'barThickness' => 20,
                        'maxBarThickness' => 40,
                    ],
                ]
            ],
            'options' => $defaultOptions
        ];

        return json_encode($preparedData);
    }

    /**
     * Format model label for better display
     *
     * @param string $modelId
     * @return string
     */
    private function formatModelLabel(string $modelId): string
    {
        // Handle NULL/empty models
        $modelId = trim($modelId);
        if ($modelId === '' || strtolower($modelId) === 'null') {
            return 'Unknown Model';
        }

        // Clean up model names (remove prefixes if any)
        $modelId = str_replace('snapshot-', '', $modelId);

        // Format GPT model names for better readability
        // Handle patterns like: gpt-4o-2024-08-06, gpt-4o-mini-2024-07-18, gpt-4-0613
        if (preg_match('/^gpt-(\d+)(o)?(-mini)?(-(\d{4}-\d{2}-\d{2}))?(-(\d{4}))?$/i', $modelId, $matches)) {
            $version = $matches[1];
            $isO = !empty($matches[2]);
            $isMini = !empty($matches[3]);
            $dateVersion = $matches[5] ?? '';
            $yearVersion = $matches[7] ?? '';

            $formatted = 'GPT-' . $version;
            if ($isO) {
                $formatted .= 'o';
            }
            if ($isMini) {
                $formatted .= ' Mini';
            }
            if ($dateVersion) {
                // Format date: 2024-08-06 -> Aug 2024 (using DateTime for better performance)
                try {
                    $date = new \DateTime($dateVersion);
                    $formatted .= ' (' . $date->format('M Y') . ')';
                } catch (\Exception $e) {
                    $formatted .= ' (' . $dateVersion . ')';
                }
            } elseif ($yearVersion) {
                $formatted .= ' (' . $yearVersion . ')';
            }

            return $formatted;
        }

        // Format other common model names using array for single pass replacement
        $replacements = [
            'gpt-' => 'GPT-',
            'davinci' => 'Davinci',
            'curie' => 'Curie',
            'babbage' => 'Babbage',
            'ada' => 'Ada'
        ];
        $modelId = str_replace(array_keys($replacements), array_values($replacements), $modelId);

        // Truncate very long model names but keep important parts
        $modelLength = strlen($modelId);
        if ($modelLength > 30) {
            // Try to keep the model type and version
            if (preg_match('/^(GPT-[^:]+)/', $modelId, $matches)) {
                return $matches[1];
            }
            return substr($modelId, 0, 27) . '...';
        }

        return $modelId;
    }
}