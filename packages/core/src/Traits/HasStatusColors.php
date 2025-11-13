<?php

namespace Moox\Core\Traits;

trait HasStatusColors
{
    /**
     * Get the color for a given status
     */
    public static function getStatusColor(string $status): string
    {
        return match (strtolower($status)) {
            'active', 'published', 'approved', 'completed', 'verified', 'success', 'done' => 'success',
            'inactive', 'not_translated', 'expired', 'maintenance', 'offline', 'locked', 'readonly' => 'gray',
            'archived', 'deleted', 'rejected', 'cancelled', 'blocked', 'error', 'failed' => 'danger',
            'draft', 'processing', 'new', 'created' => 'info',
            'scheduled', 'pending', 'suspended', 'unverified', 'updated', 'modified' => 'warning',
            'waiting' => 'primary',
            default => 'gray',
        };
    }
}
