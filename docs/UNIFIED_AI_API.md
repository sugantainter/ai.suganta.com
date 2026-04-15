# Unified AI API

This API exposes unified endpoints for OpenAI, Gemini, and Anthropic with tenant-aware auth, managed history, token tracking, and model catalog support.

## Endpoints

- `POST /api/v1/chat`
- `GET /api/v1/chat/histories?limit=20&page=1`
- `GET /api/v1/chat/history/{conversationId}?limit=100`
- `GET /api/v1/usage`
- `GET /api/v1/models`

## Request Body

```json
{
  "provider": "openai",
  "model": "gpt-4o-mini",
  "conversation_id": 123,
  "subject": "Backend API planning",
  "purpose": "support",
  "save_history": true,
  "stream": false,
  "temperature": 0.7,
  "max_tokens": 512,
  "fallback_providers": ["gemini", "anthropic"],
  "messages": [
    { "role": "system", "content": "You are helpful." },
    { "role": "user", "content": "Summarize this API." }
  ]
}
```

## Response (non-stream)

```json
{
  "conversation_id": 123,
  "provider": "openai",
  "model": "gpt-4o-mini",
  "message": "Unified API summary...",
  "usage": {
    "prompt_tokens": 10,
    "completion_tokens": 20,
    "total_tokens": 30
  }
}
```

## Response (stream)

When `stream=true`, response is sent as Server-Sent Events (`text/event-stream`) with:

- `data: {"type":"message","content":"..."}`
- `data: [DONE]`

## Authentication

The API supports two auth modes:

1. `X-API-Key: <tenant_api_key>`
2. Authenticated user session/token (same auth source as your Suganta auth endpoint)

If both are absent/invalid, request is rejected.

## Tenant API key management

Store keys in `ai_api_keys` as SHA-256 hashes:

1. Generate a random secret in your backend/admin panel.
2. Save `hash('sha256', $plainTextKey)` in `ai_api_keys.key_hash`.
3. Return the plaintext key once to the tenant.

## Provider key management per tenant

Store tenant provider credentials in `ai_provider_credentials`.

- `provider`: `openai | gemini | anthropic`
- `encrypted_api_key`: encrypted via Laravel `encrypted` cast
- `tenant_id` + `provider` is unique

If a tenant credential is absent, service falls back to env provider keys.

## Rate limiting

Each API key can define custom per-minute limits using `ai_api_keys.rate_limit_per_minute`.
Global default uses `AI_API_DEFAULT_RATE_LIMIT`.

## Usage tracking

- Per-request logs: `ai_request_logs`
- Aggregate tokens: `ai_user_usages`
- Conversation history: `ai_conversations` + `ai_messages`
- Model catalog: `ai_models`

## AI SQL Database

All AI tables are configured to use dedicated SQL connection `ai_mysql`, with database name `ai_suganta`.

- `AI_DB_CONNECTION=ai_mysql`
- `AI_DB_DATABASE=ai_suganta`
- Credentials configured via `AI_DB_HOST`, `AI_DB_PORT`, `AI_DB_USERNAME`, `AI_DB_PASSWORD`

## Model catalog

All provider models are centrally managed in `ai_models`:

- `provider`, `model_key`, `display_name`
- feature flags (`supports_streaming`, `supports_vision`)
- defaults and metadata per model

Use `GET /api/v1/models` to fetch active models.

## Managed history

- Use `conversation_id` in `POST /chat` to continue an existing thread.
- If `conversation_id` is omitted, a new conversation is created.
- Use `save_history=false` to skip persistence for one request.
- List all conversations via `GET /chat/histories`.
- Read history via `GET /chat/history/{conversationId}`.

## Required env vars

- `AI_DEFAULT_PROVIDER`, `AI_DEFAULT_MODEL`, `AI_FALLBACK_PROVIDERS`
- `OPENAI_API_KEY`, `GEMINI_API_KEY`, `ANTHROPIC_API_KEY`
- `AI_DB_CONNECTION`, `AI_DB_DATABASE`, `AI_DB_HOST`, `AI_DB_PORT`, `AI_DB_USERNAME`, `AI_DB_PASSWORD`
- `AI_USAGE_DB_CONNECTION`, `AI_HISTORY_DB_CONNECTION`
