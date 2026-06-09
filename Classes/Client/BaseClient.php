<?php

namespace NITSAN\NsAiUniverse\Client;

use DateTime;
use Exception;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class BaseClient
 *
 * Provides functionality to prepare request and response data for various AI providers.
 *
 */
class BaseClient
{
    public const OPENAI_API_URL = 'https://api.openai.com/';
    public const OPENAI_CHAT_ENDPOINT = 'v1/chat/completions';
    public const OPENAI_EMBEDDINGS_ENDPOINT = 'v1/embeddings';
    public const CLAUDE_API_URL = 'https://api.anthropic.com/v1/messages';
    public const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';
    public const OPENAI_LEGACY_API_URL = 'https://api.openai.com/v1/completions';
    public const OPENAI_USAGE_ENDPOINT = 'v1/organization/usage/completions';
    public const DEEPSEEK_API_URL = 'https://api.deepseek.com/';
    public const DEEPSEEK_CHAT_ENDPOINT = 'chat/completions';
    public const XAI_API_URL = 'https://api.x.ai/';
    public const XAI_CHAT_ENDPOINT = 'v1/chat/completions';
    public const MISTRAL_CHAT_ENDPOINT = 'https://api.mistral.ai/v1/chat/completions';
    public const ANTHROPIC_MESSAGES_ENDPOINT = 'https://api.anthropic.com/v1/messages';
    public const MISTRAL_EMBEDDINGS_ENDPOINT = 'https://api.mistral.ai/v1/embeddings';
    public const HUGGINGFACE_INFERENCE_URL = 'https://router.huggingface.co/hf-inference/models/';
    public const OLLAMA_CHAT_ENDPOINT = '/v1/chat/completions';

    protected bool $nonLegacyModel;

    /**
     * @var RequestFactory
     */
    public RequestFactory $requestFactory;

    /**
     * @var array
     */
    public array $extConf;

    public function __construct(
        bool  $nonLegacyModel,
        array $extConf
    ) {
        $this->extConf = $extConf;
        $this->nonLegacyModel = $nonLegacyModel;
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
    }

    public function getRequestData(string $type, $data, mixed $content = null, $aiSelectedModel = ''): array
    {
        $requestData = [];
        switch ($type) {
            case 'openai':
                $requestData = $this->getOpenAiRequestData($data, $aiSelectedModel);
                break;
            case 'gemini':
                $requestData = $this->getGeminiRequestData($data, $content, $aiSelectedModel);
                break;
            case 'azure':
                $requestData = $this->getAzureRequestData($data, $content, $aiSelectedModel);
                break;
            case 'claude':
                $requestData = $this->getClaudeRequestData($data, $content, $aiSelectedModel);
                break;
            case 'deepseek':
                $requestData = $this->getDeepseekRequestData($data, $aiSelectedModel);
                break;
            case 'customllm':
                $requestData = $this->getCustomLlmRequestData($data, $content);
                break;
            case 'mistral':
                $requestData = $this->getMistralRequestData($data, $aiSelectedModel);
                break;
            case 'xai':
                $requestData = $this->getXaiRequestData($data, $aiSelectedModel);
                break;
            case 'ollama':
                $requestData = $this->getOllamaRequestData($data, $aiSelectedModel);
                break;
        }
        return $requestData;
    }

    public function getResponseData(string $type, array $responseArray): string
    {
        $generatedText = '';
        switch ($type) {
            case 'openai':
                $generatedText = $this->getOpenAiResponseData($responseArray);
                break;
            case 'gemini':
                $generatedText = $this->getGeminiResponseData($responseArray);
                break;
            case 'azure':
                $generatedText = $this->getAzureResponseData($responseArray);
                break;
            case 'claude':
                $generatedText = $this->getClaudeResponseData($responseArray);
                break;
            case 'deepseek':
                $generatedText = $this->getDeepseekResponseData($responseArray);
                break;
            case 'customllm':
                $generatedText = $this->getCustomLlmResponseData($responseArray);
                break;
            case 'mistral':
                $generatedText = $this->getMistralResponseData($responseArray);
                break;
            case 'xai':
                $generatedText = $this->getXaiResponseData($responseArray);
                break;
            case 'ollama':
                $generatedText = $this->getOllamaResponseData($responseArray);
                break;
        }

        return $generatedText;
    }

    /**
     * Get request data for streaming chat (OpenAI / Gemini / Mistral).
     * Returns array with 'url' and 'body' for RequestFactory.
     *
     * @param string $type 'openai', 'gemini', or 'mistral'
     * @param array $messagesOrContents For OpenAI/Mistral: messages. For Gemini: contents [['role'=>'user','parts'=>[['text'=>'...']]]]
     * @param array $options Optional: frequency_penalty, presence_penalty (openai/mistral); model (gemini)
     * @param string $aiSelectedModel Override model (e.g. gpt-4o, gemini-1.5-flash, mistral-large-latest)
     * @return array{url: string, body: array}
     */
    public function getStreamRequestData(string $type, array $messagesOrContents, array $options = [], string $aiSelectedModel = ''): array
    {
        if ($type === 'openai') {
            return $this->getOpenAiStreamRequestData($messagesOrContents, $options, $aiSelectedModel);
        }
        if ($type === 'gemini') {
            return $this->getGeminiStreamRequestData($messagesOrContents, $options, $aiSelectedModel);
        }
        if ($type === 'mistral') {
            return $this->getMistralStreamRequestData($messagesOrContents, $options, $aiSelectedModel);
        }
        if ($type === 'claude') {
            return $this->getClaudeStreamRequestData($messagesOrContents, $options, $aiSelectedModel);
        }
        if ($type === 'ollama') {
            return $this->getOllamaStreamRequestData($messagesOrContents, $options, $aiSelectedModel);
        }
        return [
            'url' => '',
            'body' => [],
        ];
    }

