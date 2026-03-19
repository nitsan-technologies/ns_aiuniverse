[![Latest Stable Version](https://img.shields.io/badge/Stable-1.0.0-success)](https://extensions.typo3.org/extension/ns_aiuniverse/)
[![AI Universe Github](https://img.shields.io/badge/AI--Universe-informational?logo=github)](https://github.com/nitsan-technologies/ns_aiuniverse)
[![TYPO3 13](https://img.shields.io/badge/TYPO3-13-important.svg?logo=typo3)](https://get.typo3.org/version/13)
[![TYPO3 12](https://img.shields.io/badge/TYPO3-12-important.svg?logo=typo3)](https://get.typo3.org/version/12)
[![TYPO3 11](https://img.shields.io/badge/TYPO3-11-important.svg?logo=typo3)](https://get.typo3.org/version/11)
[![PHP](https://img.shields.io/badge/PHP-7.4%20to%208.4-777BB4?logo=php&logoColor=white)](https://www.php.net/)

# TYPO3 Extension `ns_aiuniverse`

AI Universe is the shared AI foundation layer for TYPO3 extensions.
It centralizes AI provider communication, model selection, request handling,
statistics preparation, and utility functions so other extensions can build AI
features faster and with consistent behavior.

It includes these features:

* **Multi-provider AI request flow:** Unified request/response handling for OpenAI, Gemini, Azure, Claude/Anthropic, DeepSeek, xAI, Mistral, and custom LLM endpoints.

* **Developer-first service layer:** Reusable classes such as `AiRequestService`, `BaseClient`, and `AiEngineConfiguration` for straightforward extension integration.

* **Usage statistics pipeline:** OpenAI usage retrieval, normalization, chart-ready output generation, and caching via TYPO3 cache framework.

* **Operational helpers:** Basic Auth utility for protected URL content retrieval and configuration helpers for extension settings and TYPO3 version checks.

* **Production documentation:** Complete documentation for developers, editors/admins, and non-technical stakeholders under `Documentation/`.

> This extension is a service/base layer and not a standalone frontend plugin.

| | URL |
|------------------|---------------------------------------------------------------|
| **Repository:** | https://github.com/nitsan-technologies/ns_aiuniverse |
| **Issues:** | https://t3planet.com/support |
| **Composer:** | https://packagist.org/packages/nitsan/ns-aiuniverse |
| **TER:** | https://extensions.typo3.org/extension/ns_aiuniverse/ |
| **Documentation:** | https://docs.typo3.org/p/nitsan/ns-aiuniverse/main/en-us/ |
| **Support:** | https://t3planet.com/support |

## Compatibility

| AI Universe Version | TYPO3 Compatibility | PHP Version | Support Level |
|---------------------|---------------------|-------------|---------------|
| v1.x | 11.0.0 - 13.4.99 | 7.4 - 8.4 | Features, bugfixes, compatibility updates |

## Quick Start

Install via Composer:

```bash
composer require nitsan/ns-aiuniverse
```

Then:

1. Activate extension `ns_aiuniverse` in TYPO3 backend.
2. Configure provider API key(s) in extension configuration.
3. Set default provider and model.
4. Integrate with `NITSAN\NsAiUniverse\Service\AiRequestService`.

## Notes

- Provider capability and model availability can change over time.
- Keep API keys restricted and rotate them regularly.
- For path-sensitive tooling (Docker, docs render), quote paths if your
  workspace contains spaces.
