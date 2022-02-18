<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\SecurityCardService;
use App\Service\CryptService;

class PinController extends AbstractController
{
    /**
     * @Route(
     *     name="setPin",
     *     path="set-pin",
     *     methods={"GET"}
     * )
     */
    public function setPin(SecurityCardService $securityCardService, Request $request)
    {
        if ($errorCode = $securityCardService->checkCard()) {
            return new Response('<h3>Ubacite karticu.</h3>', Response::HTTP_FORBIDDEN);
        }

        $message = $this->get('session')->getFlashBag()->get('message')[0] ?? null;

        return new Response(
            ($message ?? '') .
            '<form method="POST" action="/set-pin-post">
                <input name="pin" type="text" placeholder="Unesite pin">
                <input type="submit" value="Potvrdi">
            </form>',
            Response::HTTP_OK
        );
    }

    /**
     * @Route(
     *     name="setPinPost",
     *     path="set-pin-post",
     *     methods={"POST"}
     * )
     */
    public function setPinPost(SecurityCardService $securityCardService, CryptService $cryptService, Request $request)
    {
        if ($errorCode = $securityCardService->checkCard()) {
            return $this->redirectToRoute('setPin');
        }

        if(!($pin = $request->get('pin'))){
            $this->addFlash('message', 'Neispravan pin!');
            return $this->redirectToRoute('setPin');
        }

        if (!$securityCardService->checkPin($cryptService->hashHmacMd5($pin, $_ENV['APP_SECRET']))) {
            $securityCardService->setPin(null);
            $this->addFlash('message', 'Neispravan pin!');

            return $this->redirectToRoute('setPin');
        }

        $securityCardService->setPin($pin);
        $this->addFlash('message', 'Uspešno ste uneli pin!');

        return $this->redirectToRoute('setPin');
    }

    /**
     * @Route(
     *     name="pin",
     *     path="api/pin",
     *     methods={"POST"}
     * )
     */
    public function pin(SecurityCardService $securityCardService, CryptService $cryptService, Request $request)
    {
    	if ($errorCode = $securityCardService->checkCard()) {
        	return new JsonResponse($errorCode, Response::HTTP_FORBIDDEN);
    	}

	    $pin = $request->getContent();

        if (!$securityCardService->checkPin($cryptService->hashHmacMd5($pin, $_ENV['APP_SECRET']))) {
            return new JsonResponse("2100", Response::HTTP_UNAUTHORIZED);
        }

	    $securityCardService->setPin($pin);

        return new JsonResponse("0100", Response::HTTP_OK);
    }
}