    /**
     * Extract text delta from a single stream chunk (OpenAI, Gemini, or Mistral).
     *
     * @param string $modelType 'openai', 'gemini', or 'mistral'
     * @param array $chunk Decoded JSON chunk from stream
     * @return string
     */
    public function getStreamChunkText(string $modelType, array $chunk): string
    {
        if ($modelType === 'openai' || $modelType === 'mistral' || $modelType === 'ollama') {
            return (string)($chunk['choices'][0]['delta']['content'] ?? '');
        }
        if ($modelType === 'gemini') {
            if (isset($chunk['candidates'][0]['content']['parts'][0]['text'])) {
                return (string)$chunk['candidates'][0]['content']['parts'][0]['text'];
            }
            return '';
        }
        if ($modelType === 'claude') {
            if (($chunk['type'] ?? '') === 'content_block_delta' && ($chunk['delta']['type'] ?? '') === 'text_delta') {
                return (string)($chunk['delta']['text'] ?? '');
            }
            return '';
        }
        return '';
    }

    /**
     * Build message history in the format required by the given engine (for chat/stream).
     *
     * @param string $engine 'openai', 'gemini', or 'mistral'
     * @param string $systemContent System/context instruction
     * @param string $userMessage User question (optionally prefixed by caller)
     * @return array Messages or contents array ready for getStreamRequestData
     */
    public function buildMessageHistory(string $engine, string $systemContent, string $userMessage): array
    {
        if ($engine === 'gemini') {
            return [
                ['role' => 'user', 'parts' => [['text' => $systemContent]]],
                ['role' => 'user', 'parts' => [['text' => $userMessage]]],
            ];
        }
        if ($engine === 'mistral' || $engine === 'claude') {
            return [
                ['role' => 'system', 'content' => $systemContent],
                ['role' => 'user', 'content' => $userMessage],
            ];
        }
        return [
            ['role' => 'system', 'content' => $systemContent],
            ['role' => 'user', 'content' => $userMessage],
        ];
    }

    /**
     * Map deprecated Anthropic model IDs to current Claude 4.x IDs (API returns 404 for old -latest).
     */
    private static function normalizeClaudeModel(string $model): string
    {
        $map = [
            'claude-3-5-haiku-latest' => 'claude-haiku-4-5',
            'claude-3-5-sonnet-latest' => 'claude-sonnet-4-6',
            'claude-3-opus-latest' => 'claude-opus-4-6',
            'claude-3-opus-20240229' => 'claude-opus-4-6',
            'claude-3-sonnet-20240229' => 'claude-sonnet-4-6',
            'claude-3-haiku-20240307' => 'claude-haiku-4-5',
        ];
        return $map[$model] ?? $model;
    }

    /**
     * Build Anthropic streaming request (system + messages, stream: true).
     *
     * @param array $messagesOrContents [['role'=>'system','content'=>'...'], ['role'=>'user','content'=>'...']]
     * @param array $options Optional: temperature, max_tokens
     * @param string $aiSelectedModel Override model (e.g. claude-3-haiku-20240307)
     * @return array{url: string, body: array}
     */
    protected function getClaudeStreamRequestData(array $messagesOrContents, array $options, string $aiSelectedModel): array
    {
        $model = !empty($aiSelectedModel) ? $aiSelectedModel : ($this->extConf['anthropic_model'] ?? 'claude-haiku-4-5');
        $model = self::normalizeClaudeModel($model);
        $system = '';
        $messages = [];
        foreach ($messagesOrContents as $msg) {
            $role = $msg['role'] ?? '';
            $content = $msg['content'] ?? '';
            if ($role === 'system') {
                $system = $content;
            } else {
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }
        $body = [
            'model' => $model,
            'max_tokens' => (int)($options['max_tokens'] ?? $this->extConf['anthropic_max_tokens'] ?? 1024),
            'stream' => true,
            'messages' => $messages,
        ];
        if ($system !== '') {
            $body['system'] = $system;
        }
        if (isset($this->extConf['anthropic_temperature'])) {
            $body['temperature'] = (float)$this->extConf['anthropic_temperature'];
        }
        $requestData = [];
        $requestData['url'] = self::ANTHROPIC_MESSAGES_ENDPOINT;
        $requestData['body'] = [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->extConf['anthropic_api_key'] ?? '',
                'anthropic-version' => '2023-06-01',
            ],
            'json' => $body,
        ];
        return $requestData;
    }

