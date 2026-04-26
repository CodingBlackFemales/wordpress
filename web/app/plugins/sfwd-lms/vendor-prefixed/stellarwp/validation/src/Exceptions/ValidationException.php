<?php

declare(strict_types=1);

namespace StellarWP\Learndash\StellarWP\Validation\Exceptions;

use Exception;
use StellarWP\Learndash\StellarWP\Validation\Exceptions\Contracts\ValidationExceptionInterface;

class ValidationException extends Exception implements ValidationExceptionInterface
{

}
