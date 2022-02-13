<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\SecurityCardService;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index()
    {
        // return $this->render('index.html.twig');
       return $this->redirect('/documentation');
        // throw new AccessDeniedHttpException();
    }

    /**
     * @Route(
     *     name="pin",
     *     path="api/pin",
     *     methods={"POST"}
     * )
     */
    public function pin(SecurityCardService $securityCardService, Request $request)
    {
    	if ($errorCode = $securityCardService->checkCard()) {
        	return new JsonResponse($errorCode, Response::HTTP_UNPROCESSABLE_ENTITY);
    	}

	    $pin = $request->getContent();
	    $securityCardService->setPin($pin);

        return new JsonResponse("0100", Response::HTTP_OK);
    }
}
