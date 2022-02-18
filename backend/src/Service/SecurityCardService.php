<?php

namespace App\Service;

use App\Entity\PinVerification;
use App\Entity\Organization\Organization;
use Doctrine\ORM\EntityManagerInterface;

class SecurityCardService
{
    public function __construct(
        EntityManagerInterface $entityManager,
        CryptService $cryptService
    )
    {
        $this->entityManager = $entityManager;
        $this->cryptService =  $cryptService;
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

        $pin = $this->getPin();
    	if (!$pin) {
    		return [
    			'errors' => [
    				'1500'
    			]
    		];
    	}

    	if (!$this->checkPin($pin)) {
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
// $pkcs11 = new \CkPkcs11();

// $pkcs11->put_SharedLibPath('/usr/local/lib/pkcs11/opensc-pkcs11.so');

// $success = $pkcs11->Initialize();
// if ($success == 0) {
//     print $pkcs11->LastErrorText() . "\n";
//     exit;
// }

// $onlyTokensPresent = true;
// $json = new \CkJsonObject();
// $success = $pkcs11->Discover($onlyTokensPresent,$json);
// if ($success == 0) {
//     print $pkcs11->LastErrorText() . "\n";
//     exit;
// }

// $json->put_EmitCompact(true);
// print $json->emit() . "\n";

// // Make sure we have at least one slot.
// if ($json->SizeOfArray('slot') <= 0) {
//     print 'No occuplied slots.' . "\n";
//     exit;
// }

// // Get the ID of the 1st slot
// $slotID = $json->IntOf('slot[0].id');

// $readWrite = 1;
// $success = $pkcs11->OpenSession($slotID,$readWrite);
// if ($success == 0) {
//     print $pkcs11->LastErrorText() . "\n";
//     exit;
// }

// $userType = 1;
// $pin = '8953';
// $success = $pkcs11->Login($userType,$pin);
// if ($success == 0) {
//     print $pkcs11->LastErrorText() . "\n";
//     $success = $pkcs11->CloseSession();
//     exit;
// }
// die('test');


        return null;
    	if (!$securityCardNumber) {
    		$securityCardNumber = $_ENV['SECURITY_CARD_NUMBER'];
    	}

    	try {
			$scard = new \CkSCard();
			$json = new \CkJsonObject();
    	} catch (Exception $e) {
    		return "2230";
    	}

		// First establish a context to the PC/SC Resource Manager
		$success = $scard->EstablishContext('user');
		if ($success == false) {
		    // print $scard->lastErrorText() . "\n";
		    return "2210";
		}

		// Get JSON containing information about the smartcards currently inserted into readers.
		// This also includes information about USB security tokens.
		$success = $scard->FindSmartcards($json);
		if ($success == false) {
		    // print $scard->lastErrorText() . "\n";
		    return "2220";
		}

		$json->put_EmitCompact(false);
		$jsonString = $json->emit();

		$data = json_decode($jsonString, true);

		$insertedCardNumber = $data['reader']['0']['card']['atr'] ?? null;

		if (!$insertedCardNumber || $insertedCardNumber != $securityCardNumber) {
			return "1300";
		}

		return null;
    }

    public function checkPin(?string $pin): bool
    {
    	return $pin == $_ENV['SECURITY_CARD_PIN'];
	}

    public function getPin(): ?string
    {
        $last = $this->entityManager->getRepository(PinVerification::class)->findOneBy([], ['id' => 'DESC']);
        if (!$last) {
            return null;
        }

        $organization = $this->entityManager->getRepository(Organization::class)->findOneBy([]);
        if (!$organization) {
            return null;
        }

        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone($organization->getServerTimeZone()));

        if ($last->getValidTo() < $now) {
            return null;
        }

        return $last->getPin();
	}

    public function setPin(?string $pin): void
    {
        if (!$pin) {
            $this->entityManager->getRepository(PinVerification::class)->deleteAll();
            return;
        }

        $organization = $this->entityManager->getRepository(Organization::class)->findOneBy([]);
        if (!$organization) {
            return;
        }

        $validTo = new \DateTime();
        $validTo->setTimezone(new \DateTimeZone($organization->getServerTimeZone()));
        $validTo->modify('+16 hours');

        $pinVerification = new PinVerification();
        $pinVerification->setPin($this->cryptService->hashHmacMd5($pin, $_ENV['APP_SECRET']));
        $pinVerification->setValidTo($validTo);

        $this->entityManager->persist($pinVerification);
        $this->entityManager->flush();
	}
}
