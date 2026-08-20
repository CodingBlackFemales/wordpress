<?php

namespace WPMailSMTP\Vendor\Aws\Exception;

use WPMailSMTP\Vendor\Aws\HasMonitoringEventsTrait;
use WPMailSMTP\Vendor\Aws\MonitoringEventsInterface;
class TokenException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
