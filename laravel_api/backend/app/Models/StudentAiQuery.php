<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToInstitute;

class StudentAiQuery extends Model
{
    use HasFactory, BelongsToInstitute;

    protected $fillable = [
        'institute_id',
        'user_id',
        'subject',
        'question',
        'question_language',
        'answer',
        'retrieved_material_ids',
        'retrieved_chunk_ids',
        'top_similarity_score',
        'status',
        'feedback_rating',
        'feedback_comment',
        'feedback_at',
    ];

    protected $casts = [
        'retrieved_material_ids' => 'array',
        'retrieved_chunk_ids' => 'array',
        'top_similarity_score' => 'float',
        'feedback_at' => 'datetime',
    ];

    /**
     * The student who asked the question.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
