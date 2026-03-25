<?php

namespace BuddyBossPlatformPro\GuzzleHttp;

use BuddyBossPlatformPro\Psr\Http\Message\MessageInterface;
interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message) : ?string;
}
