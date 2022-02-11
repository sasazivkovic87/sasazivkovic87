<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DbExists extends Constraint
{
    public $message = null;
    public $class = null;
    public $field = null;
}
