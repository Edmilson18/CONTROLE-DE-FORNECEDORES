<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['setup_token_hash'])]
class AccountAccessRequest extends Model
{
    protected $fillable = [
        'name', 'email', 'message', 'status', 'response_message', 'reviewed_by',
        'reviewed_at', 'setup_token_hash', 'setup_token_expires_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'setup_token_expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
