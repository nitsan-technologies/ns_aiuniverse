.. include:: /Includes.rst.txt

.. _architecture:

============
Architecture
============

.. contents::
   :local:
   :depth: 2

.. _architecture-overview:

Overview
========

AI Universe is a shared foundation layer:

::

   Consuming Extension Code
           |
           v
     AiRequestService
           |
           v
        BaseClient
           |
           v
      Provider APIs

Parallel support:

::

   AiStatisticsService -> OpenAI Usage API -> Processed chart data cache
   HttpAuthUtility    -> Protected URL fetching with optional Basic Auth

.. _architecture-components:

Main components
===============

- **Request orchestration**: :php:`AiRequestService`
- **Provider adapters and payload composition**: :php:`BaseClient`
- **Statistics processing**: :php:`AiStatisticsService`
- **Engine configuration filtering**: :php:`AiEngineConfiguration`
- **Utility and environment helpers**: :php:`AiUniverseUtilityHelper`
- **HTTP auth helper**: :php:`HttpAuthUtility`

.. _architecture-config:

Configuration model
===================

Runtime behavior is mostly driven by extension configuration keys from
:file:`ext_conf_template.txt`.

This includes:

- provider keys and models
- default engine selection
- token/temperature values
- basic auth settings

.. _architecture-cache:

Caching
=======

The extension registers cache :php:`nsaiuniverse_statistics` in
:file:`ext_localconf.php`.

Statistics service stores processed data in this cache to reduce repeated
usage API calls.

.. _architecture-constraints:

Constraints
===========

- No native frontend plugin and no Fluid frontend output in this package.
- Primary role is reusable service infrastructure.
