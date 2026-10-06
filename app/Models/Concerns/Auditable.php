<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($model) => $model->writeAudit('created'));
        static::updated(fn ($model) => $model->writeAudit('updated'));
        static::deleted(fn ($model) => $model->writeAudit('deleted'));

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(fn ($model) => $model->writeAudit('restored'));
        }
    }

    public function auditLabel(): string
    {
        return (string) ($this->getAttribute('name') ?? '#' . $this->getKey());
    }

    protected function writeAudit(string $event): void
    {
        $exclude = array_merge(
            ['created_at', 'updated_at', 'deleted_at', 'remember_token'],
            $this->auditExclude ?? []
        );

        $old = null;
        $new = null;

        if ($event === 'updated') {
            $new = Arr::except($this->getChanges(), $exclude);

            if (empty($new)) {
                return;
            }

            $old = Arr::only($this->getOriginal(), array_keys($new));
        } elseif ($event === 'created') {
            $new = Arr::except($this->getAttributes(), $exclude);
        } elseif ($event === 'deleted') {
            $old = Arr::except($this->getAttributes(), $exclude);
        }

        // Nilai sensitif tidak pernah disimpan.
        foreach ($this->auditHidden ?? [] as $key) {
            if (is_array($old) && array_key_exists($key, $old)) {
                $old[$key] = '***';
            }

            if (is_array($new) && array_key_exists($key, $new)) {
                $new[$key] = '***';
            }
        }

        AuditLog::record($event, $this, $old, $new, $this->auditLabel());
    }
}