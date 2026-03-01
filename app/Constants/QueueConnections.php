<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Queue connection constants for application queues
 */
class QueueConnections
{
    /**
     * Redis queue connection
     */
    public const REDIS = 'redis';

    /**
     * Database queue connection
     */
    public const DATABASE = 'database';

    /**
     * Sync queue connection (for testing)
     */
    public const SYNC = 'sync';
}
