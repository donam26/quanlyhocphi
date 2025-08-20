<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    /**
     * Boot the auditable trait
     */
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            $model->auditCreated();
        });

        static::updated(function ($model) {
            $model->auditUpdated();
        });

        static::deleted(function ($model) {
            $model->auditDeleted();
        });
    }

    /**
     * Audit model creation
     */
    protected function auditCreated()
    {
        AuditLog::log(
            get_class($this),
            $this->getKey(),
            'created',
            null,
            $this->getAuditableAttributes(),
            $this->getAuditMetadata()
        );
    }

    /**
     * Audit model update
     */
    protected function auditUpdated()
    {
        $changes = $this->getChanges();
        $original = [];
        
        foreach (array_keys($changes) as $key) {
            $original[$key] = $this->getOriginal($key);
        }

        if (!empty($changes)) {
            AuditLog::log(
                get_class($this),
                $this->getKey(),
                'updated',
                $original,
                $changes,
                $this->getAuditMetadata()
            );
        }
    }

    /**
     * Audit model deletion
     */
    protected function auditDeleted()
    {
        AuditLog::log(
            get_class($this),
            $this->getKey(),
            'deleted',
            $this->getAuditableAttributes(),
            null,
            $this->getAuditMetadata()
        );
    }

    /**
     * Log custom action
     */
    public function auditAction(string $action, array $metadata = [])
    {
        AuditLog::log(
            get_class($this),
            $this->getKey(),
            $action,
            null,
            null,
            array_merge($this->getAuditMetadata(), $metadata)
        );
    }

    /**
     * Get attributes to audit
     */
    protected function getAuditableAttributes()
    {
        $attributes = $this->getAttributes();
        
        // Remove sensitive fields
        $hidden = $this->getHidden();
        foreach ($hidden as $field) {
            unset($attributes[$field]);
        }
        
        // Remove timestamps if not needed
        if (property_exists($this, 'auditTimestamps') && !$this->auditTimestamps) {
            unset($attributes['created_at'], $attributes['updated_at']);
        }
        
        return $attributes;
    }

    /**
     * Get audit metadata
     */
    protected function getAuditMetadata()
    {
        $metadata = [];
        
        // Add related model info if available
        if (method_exists($this, 'getAuditRelatedInfo')) {
            $metadata['related'] = $this->getAuditRelatedInfo();
        }
        
        // Add custom metadata
        if (property_exists($this, 'auditMetadata')) {
            $metadata = array_merge($metadata, $this->auditMetadata);
        }
        
        return $metadata;
    }

    /**
     * Get audit logs for this model
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'model_id')
            ->where('model_type', get_class($this))
            ->orderBy('created_at', 'desc');
    }
}
