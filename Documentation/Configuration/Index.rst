.. include:: /Includes.rst.txt

.. _configuration:

=============
Configuration
=============

This extension is configured through TYPO3 Extension Configuration values
defined in :file:`ext_conf_template.txt`.

.. contents::
   :local:
   :depth: 2

.. _configuration-overview:

Configuration overview
======================

Main groups include:

- AI engine defaults
- Provider-specific API keys and models
- Embedding model settings
- Translation provider defaults
- Basic authentication options

.. _configuration-minimum:

Minimum production configuration
================================

For a working setup:

1. Set :php:`defaultModel` (for example `openai`, `gemini`, `claude`, `mistral`).
2. Add API key for the selected provider.
3. Set provider default model.
4. Keep token and temperature values aligned with your usage/cost policy.

.. _configuration-provider-keys:

Provider keys
=============

Commonly used keys:

OpenAI
  :php:`openai_api_key`, :php:`openai_model`, :php:`openai_temperature`,
  :php:`openai_max_tokens`, :php:`openai_embedding_model`,
  :php:`openai_admin_api_key`

Anthropic / Claude
  :php:`anthropic_api_key`, :php:`anthropic_model`,
  :php:`anthropic_temperature`, :php:`anthropic_max_tokens`

Gemini
  :php:`gemini_api_key`, :php:`gemini_model`, :php:`gemini_embedding_model`

Azure
  :php:`azure_api_key`, :php:`azure_api_endpoint`,
  :php:`azure_api_model`, :php:`azure_api_version`

Mistral
  :php:`mistral_api_key`, :php:`mistral_model`,
  :php:`mistral_embedding_model`, :php:`mistral_temperature`,
  :php:`mistral_max_tokens`

DeepSeek
  :php:`deepseek_api_key`, :php:`deepseek_model`,
  :php:`deepseek_temperature`, :php:`deepseek_response_format`

xAI
  :php:`xai_api_key`, :php:`xai_model`,
  :php:`xai_temperature`, :php:`xai_response_format`

Custom LLM
  :php:`enable_custom_llm_model`, :php:`custom_llm_api_url`,
  :php:`custom_llm_api_key`, :php:`custom_llm_model_name`,
  :php:`custom_llm_temperature`

.. _configuration-basic-auth:

Basic authentication
====================

Use these keys when your source URLs are protected:

- :php:`basicAuthEnabled`
- :php:`basicAuthUsername`
- :php:`basicAuthPassword`

The helper :php:`HttpAuthUtility` retries `401/403` requests with basic auth
when this is enabled and fully configured.

.. _configuration-translation:

Translation-related settings
============================

Translation provider defaults are configured via:

- :php:`defaultModelForTranslation`
- :php:`deepl_api_key`, :php:`deepl_api_url`
- :php:`google_api_key`, :php:`google_api_url`

.. _configuration-security:

Security recommendations
========================

- Restrict backend access to trusted administrators.
- Rotate provider API keys regularly.
- Avoid sharing backend screenshots that expose secrets.
- Use dedicated provider keys per environment when possible.

.. warning::

   API keys are stored in extension configuration. Ensure your operational
   processes, backups, and access control policies treat them as sensitive data.
