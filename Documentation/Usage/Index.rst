.. include:: /Includes.rst.txt

.. _usage:

=====
Usage
=====

This guide focuses on practical operation for editors, administrators, and
non-technical stakeholders.

.. contents::
   :local:
   :depth: 2

.. _usage-admin:

For administrators
==================

Daily responsibilities:

- Keep provider API keys valid.
- Maintain default model settings.
- Monitor OpenAI usage trends.
- Keep credentials and access permissions under control.

.. _usage-admin-checklist:

Admin checklist
---------------

1. Confirm selected :php:`defaultModel`.
2. Confirm provider key is set (for selected provider).
3. Test extension-dependent AI features in your connected modules.
4. Review usage statistics regularly (cost/rate-control).

.. _usage-editors:

For editors
===========

Editors usually do not configure providers directly. They interact with
features built by other extensions that depend on AI Universe.

When AI features fail in a backend module:

- Retry once.
- Capture exact error text.
- Inform administrator with module/page context.

.. _usage-non-technical:

For non-technical stakeholders
==============================

AI Universe helps organizations by:

- Reducing duplicated AI integration work across extensions.
- Centralizing provider and model governance.
- Improving consistency of AI capabilities across teams.

.. _usage-expectations:

What to expect operationally
============================

- Some providers have rate limits and temporary outages.
- Model behavior can differ between providers and versions.
- Statistics data may be cached and not always real-time.

.. _usage-boundaries:

Known boundaries
================

- No standalone frontend plugin is provided by this extension.
- This package is a service layer; UI features come from dependent extensions.
