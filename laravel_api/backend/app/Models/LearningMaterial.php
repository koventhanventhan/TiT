<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToInstitute;

class LearningMaterial extends Model
{
    use HasFactory, BelongsToInstitute;

    protected $fillable = [
        'title',
        'description',
        'type',
        'file_path',
        'thumbnail_path',
        'grade',
        'medium',
        'subject',
        'institute_id',
        'group',
        'file_size',
        'url',
        'teacher_id',
        'zoom_schedule_id',
        'rag_status',
        'rag_error',
        'rag_indexed_at',
    ];

    /**
     * Chunks extracted for RAG retrieval.
     */
    public function chunks()
    {
        return $this->hasMany(MaterialChunk::class, 'material_id');
    }

    protected static function booted()
    {
        // 1. On Created: notify students and dispatch RAG ingestion
        static::created(function (LearningMaterial $material) {
            try {
                \App\Services\EventNotifier::materialCreated($material);
            } catch (\Throwable $e) {
                \Log::error('Material notification failed: ' . $e->getMessage());
            }

            if ($material->type !== 'recording' && !empty($material->file_path)) {
                \App\Jobs\IngestLearningMaterialJob::dispatch($material->id)->afterCommit();
            } else {
                $material->updateQuietly(['rag_status' => 'skipped']);
            }
        });

        // 2. On Updated: re-ingest if file changed, or sync metadata if grade/medium/subject changed
        static::updated(function (LearningMaterial $material) {
            if ($material->wasChanged('file_path')) {
                if ($material->type !== 'recording' && !empty($material->file_path)) {
                    $material->updateQuietly(['rag_status' => 'pending']);
                    \App\Jobs\IngestLearningMaterialJob::dispatch($material->id)->afterCommit();
                } else {
                    \App\Models\MaterialChunk::withoutGlobalScopes()
                        ->where('material_id', $material->id)
                        ->delete();
                    $material->updateQuietly(['rag_status' => 'skipped']);
                }
            } elseif ($material->wasChanged(['grade', 'medium', 'subject'])) {
                \App\Models\MaterialChunk::withoutGlobalScopes()
                    ->where('material_id', $material->id)
                    ->update([
                        'grade' => $material->grade,
                        'medium' => $material->medium,
                        'subject' => $material->subject,
                    ]);
            }
        });

        // 3. On Deleted: remove chunks (works regardless of database cascade or soft deletes)
        static::deleted(function (LearningMaterial $material) {
            \App\Models\MaterialChunk::withoutGlobalScopes()
                ->where('material_id', $material->id)
                ->delete();
        });
    }
}

