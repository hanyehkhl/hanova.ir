<?php
// Copy to config/llm.php (or set env vars). Never commit real keys.

return [
    'vendor'      => getenv('LLM_VENDOR') ?: 'gapgpt',
    'base_url'    => getenv('LLM_BASE_URL') ?: 'https://api.gapgpt.app/v1',
    'api_key'     => getenv('LLM_API_KEY') ?: '',
    'model'       => getenv('LLM_MODEL') ?: 'gpt-4.1-mini',
    'timeout'     => (int) (getenv('LLM_TIMEOUT_SECONDS') ?: 30),
    'temperature' => (float) (getenv('LLM_TEMPERATURE') ?: 0.2),
    'max_tokens'  => (int) (getenv('LLM_MAX_TOKENS') ?: 1024),
];
