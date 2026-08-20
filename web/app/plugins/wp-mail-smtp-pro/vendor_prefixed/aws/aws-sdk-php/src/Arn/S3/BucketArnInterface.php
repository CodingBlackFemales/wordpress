<?php

namespace WPMailSMTP\Vendor\Aws\Arn\S3;

use WPMailSMTP\Vendor\Aws\Arn\ArnInterface;
/**
 * @internal
 */
interface BucketArnInterface extends ArnInterface
{
    public function getBucketName();
}
