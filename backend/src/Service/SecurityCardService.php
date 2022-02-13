<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class SecurityCardService
{
    public function __construct(
    	RequestStack $requestStack
    ) {
        $this->requestStack = $requestStack;
    }

    public function cardValidation(): array
    {
    	if ($errorCode = $this->checkCard()) {
    		return [
    			'errors' => [
    				$errorCode
    			]
    		];
    	}

    	if (!$this->getPin()) {
    		return [
    			'errors' => [
    				'1500'
    			]
    		];
    	}

    	if (!$this->checkPin()) {
    		return [
    			'errors' => [
    				'2100'
    			]
    		];
    	}

    	return [];
    }

    public function checkCard($securityCardNumber = null): ?string
    {
        return null;
    	if (!$securityCardNumber) {
    		$securityCardNumber = $_ENV['SECURITY_CARD_NUMBER'];
    	}

    	try {
			$scard = new \CkSCard();
			$json = new \CkJsonObject();
    	} catch (Exception $e) {
    		return '2230';
    	}

		// First establish a context to the PC/SC Resource Manager
		$success = $scard->EstablishContext('user');
		if ($success == false) {
		    // print $scard->lastErrorText() . "\n";
		    return '2210';
		}

		// Get JSON containing information about the smartcards currently inserted into readers.
		// This also includes information about USB security tokens.
		$success = $scard->FindSmartcards($json);
		if ($success == false) {
		    // print $scard->lastErrorText() . "\n";
		    return '2220';
		}

		$json->put_EmitCompact(false);
		$jsonString = $json->emit();

		$data = json_decode($jsonString, true);

		$insertedCardNumber = $data['reader']['0']['card']['atr'] ?? null;

		if (!$insertedCardNumber || $insertedCardNumber != $securityCardNumber) {
			return '1300';
		}

		return null;
    }

    public function checkPin($pin = null): bool
    {
    	if (!$pin) {
    		$pin = $_ENV['SECURITY_CARD_PIN'];
    	}

	    $session = $this->requestStack->getSession();

    	return $session->get('SecurityCardPin', null) == $pin;
	}

    public function getPin(): ?string
    {
	    $session = $this->requestStack->getSession();

    	return $session->get('SecurityCardPin', null);
	}

    public function setPin(?string $pin): void
    {
	    $session = $this->requestStack->getSession();
		$session->set('SecurityCardPin', $pin);
	}
}
