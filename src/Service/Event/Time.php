<?php
namespace GibsonOS\Module\Hc\Service\Event;

class Time extends AbstractEvent
{
    /**
     * @param int $seconds
     */
    public function sleep($seconds)
    {
        sleep($seconds);
    }

    /**
     * @param int $microseconds
     */
    public function usleep($microseconds)
    {
        usleep($microseconds);
    }
}