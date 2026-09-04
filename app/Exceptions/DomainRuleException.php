<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

abstract class DomainRuleException extends Exception implements ShouldntReport
{
    abstract public function errorCode(): string;
}
