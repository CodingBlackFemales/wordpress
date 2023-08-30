<?php

namespace BuddyBoss\PlatformPro\Vendor\GuzzleHttp;

use BuddyBoss\PlatformPro\Vendor\Psr\Http\Message\MessageInterface;

interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string;
}
