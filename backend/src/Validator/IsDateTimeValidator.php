<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsDateTimeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
    	try {
            new \DateTime($value);
        } catch (\Exception $e) {      
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
