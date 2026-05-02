<?php

declare(strict_types=1);

namespace NakoPay\Resource;

use NakoPay\Client;

abstract class Resource
{
    public function __construct(protected Client $client) {}
}
