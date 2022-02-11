<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class InArrayValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
    	$pass = false;

        foreach ($constraint->checkArray as $element) {
        	if ($value === $element) {
        		$pass = true;
        		break;
        	}
        }

        if (!$pass) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
