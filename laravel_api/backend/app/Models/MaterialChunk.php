<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToInstitute;

class MaterialChunk extends Model
{
    use HasFactory, BelongsToInstitute;

    protected $fillable = [
        'institute_id',
        'material_id',
        'grade',
        'medium',
        'subject',
        'chunk_type',
        'page_number',
        'year',
        'term',
        'question_number',
        'content',
        'embedding_model',
        'dimensions',
        'embedding',
        'word_count',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'year' => 'integer',
        'dimensions' => 'integer',
        'word_count' => 'integer',
    ];

    /**
     * The parent learning material this chunk belongs to.
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class, 'material_id');
    }

    /**
     * Pack an array of floats into a 32-bit single precision binary BLOB.
     */
    public function setEmbeddingVector(array $vector): self
    {
        $this->dimensions = count($vector);
        $this->embedding = pack('f*', ...$vector);
        return $this;
    }

    /**
     * Unpack binary BLOB back into an array of floats.
     *
     * @return array<int, float>
     */
    public function getEmbeddingVector(): array
    {
        if (empty($this->embedding)) {
            return [];
        }
        $floats = unpack('f*', $this->embedding);
        return $floats ? array_values($floats) : [];
    }
}
