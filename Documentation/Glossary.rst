.. include:: /Includes.rst.txt

.. _glossary:

========
Glossary
========

AI provider
  External service that processes prompts and returns responses
  (for example OpenAI, Gemini, Anthropic, Mistral).

Model
  Specific AI model identifier within a provider.

Embedding
  Numeric vector representation of text used for similarity and semantic tasks.

Request orchestration
  The process of building payloads, sending requests, and parsing responses.

Basic Auth
  HTTP authentication using username and password, encoded as
  `Authorization: Basic ...`.

Cache
  Local storage used to reduce repeated API calls and improve response times.

Service layer
  Internal reusable classes used by other extensions rather than direct
  frontend output.
