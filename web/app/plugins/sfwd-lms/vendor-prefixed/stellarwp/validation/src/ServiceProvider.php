<?php

declare(strict_types=1);

namespace StellarWP\Learndash\StellarWP\Validation;

use StellarWP\Learndash\StellarWP\Validation\Rules\Boolean;
use StellarWP\Learndash\StellarWP\Validation\Rules\Currency;
use StellarWP\Learndash\StellarWP\Validation\Rules\DateTime;
use StellarWP\Learndash\StellarWP\Validation\Rules\Email;
use StellarWP\Learndash\StellarWP\Validation\Rules\Exclude;
use StellarWP\Learndash\StellarWP\Validation\Rules\ExcludeIf;
use StellarWP\Learndash\StellarWP\Validation\Rules\ExcludeUnless;
use StellarWP\Learndash\StellarWP\Validation\Rules\In;
use StellarWP\Learndash\StellarWP\Validation\Rules\InStrict;
use StellarWP\Learndash\StellarWP\Validation\Rules\Integer;
use StellarWP\Learndash\StellarWP\Validation\Rules\Max;
use StellarWP\Learndash\StellarWP\Validation\Rules\Min;
use StellarWP\Learndash\StellarWP\Validation\Rules\Nullable;
use StellarWP\Learndash\StellarWP\Validation\Rules\NullableIf;
use StellarWP\Learndash\StellarWP\Validation\Rules\NullableUnless;
use StellarWP\Learndash\StellarWP\Validation\Rules\Numeric;
use StellarWP\Learndash\StellarWP\Validation\Rules\Optional;
use StellarWP\Learndash\StellarWP\Validation\Rules\OptionalIf;
use StellarWP\Learndash\StellarWP\Validation\Rules\OptionalUnless;
use StellarWP\Learndash\StellarWP\Validation\Rules\Required;
use StellarWP\Learndash\StellarWP\Validation\Rules\Size;

class ServiceProvider
{
    /**
     * @var array<class-string>
     */
    private array $validationRules = [
        Required::class,
        Min::class,
        Max::class,
        Size::class,
        Numeric::class,
        In::class,
        InStrict::class,
        Integer::class,
        Email::class,
        Currency::class,
        Exclude::class,
        ExcludeIf::class,
        ExcludeUnless::class,
        Nullable::class,
        NullableIf::class,
        NullableUnless::class,
        Optional::class,
        OptionalIf::class,
        OptionalUnless::class,
        DateTime::class,
        Boolean::class,
    ];

    /**
     * Registers the validation rules registrar with the container
     */
    public function register(): void
    {
        Config::getServiceContainer()->singleton(ValidationRulesRegistrar::class, function () {
            $register = new ValidationRulesRegistrar();

            foreach ($this->validationRules as $rule) {
                $register->register($rule);
            }

            do_action(Config::getHookPrefix() . 'register_validation_rules', $register);

            return $register;
        });
    }
}
