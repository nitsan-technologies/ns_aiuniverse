.. include:: /Includes.rst.txt

.. _developer:

===============
Developer guide
===============

Technical integration guide for extension developers.

.. contents::
   :local:
   :depth: 2

.. _developer-services:

Core services
=============

:php:`AiRequestService`
   Sends provider requests, handles response parsing, and logs request outcomes.

:php:`BaseClient`
   Builds provider-specific request payloads and extracts provider-specific responses.

:php:`AiStatisticsService`
   Fetches OpenAI usage data, transforms result sets, and prepares chart-ready data.

:php:`AiEngineConfiguration`
   Exposes configured engines and filters engines based on available API keys.

:php:`HttpAuthUtility`
   Adds basic auth headers and provides protected URL fetch utility behavior.

:php:`AiUniverseUtilityHelper`
   Utility methods for extension configuration, TYPO3 version data, and page/language helpers.

.. _developer-di:

Dependency injection
====================

Services are autowired through :file:`Configuration/Services.yaml`.

.. code-block:: yaml
   :caption: Service registration overview

   services:
     _defaults:
       autowire: true
       autoconfigure: true
       public: false

     NITSAN\NsAiUniverse\:
       resource: '../Classes/*'

.. _developer-request-flow:

Request flow
============

1. Consumer calls :php:`AiRequestService::sendRequest()`.
2. Service merges defaults and incoming options.
3. :php:`BaseClient::getRequestData()` builds provider-specific endpoint + body.
4. TYPO3 :php:`RequestFactory` sends request.
5. :php:`BaseClient::getResponseData()` extracts generated text.
6. :php:`AiLogService` stores status log entry.

.. _developer-example:

Example integration
===================

.. code-block:: php
   :caption: Example use in custom service

   use NITSAN\NsAiUniverse\Service\AiRequestService;
   use TYPO3\CMS\Core\Utility\GeneralUtility;

   $ai = GeneralUtility::makeInstance(AiRequestService::class);

   $text = $ai->sendRequest(
       'openai',
       [['role' => 'user', 'content' => 'Summarize this page in 3 bullets.']],
       'gpt-4o',
       ['temperature' => 0.3, 'max_tokens' => 300],
       true,
       'my_extension',
       'summary'
   );

.. _developer-embeddings:

Embeddings
==========

Use :php:`BaseClient::getEmbeddingRequestData()` and
:php:`BaseClient::parseEmbeddingResponse()` for embedding workflows.

Supported embedding request handlers in code:

- OpenAI
- Gemini
- Mistral

.. _developer-statistics:

Statistics and caching
======================

:php:`AiStatisticsService`:

- fetches OpenAI usage API data
- paginates if needed
- transforms data via :php:`AiUniverseChartHelper`
- stores processed results in :php:`nsaiuniverse_statistics` cache
- default cache TTL is 24 hours for statistics snapshots

.. _developer-logging:

Logging
=======

Request outcomes are written to :php:`sys_log` via :php:`AiLogService`.
Use this for operational tracing and debugging.

.. _developer-notes:

Implementation notes
====================

- Provider capabilities and models evolve quickly; keep defaults reviewed.
- Error handling should be explicit around network failures and provider API errors.
- For security-sensitive environments, review how extension configuration is managed.
