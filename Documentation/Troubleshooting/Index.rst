.. include:: /Includes.rst.txt

.. _troubleshooting:

===============
Troubleshooting
===============

.. contents::
   :local:
   :depth: 2

.. _troubleshooting-common:

Common issues
=============

Provider request fails
----------------------

Checklist:

- Verify selected provider key (for example :php:`openai_api_key`) is set.
- Verify selected model value is valid for that provider.
- Verify endpoint URL for custom/azure setups.
- Review :php:`sys_log` entries written by :php:`AiLogService`.

HTTP 401 / 403 when fetching protected URL
------------------------------------------

Checklist:

- Enable :php:`basicAuthEnabled`.
- Set both :php:`basicAuthUsername` and :php:`basicAuthPassword`.
- Re-test URL using :php:`HttpAuthUtility`.

No statistics shown
-------------------

Checklist:

- Ensure OpenAI key is configured (admin key preferred for org usage endpoint).
- Check if API rate limit was hit.
- Force refresh in consumer module (if available) or clear cache.
- Confirm cache :php:`nsaiuniverse_statistics` is available.

Unexpected model behavior
-------------------------

- Confirm active default model key.
- Confirm per-provider model key.
- Validate option values (temperature/tokens) are in expected range.

.. _troubleshooting-ops:

Operational guidance
====================

- Use separate API keys per environment where possible.
- Rotate keys after staff/vendor access changes.
- Monitor usage trends and adjust limits before cost spikes.

.. _troubleshooting-support:

Support information
===================

When escalating an issue, provide:

- TYPO3 version
- extension version
- provider name and model
- timestamp and scope/module
- sanitized error message from logs
