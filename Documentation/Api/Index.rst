.. include:: /Includes.rst.txt

.. _api-reference:

=============
API reference
=============

Reference for major public classes used by dependent extensions.

.. contents::
   :local:
   :depth: 2

AiRequestService
================

Namespace:
  :php:`NITSAN\NsAiUniverse\Service\AiRequestService`

Key method:

.. code-block:: php
   :caption: Main request API

   public function sendRequest(
       string $modelType,
       array $messages,
       string $aiSelectedModel = '',
       array $options = [],
       bool $logRequest = true,
       string $module = '',
       string $scope = ''
   ): string

BaseClient
==========

Namespace:
  :php:`NITSAN\NsAiUniverse\Client\BaseClient`

Important methods:

- :php:`getRequestData()`
- :php:`getResponseData()`
- :php:`getStreamRequestData()`
- :php:`getStreamChunkText()`
- :php:`getEmbeddingRequestData()`
- :php:`parseEmbeddingResponse()`
- :php:`getOpenAiUsageData()`

AiStatisticsService
===================

Namespace:
  :php:`NITSAN\NsAiUniverse\Service\AiStatisticsService`

Main method:

- :php:`getOpenAiStatistics(string $date = '', int $dateScope = 0, bool $forceRefresh = false): array`

AiEngineConfiguration
=====================

Namespace:
  :php:`NITSAN\NsAiUniverse\Configuration\AiEngineConfiguration`

Main methods:

- :php:`getTextGenerationAIEngines(bool $ignoreConfig = false): array`
- :php:`getAllAIEngines(bool $ignoreConfig = false): array`

HttpAuthUtility
===============

Namespace:
  :php:`NITSAN\NsAiUniverse\Utility\HttpAuthUtility`

Main methods:

- :php:`fetchContentFromUrl(string $url): string`
- :php:`addAuthHeader(ServerRequestInterface $request): ServerRequestInterface`
- :php:`isBasicAuthEnabled(): bool`

AiUniverseUtilityHelper
=======================

Namespace:
  :php:`NITSAN\NsAiUniverse\Utility\AiUniverseUtilityHelper`

Main methods:

- :php:`getExtensionConf(string $extensionKey = 'ns_aiuniverse'): array`
- :php:`setExtensionConf(array $value, string $extensionKey = 'ns_aiuniverse'): void`
- :php:`isApiKeySet(string $extensionKey = 'ns_aiuniverse', string $apiKeyName = 'openai_api_key'): bool`
