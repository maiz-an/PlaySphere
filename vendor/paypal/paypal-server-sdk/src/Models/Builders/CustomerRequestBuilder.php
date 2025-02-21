<?php

declare(strict_types=1);

/*
 * PaypalServerSdkLib
 *
 * This file was automatically generated by APIMATIC v3.0 ( https://www.apimatic.io ).
 */

namespace PaypalServerSdkLib\Models\Builders;

use Core\Utils\CoreHelper;
use PaypalServerSdkLib\Models\CustomerRequest;

/**
 * Builder for model CustomerRequest
 *
 * @see CustomerRequest
 */
class CustomerRequestBuilder
{
    /**
     * @var CustomerRequest
     */
    private $instance;

    private function __construct(CustomerRequest $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Initializes a new customer request Builder object.
     */
    public static function init(): self
    {
        return new self(new CustomerRequest());
    }

    /**
     * Sets id field.
     */
    public function id(?string $value): self
    {
        $this->instance->setId($value);
        return $this;
    }

    /**
     * Sets merchant customer id field.
     */
    public function merchantCustomerId(?string $value): self
    {
        $this->instance->setMerchantCustomerId($value);
        return $this;
    }

    /**
     * Initializes a new customer request object.
     */
    public function build(): CustomerRequest
    {
        return CoreHelper::clone($this->instance);
    }
}
