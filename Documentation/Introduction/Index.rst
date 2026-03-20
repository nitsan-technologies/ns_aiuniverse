.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

AI Universe is a base extension for AI operations in T3Planet's AI Extension.
It is designed as shared infrastructure, not as a standalone frontend plugin.

.. contents::
   :local:
   :depth: 2

.. _introduction-what-it-is:

What it is
==========

- A reusable service layer for AI provider communication.
- A central configuration point for API keys and model defaults.
- A utility and statistics layer for AI-enabled T3Plane's AI Extensions for TYPO3.

.. _introduction-what-it-is-not:

What it is not
==============

- It does not register a frontend plugin by itself.
- It does not ship page templates or TypoScript frontend rendering.
- It is not an end-user chatbot UI product out of the box.

.. _introduction-who-is-it-for:

Who it is for
=============

**Developers**
   Use services like :php:`AiRequestService`, :php:`BaseClient`,
   :php:`AiStatisticsService`, and utilities to build AI features.

**Editors / admins**
   Configure providers, API keys, default models, and basic auth in
   Extension Configuration.

**Non-technical stakeholders**
   Get a single foundation layer that reduces duplicated AI integration
   work across extensions.

.. _introduction-key-capabilities:

Key capabilities
================

- Multi-provider request preparation and response parsing.
- Embedding request/response support for selected providers.
- OpenAI usage statistics retrieval, transformation, and cache-backed chart data.
- Basic authentication helper for protected URL fetches.
- Centralized utility helpers for extension configuration access.

.. _introduction-supported-providers:

Supported providers
===================

The codebase includes provider handling for:

- OpenAI
- Claude / Anthropic
- Gemini
- Azure OpenAI
- Mistral
- DeepSeek
- xAI
- Custom LLM endpoint

Provider behavior and available options depend on configured API keys and model
settings in :file:`ext_conf_template.txt`.
