<?php

namespace WPMailSMTP\Vendor\Aws\Auth\Exception;

use WPMailSMTP\Vendor\Aws\HasMonitoringEventsTrait;
use WPMailSMTP\Vendor\Aws\MonitoringEventsInterface;
/**
 * Represents an error when attempting to resolve authentication.
 */
class UnresolvedAuthSchemeException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
