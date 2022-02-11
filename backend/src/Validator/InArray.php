<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class InArray extends Constraint
{
    public $message = null;
    public $checkArray = [];
}
