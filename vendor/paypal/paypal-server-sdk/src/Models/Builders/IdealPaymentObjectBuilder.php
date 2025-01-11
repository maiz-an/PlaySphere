<?php

declare(strict_types=1);

/*
 * PaypalServerSdkLib
 *
 * This file was automatically generated by APIMATIC v3.0 ( https://www.apimatic.io ).
 */

namespace PaypalServerSdkLib\Models\Builders;

use Core\Utils\CoreHelper;
use PaypalServerSdkLib\Models\IdealPaymentObject;

/**
 * Builder for model IdealPaymentObject
 *
 * @see IdealPaymentObject
 */
class IdealPaymentObjectBuilder
{
    /**
     * @var IdealPaymentObject
     */
    private $instance;

    private function __construct(IdealPaymentObject $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Initializes a new ideal payment object Builder object.
     */
    public static function init(): self
    {
        return new self(new IdealPaymentObject());
    }

    /**
     * Sets name field.
     */
    public function name(?string $value): self
    {
        $this->instance->setName($value);
        return $this;
    }

    /**
     * Sets country code field.
     */
    public function countryCode(?string $value): self
    {
        $this->instance->setCountryCode($value);
        return $this;
    }

    /**
     * Sets bic field.
     */
    public function bic(?string $value): self
    {
        $this->instance->setBic($value);
        return $this;
    }

    /**
     * Sets iban last chars field.
     */
    public function ibanLastChars(?string $value): self
    {
        $this->instance->setIbanLastChars($value);
        return $this;
    }

    /**
     * Initializes a new ideal payment object object.
     */
    public function build(): IdealPaymentObject
    {
        return CoreHelper::clone($this->instance);
    }
}
