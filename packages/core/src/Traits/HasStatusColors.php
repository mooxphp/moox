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
            'active', 'published', 'approved', 'completed', 'verified' => 'success',
            'inactive', 'not_translated', 'expired' => 'gray',
            'archived', 'deleted', 'rejected', 'cancelled', 'blocked' => 'danger',
            'draft', 'processing' => 'info',
            'scheduled', 'pending', 'suspended', 'unverified' => 'warning',
            'waiting' => 'primary',
            default => 'gray',
        };
    }
}
