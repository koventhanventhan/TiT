<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Models
    |--------------------------------------------------------------------------
    |
    | Official Google Gemini GA models (verified October 2026):
    | - gemini-embedding-2: multimodal and text embeddings supporting 768 / 1536 / 3072 dimensions. No shutdown date announced.
    | - gemini-3.6-flash: current production GA Flash model (gemini-3.5-flash shutdown is N/A and routes upstream to 3.6-flash).
    | - gemini-3.5-flash-lite: high-throughput fallback model. No shutdown date announced.
    | - text-embedding-004: legacy embedding model, shut down on January 14, 2026.
    |
    */
    'models' => [
        'embedding' => env('RAG_EMBEDDING_MODEL', 'gemini-embedding-2'),
        'embedding_dimensions' => (int) env('RAG_EMBEDDING_DIMENSIONS', 768),
        'llm' => env('RAG_LLM_MODEL', 'gemini-3.6-flash'),
        'ocr' => env('RAG_OCR_MODEL', 'gemini-3.6-flash'),
        'ocr_fallback' => env('RAG_OCR_FALLBACK_MODEL', 'gemini-3.5-flash-lite'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OCR Engine Settings
    |--------------------------------------------------------------------------
    |
    | Deterministic transcription settings for vision OCR:
    | - temperature = 0.0: greedy token decoding for strictly reproducible transcription
    | - page_timeout: seconds per page job (240s minimum recommended for complex scans)
    |
    */
    'ocr' => [
        'temperature' => (float) env('RAG_OCR_TEMPERATURE', 0.0),
        'page_timeout' => (int) env('RAG_OCR_PAGE_TIMEOUT', 240),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retrieval & Similarity Threshold
    |--------------------------------------------------------------------------
    |
    | Two-level relevance thresholding:
    | - min_candidate_similarity: determines candidate chunks eligible for LLM context (default 0.50).
    | - similarity_threshold / min_similarity: confident answer threshold (default 0.70).
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Global Master Feature Switch
    |--------------------------------------------------------------------------
    |
    | Global switch for RAG Study Assistant. Defaults to false (OFF) when missing.
    | Enabled only when explicitly turned on by admin or env.
    |
    */
    'enabled' => filter_var(env('RAG_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'retrieval' => [
        'min_candidate_similarity' => (float) env('RAG_MIN_CANDIDATE_SIMILARITY', 0.50),
        'similarity_threshold' => (float) env('RAG_SIMILARITY_THRESHOLD', 0.70),
        'min_similarity' => (float) env('RAG_MIN_SIMILARITY', 0.70),
        'top_k' => (int) env('RAG_TOP_K', 5),
        'hybrid_keyword_weight' => (float) env('RAG_HYBRID_KEYWORD_WEIGHT', 0.20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limits & Safety Quotas
    |--------------------------------------------------------------------------
    |
    | Daily and per-minute throttling per student to prevent abuse and manage API costs.
    |
    */
    'limits' => [
        'per_minute' => (int) env('RAG_RATE_LIMIT_PER_MINUTE', 10),
        'daily_questions_per_student' => (int) env('RAG_DAILY_QUESTION_LIMIT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM Generation & Child Safety Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for Gemini LLM responses, token limits, timeouts, and child-safe
    | strict harm category thresholds.
    |
    */
    'llm' => [
        'temperature' => (float) env('RAG_LLM_TEMPERATURE', 0.2),
        'top_p' => (float) env('RAG_LLM_TOP_P', 0.8),
        'max_output_tokens' => (int) env('RAG_LLM_MAX_OUTPUT_TOKENS', 1500),
        'request_timeout' => (int) env('RAG_LLM_REQUEST_TIMEOUT', 45),
        'retry_429_max_attempts' => (int) env('RAG_429_MAX_ATTEMPTS', 4),
        'retry_429_max_wait' => (int) env('RAG_429_MAX_WAIT', 60),
        'safety_settings' => [
            [
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => env('RAG_SAFETY_HARASSMENT', 'BLOCK_LOW_AND_ABOVE'),
            ],
            [
                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                'threshold' => env('RAG_SAFETY_HATE_SPEECH', 'BLOCK_LOW_AND_ABOVE'),
            ],
            [
                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                'threshold' => env('RAG_SAFETY_SEXUALLY_EXPLICIT', 'BLOCK_LOW_AND_ABOVE'),
            ],
            [
                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => env('RAG_SAFETY_DANGEROUS_CONTENT', 'BLOCK_LOW_AND_ABOVE'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Student Support & Distress Helplines
    |--------------------------------------------------------------------------
    |
    | Configured crisis contacts and helplines shown when a student expresses distress.
    | Empty by default: verified helpline names and numbers must be supplied via .env.
    | Only helplines with a non-empty configured number are shown to students.
    |
    */
    'safety' => [
        'helplines' => [
            [
                'name' => env('RAG_HELPLINE_PRIMARY_NAME', ''),
                'number' => env('RAG_HELPLINE_PRIMARY_NUMBER', ''),
                'hours' => env('RAG_HELPLINE_PRIMARY_HOURS', ''),
            ],
            [
                'name' => env('RAG_HELPLINE_SECONDARY_NAME', ''),
                'number' => env('RAG_HELPLINE_SECONDARY_NUMBER', ''),
                'hours' => env('RAG_HELPLINE_SECONDARY_HOURS', ''),
            ],
            [
                'name' => env('RAG_HELPLINE_INSTITUTE_NAME', 'Institute / Teacher Support'),
                'number' => env('RAG_HELPLINE_INSTITUTE_NUMBER', ''),
                'hours' => env('RAG_HELPLINE_INSTITUTE_HOURS', ''),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Chunking Defaults
    |--------------------------------------------------------------------------
    */
    'chunking' => [
        'target_word_count' => (int) env('RAG_CHUNK_TARGET_WORDS', 300),
        'overlap_words' => (int) env('RAG_CHUNK_OVERLAP_WORDS', 40),
        'embedding_batch_size' => (int) env('RAG_EMBEDDING_BATCH_SIZE', 25),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ingestion Limits & Cost Controls
    |--------------------------------------------------------------------------
    */
    'ingestion' => [
        'max_pages_per_material' => (int) env('RAG_MAX_PAGES_PER_MATERIAL', 50),
        'max_file_size_mb' => (int) env('RAG_MAX_FILE_SIZE_MB', 50),
        'ocr_max_pages' => (int) env('RAG_OCR_MAX_PAGES', 20),
        'ocr_min_words_per_page' => (int) env('RAG_OCR_MIN_WORDS_PER_PAGE', 20),
        'ocr_max_dimension' => (int) env('RAG_OCR_MAX_DIMENSION', 1600),
        'ocr_jpeg_quality' => (int) env('RAG_OCR_JPEG_QUALITY', 80),
        'ocr_total_timeout' => (int) env('RAG_OCR_TOTAL_TIMEOUT', 240),
        'download_timeout' => (int) env('RAG_DOWNLOAD_TIMEOUT', 30),
        'gemini_timeout' => (int) env('RAG_GEMINI_TIMEOUT', 45),
        'legacy_font_min_script_ratio' => (float) env('RAG_LEGACY_FONT_MIN_SCRIPT_RATIO', 0.20),
        'legacy_font_min_letters' => (int) env('RAG_LEGACY_FONT_MIN_LETTERS', 50),
        'readability_min_score' => (float) env('RAG_READABILITY_MIN_SCORE', 0.60),
        'readability_sample_words' => (int) env('RAG_READABILITY_SAMPLE_WORDS', 150),
        'ocr_enable_safeguard' => filter_var(env('RAG_OCR_ENABLE_SAFEGUARD', true), FILTER_VALIDATE_BOOLEAN),
        'ocr_completeness_diff_threshold' => (float) env('RAG_OCR_COMPLETENESS_DIFF_THRESHOLD', 0.10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Privacy & Retention
    |--------------------------------------------------------------------------
    |
    | Number of days before question and answer text fields are redacted
    | while preserving high-level telemetry and ratings for quality analytics.
    |
    */
    'privacy' => [
        'retention_days' => (int) env('RAG_PRIVACY_RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vector Store Implementation
    |--------------------------------------------------------------------------
    |
    | Pluggable vector store driver implementing VectorStoreInterface.
    | Defaults to MariaDB packed-float BLOB with cosine similarity.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Public Website Assistant Settings
    |--------------------------------------------------------------------------
    |
    | Global switch, visitor rate limits, reCAPTCHA score thresholds,
    | and caching settings for the unified public website assistant.
    |
    */
    'public_enabled' => filter_var(env('RAG_PUBLIC_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'assistant' => [
        'public_cache_ttl' => (int) env('RAG_ASSISTANT_CACHE_TTL', 300),
        'recaptcha_min_score' => (float) env('RAG_RECAPTCHA_MIN_SCORE', 0.5),
        'visitor_per_minute' => (int) env('RAG_VISITOR_PER_MINUTE', 10),
        'visitor_per_day' => (int) env('RAG_VISITOR_PER_DAY', 60),
        'visitor_daily_cap' => (int) env('RAG_VISITOR_DAILY_CAP', 500),
        'visitor_max_chars' => (int) env('RAG_VISITOR_MAX_CHARS', 200),
        'visitor_log_retention_days' => (int) env('RAG_VISITOR_RETENTION_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Grounding Post-Check Settings
    |--------------------------------------------------------------------------
    |
    | Action to take when an answer contains ungrounded terms not found in
    | retrieved context or student question:
    | - 'replace': replaces the answer with the safe localized "notes contain only..." message.
    | - 'log': logs warning only and preserves the generated answer.
    |
    */
    'grounding' => [
        'action' => env('RAG_GROUNDING_ACTION', 'replace'),
    ],

    'vector_store' => env('RAG_VECTOR_STORE', 'database'),
];