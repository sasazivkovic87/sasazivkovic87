<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsDateTime extends Constraint
{
    public $message = null;
}
