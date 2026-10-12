<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Models
    |--------------------------------------------------------------------------
    |
    | Using current Google Gemini GA models:
    | - gemini-embedding-2: multimodal / text embedding supporting 768 / 1536 / 3072 dimensions
    | - gemini-3.5-flash: high efficiency, high accuracy, streaming LLM
    |
    */
    'models' => [
        'embedding' => env('RAG_EMBEDDING_MODEL', 'gemini-embedding-2'),
        'embedding_dimensions' => (int) env('RAG_EMBEDDING_DIMENSIONS', 768),
        'llm' => env('RAG_LLM_MODEL', 'gemini-3.5-flash'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retrieval & Similarity Threshold
    |--------------------------------------------------------------------------
    |
    | If the highest cosine similarity between the query and candidate chunks
    | is below this threshold, the assistant must return a polite fallback
    | stating the answer is not in the teacher's notes without calling the LLM.
    |
    */
    'retrieval' => [
        'similarity_threshold' => (float) env('RAG_SIMILARITY_THRESHOLD', 0.70),
        'top_k' => (int) env('RAG_TOP_K', 5),
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
    | Document Chunking Defaults
    |--------------------------------------------------------------------------
    */
    'chunking' => [
        'target_word_count' => (int) env('RAG_CHUNK_TARGET_WORDS', 400),
        'overlap_words' => (int) env('RAG_CHUNK_OVERLAP_WORDS', 50),
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
    'vector_store' => env('RAG_VECTOR_STORE', 'database'),
];
