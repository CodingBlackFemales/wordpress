<?php

namespace WPMailSMTP\Vendor\Aws\Arn;

/**
 * @internal
 */
interface AccessPointArnInterface extends ArnInterface
{
    public function getAccesspointName();
}
