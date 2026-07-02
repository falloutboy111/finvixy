<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Versioned, DB-backed knowledge block (editable without a redeploy).
 * Bump `version` whenever `content` changes — caches key on it.
 */
class KnowledgeBlock extends Model
{
    protected $fillable = ['key', 'version', 'active', 'content'];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'active'  => 'boolean',
            'content' => 'array',
        ];
    }
}
