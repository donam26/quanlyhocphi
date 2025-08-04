<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'status',
        'total_count',
        'processed_count',
        'sent_count',
        'failed_count',
        'skipped_count',
        'course_ids',
        'enrollment_ids',
        'errors',
        'created_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'course_ids' => 'array',
        'enrollment_ids' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relationship với User (người tạo batch)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Tính phần trăm hoàn thành
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_count === 0) return 0;
        return round(($this->processed_count / $this->total_count) * 100, 2);
    }

    /**
     * Tính tỷ lệ thành công
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->processed_count === 0) return 0;
        return round(($this->sent_count / $this->processed_count) * 100, 2);
    }

    /**
     * Kiểm tra xem batch đã hoàn thành chưa
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Kiểm tra xem batch có lỗi không
     */
    public function hasErrors(): bool
    {
        return $this->failed_count > 0;
    }

    /**
     * Lấy thời gian xử lý
     */
    public function getProcessingTimeAttribute(): ?string
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        $diff = $this->completed_at->diffInSeconds($this->started_at);
        
        if ($diff < 60) {
            return $diff . ' giây';
        } elseif ($diff < 3600) {
            return round($diff / 60, 1) . ' phút';
        } else {
            return round($diff / 3600, 1) . ' giờ';
        }
    }

    /**
     * Tạo batch mới
     */
    public static function createBatch(string $name, string $type, array $enrollmentIds, ?int $createdBy = null): self
    {
        return self::create([
            'name' => $name,
            'type' => $type,
            'total_count' => count($enrollmentIds),
            'enrollment_ids' => $enrollmentIds,
            'created_by' => $createdBy,
            'status' => 'pending',
        ]);
    }

    /**
     * Bắt đầu xử lý batch
     */
    public function start(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Scope để lấy batch đang xử lý
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope để lấy batch đã hoàn thành
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope để lấy batch của user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }
}