    protected function getOpenAiStreamRequestData(array $messages, array $options, string $aiSelectedModel): array
    {
        $model = !empty($aiSelectedModel) ? $aiSelectedModel : ($this->extConf['openai_model'] ?? 'gpt-4.1');
        $data = [
            'model' => $model,
            'stream' => true,
            'messages' => $messages,
            'temperature' => (float)($options['temperature'] ?? $this->extConf['openai_temperature'] ?? 0.7),
            'max_tokens' => (int)($options['max_tokens'] ?? $this->extConf['openai_max_tokens'] ?? 1024),
            'frequency_penalty' => (float)($options['frequency_penalty'] ?? $this->extConf['openai_frequency_penalty'] ?? 0),
            'presence_penalty' => (float)($options['presence_penalty'] ?? $this->extConf['openai_presence_penalty'] ?? 0),
        ];
        if (strpos($model, 'gpt-5') === 0) {
            $data['max_completion_tokens'] = $data['max_tokens'] ?? (int)($this->extConf['openai_max_tokens'] ?? 1024);
            unset($data['max_tokens'], $data['temperature'], $data['presence_penalty'], $data['frequency_penalty']);
        }
        $requestData = [];
        $requestData['url'] = $this->nonLegacyModel ? self::OPENAI_API_URL . self::OPENAI_CHAT_ENDPOINT : self::OPENAI_LEGACY_API_URL;
        $requestData['body'] = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . ($this->extConf['openai_api_key'] ?? ''),
            ],
            'json' => $data,
        ];
        return $requestData;
    }

    protected function getGeminiStreamRequestData(array $contents, array $options, string $aiSelectedModel): array
    {
        $model = !empty($aiSelectedModel) ? $aiSelectedModel : ($this->extConf['gemini_model'] ?? 'gemini-1.5-flash');
        $requestPayload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => (int)($this->extConf['gemini_max_output_tokens'] ?? 1024),
                'stopSequences' => [],
                'stream' => true,
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
            ],
        ];
        $requestData = [];
        $requestData['url'] = self::GEMINI_API_URL . $model . ':generateContent';
        $requestData['body'] = [
            'query' => ['key' => $this->extConf['gemini_api_key'] ?? ''],
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $requestPayload,
        ];
        return $requestData;
    }

    protected function getMistralStreamRequestData(array $messages, array $options, string $aiSelectedModel): array
    {
        $model = !empty($aiSelectedModel) ? $aiSelectedModel : ($this->extConf['mistral_model'] ?? 'mistral-large-latest');
        $data = [
            'model' => $model,
            'stream' => true,
            'messages' => $messages,
            'temperature' => (float)($options['temperature'] ?? $this->extConf['mistral_temperature'] ?? 0.7),
            'max_tokens' => (int)($options['max_tokens'] ?? $this->extConf['mistral_max_tokens'] ?? 1024),
        ];
        if (isset($options['frequency_penalty']) || isset($this->extConf['mistral_frequency_penalty'])) {
            $data['frequency_penalty'] = (float)($options['frequency_penalty'] ?? $this->extConf['mistral_frequency_penalty'] ?? 0);
        }
        if (isset($options['presence_penalty']) || isset($this->extConf['mistral_presence_penalty'])) {
            $data['presence_penalty'] = (float)($options['presence_penalty'] ?? $this->extConf['mistral_presence_penalty'] ?? 0);
        }
        $requestData = [];
        $requestData['url'] = self::MISTRAL_CHAT_ENDPOINT;
        $requestData['body'] = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . ($this->extConf['mistral_api_key'] ?? ''),
            ],
            'json' => $data,
        ];
        return $requestData;
    }

    protected function getOpenAiRequestData($data, $aiSelectedModel): array
    {
        $requestData = [];
        $requestData['url'] = $this->nonLegacyModel ? self::OPENAI_API_URL . self::OPENAI_CHAT_ENDPOINT : self::OPENAI_LEGACY_API_URL;
        $data['model'] = !empty($aiSelectedModel) ? $aiSelectedModel : $this->extConf['openai_model'];
        $apiKey = $this->extConf['openai_api_key'] ?? '';
        if (strpos((string)$data['model'], 'gpt-5') === 0) {
            $data['max_completion_tokens'] = $data['max_tokens'];
            unset(
                $data['max_tokens'],
                $data['temperature'],
                $data['top_p'],
                $data['presence_penalty'],
                $data['frequency_penalty']
            );
        }

        if ($data['model'] === 'openai-oss') {
            $model = $this->extConf['openai_oss_api_model'] ?? '';
            $data['model'] = $model;
            $data['max_tokens'] = 2000;
            $data['top_p'] = 0.8;
            unset(
                $data['frequency_penalty'],
                $data['presence_penalty']
            );
            $requestData['url'] = ($this->extConf['openai_oss_api_url'] ?? 'https://api.cerebras.ai/') . self::OPENAI_CHAT_ENDPOINT;
            $apiKey = $this->extConf['openai_oss_api_key'] ?? '';
        }

        $requestData['body'] = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey
            ],
            'json' => $data
        ];
        return $requestData;
    }

    public function getCustomLlmRequestData($data, $content, $aiSelectedModel = '')
    {
        $requestData = [];
        // $model = !empty($aiSelectedModel) ? $aiSelectedModel : '';

        // Base request body data
        $requestBodyData = [
            // 'model' => $model,
            'stream' => false
        ];
        $content = !empty($data['messages'][0]['content'])
            ? $data['messages'][0]['content']
            : (!empty($data['messages'][1]['role']) ? $data['messages'][1]['content'] : $content);
        // Add endpoint-specific data structure
        $requestBodyData['messages'] = [
            [
                'role' => 'user',
                'content' => $content
            ]
        ];

        $customllm = [
            'temperature',
        ];

        foreach($customllm as $opion) {
            if(isset($this->extConf['custom_llm_'.$opion]) && $this->extConf['custom_llm_'.$opion] != '') {
                $requestBodyData[$opion] = $this->extConf['custom_llm_'.$opion];
            }
        }

        $requestData['body'] = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->extConf['custom_llm_api_key']
            ],
            'body' => json_encode($requestBodyData)
        ];
        $requestData['url'] = $this->extConf['custom_llm_api_url'];
        return $requestData;
    }
    protected function getGeminiRequestData($data, $content, $aiSelectedModel): array
    {
        $requestData = [];
        $dataContent = '';
        if (isset($data['messages'][0]['content']) && $data['messages'][0]['content'] !== '') {
            $dataContent = $data['messages'][0]['content'];
        } elseif(isset($data['messages'][1]['content']) && $data['messages'][1]['content'] !== '') {
            $dataContent = $data['messages'][1]['content'];
        } else {
            $dataContent = $content;
        }
        $requestPayload = [
            "contents" => [
                "parts" => [
                    ["text" => $dataContent]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.1,
                "maxOutputTokens" => (int)($this->extConf['gemini_max_output_tokens'] ?? 1024),
                "stopSequences" => [],
            ],
            "safetySettings" => [
                [
                    "category" => "HARM_CATEGORY_HARASSMENT",
                    "threshold" => "BLOCK_NONE",
                ],
                [
                    "category" => "HARM_CATEGORY_HATE_SPEECH",
                    "threshold" => "BLOCK_NONE",
                ],
                [
                    "category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                    "threshold" => "BLOCK_NONE",
                ],
                [
                    "category" => "HARM_CATEGORY_DANGEROUS_CONTENT",
                    "threshold" => "BLOCK_NONE",
                ],
            ],
        ];

        $model = !empty($aiSelectedModel) ? $aiSelectedModel : $this->extConf['gemini_model'];
        $requestData['url'] = self::GEMINI_API_URL . $model . ':generateContent';
        $requestData['body'] = [
            'query' => [
                'key' => $this->extConf['gemini_api_key']
            ],
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => $requestPayload
        ];

        return $requestData;
    }

    protected function getAzureRequestData($data, $content, $aiSelectedModel): array
    {
        $model = !empty($aiSelectedModel) ? $aiSelectedModel : $this->extConf['azure_api_model'];
        $requestData['url'] = $this->extConf['azure_api_endpoint'] . 'openai/deployments/'.$model . '/chat/completions';
        $requestData['body'] = [
            'query' => [
                'api-version' => $this->extConf['azure_api_version']
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'api-key' => $this->extConf['azure_api_key']
            ],
            'json' => [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $data['messages'][0]['content'] ?? $content
                    ]
                ]
            ]
        ];
        return $requestData;
    }

    protected function getClaudeRequestData($data, $content, $aiSelectedModel): array
    {
        $model = !empty($aiSelectedModel) ? $aiSelectedModel : $this->extConf['claude_api_model'];
        $content = !empty($data['messages'][0]['content'])
            ? $data['messages'][0]['content']
            : (!empty($data['messages'][1]['role']) ? $data['messages'][1]['content'] : $content);
        $requestPayload = [
            'model' => $model,
            'max_tokens' => 1024,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $content
                ]
            ]
        ];

        $requestData['url'] = self::CLAUDE_API_URL;
        $requestData['body'] = [
            'headers' => [
                'x-api-key' => $this->extConf['anthropic_api_key'],
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json'
            ],
            'json' => $requestPayload
        ];

        return $requestData;
    }

    protected function getDeepseekRequestData($data, $aiSelectedModel): array
    {
        $requestData = [
            'url' => self::DEEPSEEK_API_URL . self::DEEPSEEK_CHAT_ENDPOINT,
            'body' => [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->extConf['deepseek_api_key']
                ],
                'json' => [
                    'model' => $aiSelectedModel ?: $this->extConf['deepseek_model'],
                    'stop' => $this->extConf['deepseek_stop'] ?? null,
                    'response_format' => [
                        'type' => $this->extConf['deepseek_response_format'] ?? 'text'
                    ],
                    'stream' => (bool)($this->extConf['deepseek_stream'] ?? false),
                    'stream_options' => $this->extConf['deepseek_stream_options'] ?? null,
                    'tools' => $this->extConf['deepseek_tools'] ?? null,
                    'tool_choice' => $this->extConf['deepseek_tool_choice'] ?? 'none',
                    'logprobs' => (bool)($this->extConf['deepseek_logprobs'] ?? false),
                    'top_logprobs' => null
                ]
            ]
        ];

        return $requestData;
    }

    protected function getXaiRequestData($data, $aiSelectedModel): array
    {
        $requestData = [
            'url' => self::XAI_API_URL . self::XAI_CHAT_ENDPOINT,
            'body' => [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->extConf['xai_api_key']
                ],
                'json' => [
                    'model' => $aiSelectedModel ?: $this->extConf['xai_model'],
                    'messages' => $data['messages'],
                    'response_format' => [
                        'type' => $this->extConf['xai_response_format'] ?? 'text'
                    ],
                    'temperature' => (float)$this->extConf['xai_temperature'] ?? null,
                    'max_tokens' => 16384,
                ]
            ]
        ];
        return $requestData;
    }

    protected function getOpenAiResponseData($responseArray): string
    {
        return
            $this->extConf['openai_model'] === 'gpt-4.1' ||
            $this->extConf['openai_model'] === 'gpt-5.4-mini' ||
            $this->extConf['openai_model'] === 'gpt-5.4-pro' ||
            $this->extConf['openai_model'] === 'gpt-5.4-nano' ?
            $responseArray['choices'][0]['message']['content'] : $responseArray['choices'][0]['text'];
    }

    protected function getCustomLlmResponseData($responseArray): string
    {
        return $responseArray['message']['content'];
    }

    protected function getGeminiResponseData($responseArray): string
    {
        $generatedText = '';
        if (isset($responseArray['candidates'])) {
            $generatedText = isset($responseArray['candidates'][0]['content']) ? $responseArray['candidates'][0]['content']['parts'][0]['text'] : '';
        } elseif (isset($responseArray['error']['code']) && $responseArray['error']['code'] == 400) {
            $generatedText = $responseArray['error']['code'];
        }
        return $generatedText;
    }

    protected function getAzureResponseData($responseArray): string
    {
        return  $responseArray['choices'][0]['message']['content'] ?? $responseArray['choices'][0]['text'];
    }

    protected function getClaudeResponseData($responseArray): string
    {
        $generatedText = '';
        if (isset($responseArray['content'][0]['text'])) {
            $generatedText = $responseArray['content'][0]['text'];
            $substring = ":";
            // Find the position of the first occurrence of the substring
            $position = strpos($generatedText, $substring);

            // Check if the substring is found in the string
            if ($position !== false) {
                // Calculate the position right after the substring
                $startPosition = $position + strlen($substring);
                // Extract the part of the string after the substring
                $generatedText = substr($generatedText, $startPosition);
            }

        } elseif (isset($responseArray['error']['code']) && $responseArray['error']['code'] == 400) {
            $generatedText = $responseArray['error']['code'];
        } elseif (isset($responseArray['error'])) {
            $generatedText = $responseArray['error']['message'];
        }
        return $generatedText;
    }

    protected function getDeepseekResponseData($responseArray): string
    {
        return $responseArray['choices'][0]['message']['content'] ?? '';
    }

    protected function getXaiResponseData($responseArray): string
    {
        return $responseArray['choices'][0]['message']['content'] ?? '';
    }


    /**
     * @param string $date
     * @param integer $dateScope
     * @return array
     */
    public function getOpenAiUsageData(string $date, int $dateScope): array
    {
        if ($date == '') {
            $date = date('Y-m-d');
        }

        // Convert date to Unix timestamp (start of day)
        $dateTime = new DateTime($date);
        $dateTime->setTime(0, 0, 0);
        $startTime = $dateTime->getTimestamp();

        if ($dateScope === 0) {
            // Single date: get data for that day (start to end of day)
            $endTime = $dateTime->getTimestamp() + 86399; // End of day (23:59:59)
            // Use bucket_width=1d for daily buckets, group_by=model to separate by model
            $url = self::OPENAI_API_URL . self::OPENAI_USAGE_ENDPOINT . '?start_time=' . $startTime . '&end_time=' . $endTime . '&bucket_width=1d&group_by=model';
            return $this->getOpenAiUsageDataFromApi($url);
        } else {
            // Date range: get data for the past N days
            $endDate = new DateTime();
            $endDate->setTime(23, 59, 59); // End of today
            $endTime = $endDate->getTimestamp();

            // Calculate start time (N days ago at start of day)
            $startDate = clone $endDate;
            $startDate->modify('-' . $dateScope . ' days');
            $startDate->setTime(0, 0, 0);
            $startTime = $startDate->getTimestamp();

            // Use bucket_width=1d for daily buckets, group_by=model to separate by model
            $url = self::OPENAI_API_URL . self::OPENAI_USAGE_ENDPOINT . '?start_time=' . $startTime . '&end_time=' . $endTime . '&bucket_width=1d&group_by=model';
            return $this->getOpenAiUsageDataFromApi($url);
        }
    }

    /**
     * @param string $url
     * @return array
     */
    protected function getOpenAiUsageDataFromApi(string $url): array
    {
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $authorization = 'Bearer ' . ($this->extConf['openai_admin_api_key'] ?? $this->extConf['openai_api_key'] ?? '');
        $allData = [];
        $nextPage = null;
        $hasMore = true;

        try {
            // Handle pagination - fetch all pages if needed (per OpenAI API docs)
            while ($hasMore) {
                $currentUrl = $url;
                if ($nextPage !== null) {
                    // Add next_page cursor to URL for pagination
                    $separator = strpos($currentUrl, '?') !== false ? '&' : '?';
                    $currentUrl .= $separator . 'after=' . urlencode($nextPage);
                }

                $response = $requestFactory->request(
                    $currentUrl,
                    'GET',
                    [
                        'headers' => [
                            'Authorization' => $authorization
                        ]
                    ]
                );

                $resJsonBody = $response->getBody()->getContents();
                $responseData = json_decode($resJsonBody, true);

                // Check for HTTP errors in response
                if ($response->getStatusCode() !== 200) {
                    $errorMessage = $responseData['error']['message'] ?? $responseData['error'] ?? 'Unknown error';
                    return [
                        'success' => false,
                        'responseData' => $errorMessage
                    ];
                }

                // Merge data from this page
                if (isset($responseData['data']) && is_array($responseData['data'])) {
                    if (empty($allData)) {
                        $allData = $responseData;
                    } else {
                        // Merge buckets from this page
                        $allData['data'] = array_merge($allData['data'], $responseData['data']);
                    }
                }

                // Check if there are more pages (per OpenAI API pagination)
                $hasMore = isset($responseData['has_more']) && $responseData['has_more'] === true;
                $nextPage = $responseData['next_page'] ?? null;

                // Break if no more pages
                if (!$hasMore || $nextPage === null) {
                    break;
                }
            }

            return [
                'success' => true,
                'responseData' => $allData
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'responseData' => $e->getMessage()
            ];
        }
    }

    protected function getMistralRequestData($data, $aiSelectedModel): array
    {
        $requestData = [];
        $requestData['url'] = self::MISTRAL_CHAT_ENDPOINT;
        $requestData['body'] = [
            'headers' => [
                'Authorization' => 'Bearer ' . ($this->extConf['mistral_api_key'] ?? ''),
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'model' => $aiSelectedModel ?: $this->extConf['mistral_model'],
                'messages' => $data['messages'],
                'temperature' => (float)($this->extConf['mistral_temperature'] ?? 0.7),
                'max_tokens' => (int)($this->extConf['mistral_max_tokens'] ?? 1024),
            ]
        ];
        return $requestData;
    }

    protected function getMistralResponseData($responseArray): string
    {
        if (is_string($responseArray['choices'][0]['message']['content'])) {
            return $responseArray['choices'][0]['message']['content'];
        } else {
            $finalText = '';
            foreach ($responseArray['choices'][0]['message']['content'] as $item) {
                if (($item['type'] ?? '') === 'text') {
                    $finalText .= $item['text'] ?? '';
                }
            }
            return $finalText;
        }
    }

    /**
     * Get request data for embedding API (OpenAI or Gemini).
     * Returns array with 'url' and 'body' (headers + json) for RequestFactory.
     *
     * @param string $modelType 'openai' or 'gemini'
     * @param string $text Text to embed
     * @return array{url: string, body: array{headers: array, json: array}}
     */
    public function getEmbeddingRequestData(string $modelType, string $text): array
    {
        if ($modelType === 'gemini') {
            return $this->getGeminiEmbeddingRequestData($text);
        }
        else if ($modelType === 'mistral') {
            return $this->getMistralEmbeddingRequestData($text);
        }
        else if ($modelType === 'huggingface') {
            return $this->getHuggingFaceEmbeddingRequestData($text);
        }
        else if ($modelType === 'ollama') {
            return $this->getOllamaEmbeddingRequestData($text);
        }
        else {
            return $this->getOpenAiEmbeddingRequestData($text);
        }
    }

    /**
     * Get request data for embedding API (OpenAI).
     * Returns array with 'url' and 'body' (headers + json) for RequestFactory.
     *
     * @param string $text Text to embed
     * @return array{url: string, body: array{headers: array, json: array}}
     */
    protected function getOpenAiEmbeddingRequestData(string $text): array
    {
        $model = $this->extConf['openai_embedding_model'] ?? 'text-embedding-ada-002';
        $jsonContent = [
            'model' => $model,
            'input' => $text,
        ];
        $url = self::OPENAI_API_URL . self::OPENAI_EMBEDDINGS_ENDPOINT;
        $body = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . ($this->extConf['openai_api_key'] ?? ''),
            ],
            'json' => $jsonContent,
        ];
        return ['url' => $url, 'body' => $body];
    }

    /**
     * Get request data for embedding API (Gemini).
     * Returns array with 'url' and 'body' (headers + json) for RequestFactory.
     *
     * @param string $text Text to embed
     * @return array{url: string, body: array{headers: array, json: array}}
     */
    protected function getGeminiEmbeddingRequestData(string $text): array
    {
        $model = $this->extConf['gemini_embedding_model'] ?? 'text-embedding-004';
        $jsonContent = [
            'model' => $model,
            'content' => [
                'parts' => [
                    ['text' => $text],
                ],
            ],
        ];
        $url = self::GEMINI_API_URL . $model . ':embedContent';
        $body = [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->extConf['gemini_api_key'] ?? '',
            ],
            'json' => $jsonContent,
        ];
        return ['url' => $url, 'body' => $body];
    }

    protected function getMistralEmbeddingRequestData(string $text): array
    {
        $model = $this->extConf['mistral_embedding_model'] ?? 'mistral-embed';
        $jsonContent = [
            'model' => $model,
            'input' => [$text],
        ];
        $url = self::MISTRAL_EMBEDDINGS_ENDPOINT;
        $body = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . ($this->extConf['mistral_api_key'] ?? ''),
            ],
            'json' => $jsonContent,
        ];
        return ['url' => $url, 'body' => $body];
    }
    /**
     * Parse embedding API response to vector array.
     *
     * @param string $modelType 'openai' or 'gemini'
     * @param array $responseData Decoded JSON response
     * @return array|null Vector of floats or null on failure
     */
    public function parseEmbeddingResponse(string $modelType, array $responseData): ?array
    {
        if ($modelType === 'gemini') {
            return $this->parseGeminiEmbeddingResponse($responseData);
        }
        else if ($modelType === 'mistral') {
            return $this->parseMistralEmbeddingResponse($responseData);
        }
        else if ($modelType === 'huggingface') {
            return $this->parseHuggingFaceEmbeddingResponse($responseData);
        }
        else if ($modelType === 'ollama') {
            return $this->parseOpenAiEmbeddingResponse($responseData);
        }
        else {
            return $this->parseOpenAiEmbeddingResponse($responseData);
        }
    }



    protected function parseOpenAiEmbeddingResponse(array $responseData): ?array
    {
        if (!isset($responseData['data']) || !is_array($responseData['data']) || empty($responseData['data'])) {
            return null;
        }
        $result = [];
        foreach ($responseData['data'] as $item) {
            if (isset($item['embedding']) && is_array($item['embedding'])) {
                $result['embedding'] = $item['embedding'];
            }
        }
        if(isset($responseData['usage']['total_tokens']) && $responseData['usage']['total_tokens'] > 0) {
            $result['token_used'] = $responseData['usage']['total_tokens'];
        }
        return $result;
    }

    protected function parseGeminiEmbeddingResponse(array $responseData): ?array
    {
        if (!isset($responseData['embedding']['values']) || !is_array($responseData['embedding']['values'])) {
            return null;
        }
        $values = $responseData['embedding']['values'];
        return !empty($values) ? $values : null;
    }

    protected function parseMistralEmbeddingResponse(array $responseData): ?array
    {
        if (!isset($responseData['data']) || !is_array($responseData['data']) || empty($responseData['data'])) {
            return null;
        }
        $result = [];
        foreach ($responseData['data'] as $item) {
            if (isset($item['embedding']) && is_array($item['embedding'])) {
                $result['embedding'] = $item['embedding'];
            }
        }
        if(isset($responseData['usage']['total_tokens']) && $responseData['usage']['total_tokens'] > 0) {
            $result['token_used'] = $responseData['usage']['total_tokens'];
        }
        return $result;
    }

    /**
     * HuggingFace Inference API embedding request (sentence-transformers).
     */
    protected function getHuggingFaceEmbeddingRequestData(string $text): array
    {
        $model = $this->extConf['huggingface_embedding_model'] ?? 'sentence-transformers/all-MiniLM-L6-v2';
        $jsonContent = [
            'inputs' => $text,
        ];
        $url = self::HUGGINGFACE_INFERENCE_URL . $model . '/pipeline/feature-extraction';
        $body = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . ($this->extConf['huggingface_api_key'] ?? ''),
                'X-Wait-For-Model' => 'true',
            ],
            'json' => $jsonContent,
        ];
        return ['url' => $url, 'body' => $body];
    }

    /**
     * Parse HuggingFace embedding response.
     * sentence-transformers returns [[float, ...]] (2D) or [[[float,...],..]] (3D token-level).
     */
    protected function parseHuggingFaceEmbeddingResponse(array $responseData): ?array
    {
        if (empty($responseData)) {
            return null;
        }
        $first = $responseData[0] ?? null;
        if (!is_array($first)) {
            return null;
        }
        // 3D array (token-level): mean-pool over tokens
        if (is_array($first[0] ?? null)) {
            $tokenCount = count($first);
            if ($tokenCount === 0) {
                return null;
            }
            $dims = count($first[0]);
            $averaged = array_fill(0, $dims, 0.0);
            foreach ($first as $tokenEmb) {
                foreach ($tokenEmb as $i => $val) {
                    $averaged[$i] += (float)$val;
                }
            }
            $averaged = array_map(fn($v) => $v / $tokenCount, $averaged);
            return ['embedding' => $averaged, 'token_used' => 0];
        }
        // 2D array (sentence-level): use directly
        return ['embedding' => array_map('floatval', $first), 'token_used' => 0];
    }

    /**
     * Ollama embedding request (OpenAI-compatible endpoint).
     */
    protected function getOllamaEmbeddingRequestData(string $text): array
    {
        $ollamaUrl = rtrim($this->extConf['ollama_api_url'] ?? 'http://localhost:11434', '/');
        $model = $this->extConf['ollama_embedding_model'] ?? 'nomic-embed-text';
        $data = [
            'model' => $model,
            'input' => $text,
        ];
        return [
            'url' => $ollamaUrl . '/v1/embeddings',
            'body' => [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $data,
            ],
        ];
    }

    /**
     * Ollama streaming request (OpenAI-compatible endpoint).
     */
    protected function getOllamaStreamRequestData(array $messages, array $options, string $aiSelectedModel): array
    {
        $ollamaUrl = rtrim($this->extConf['ollama_api_url'] ?? 'http://localhost:11434', '/');
        $model = !empty($aiSelectedModel) ? $aiSelectedModel : ($this->extConf['ollama_model'] ?? 'gemma3:27b-cloud');
        $data = [
            'model' => $model,
            'stream' => true,
            'messages' => $messages,
        ];
        if (isset($options['temperature'])) {
            $data['temperature'] = (float)$options['temperature'];
        }
        if (isset($options['max_tokens'])) {
            $data['max_tokens'] = (int)$options['max_tokens'];
        }
        return [
            'url' => $ollamaUrl . self::OLLAMA_CHAT_ENDPOINT,
            'body' => [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $data,
            ],
        ];
    }

    /**
     * Ollama non-streaming request.
     */
    protected function getOllamaRequestData($data, $aiSelectedModel): array
    {
        if (
            isset($data['content'])
            && is_string($data['content'])
            && $data['content'] !== ''
            && empty($data['messages'])
        ) {
            $data['messages'] = [
                [
                    'role' => 'user',
                    'content' => $data['content'],
                ],
            ];
            unset($data['content']);
        }
        $ollamaUrl = rtrim($this->extConf['ollama_api_url'] ?? 'http://localhost:11434', '/');
        $model = !empty($aiSelectedModel) ? $aiSelectedModel : ($this->extConf['ollama_model'] ?? 'gemma3:27b-cloud');
        $data['model'] = $model;
        return [
            'url' => $ollamaUrl . self::OLLAMA_CHAT_ENDPOINT,
            'body' => [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $data,
            ],
        ];
    }

    /**
     * Parse Ollama response (OpenAI-compatible format).
     */
    protected function getOllamaResponseData($responseArray): string
    {
        $text = (string)($responseArray['choices'][0]['message']['content'] ?? '');
        if ($text === '' || !class_exists(\Parsedown::class)) {
            return $text;
        }
        $parsedown = new \Parsedown();
        $parsedown->setSafeMode(true);

        return $parsedown->text($text);
    }

    /**
     * Build multi-turn chat payload for streaming (OpenAI / Mistral / Claude messages, or Gemini contents).
     * Used by extensions such as T3AS AI Search chat; pairs with getStreamRequestData() / getStreamChunkText().
     *
     * @param string $engine 'openai', 'mistral', 'claude', or 'gemini'
     * @param string $fullSystemPrompt Full system string (caller may embed search context)
     * @param list<array{role: string, content: string}> $history Prior user/assistant turns (normalized by caller)
     * @param string $currentUserMessage Current user message
     * @return array<int, array<string, mixed>> Ready for getStreamRequestData()
     */
    public function buildMultiTurnChatMessages(
        string $engine,
        string $fullSystemPrompt,
        array $history,
        string $currentUserMessage
    ): array {
        if ($engine === 'gemini') {
            return $this->buildGeminiMultiTurnContents($fullSystemPrompt, $history, $currentUserMessage);
        }

        return $this->buildOpenAiStyleMultiTurnMessages($fullSystemPrompt, $history, $currentUserMessage);
    }

        /**
     * @param list<array{role: string, content: string}> $history
     * @return list<array{role: string, content: string}>
     */
    private function buildOpenAiStyleMultiTurnMessages(
        string $fullSystem,
        array $history,
        string $currentUserMessage
    ): array {
        $messages = [['role' => 'system', 'content' => $fullSystem]];
        foreach ($history as $turn) {
            $role = (($turn['role'] ?? '') === 'assistant') ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => (string)($turn['content'] ?? '')];
        }
        $messages[] = ['role' => 'user', 'content' => $currentUserMessage];

        return $messages;
    }

    /**
     * @param list<array{role: string, content: string}> $history
     * @return list<array<string, mixed>>
     */
    private function buildGeminiMultiTurnContents(
        string $fullSystem,
        array $history,
        string $currentUserMessage
    ): array {
        $contents = [
            ['role' => 'user', 'parts' => [['text' => $fullSystem]]],
        ];
        foreach ($history as $turn) {
            if (($turn['role'] ?? '') === 'assistant') {
                $contents[] = ['role' => 'model', 'parts' => [['text' => (string)($turn['content'] ?? '')]]];
            } else {
                $contents[] = ['role' => 'user', 'parts' => [['text' => (string)($turn['content'] ?? '')]]];
            }
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $currentUserMessage]]];

        return $contents;
    }
}
