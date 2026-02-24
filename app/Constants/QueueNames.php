<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Queue name constants for application queues
 * 
 * @package App\Constants
 */
class QueueNames
{
    /**
     * Email notification jobs
     */
    public const EMAILS = 'emails';
    
    /**
     * Default application jobs
     */
    public const DEFAULT = 'default';
}
