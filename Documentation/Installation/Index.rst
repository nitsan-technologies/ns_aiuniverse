.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

.. contents::
   :local:
   :depth: 2

.. _installation-requirements:

Requirements
============

- TYPO3: 11.0 up to 13.4
- PHP: 7.4 up to 8.4
- Composer-based TYPO3 setup recommended

.. _installation-composer:

Install with Composer
=====================

.. code-block:: bash
   :caption: Install extension

   composer require nitsan/ns-aiuniverse

.. _installation-activate:

Activate extension
==================

1. Open TYPO3 backend.
2. Go to :guilabel:`Admin Tools > Extensions`.
3. Activate :t3ext:`ns_aiuniverse`.

.. _installation-post-install:

Post-install checks
===================

After activation, verify:

- Extension is listed as active.
- Extension configuration is available in
  :guilabel:`Admin Tools > Settings > Extension Configuration`.
- Cache configuration :php:`nsaiuniverse_statistics` is present
  (registered by :file:`ext_localconf.php`).

.. _installation-first-config:

First configuration
===================

At minimum, set:

- :php:`defaultModel` (provider family)
- Provider API key (for example :php:`openai_api_key`)
- Provider default model (for example :php:`openai_model`)

For details, continue with :ref:`configuration`.
