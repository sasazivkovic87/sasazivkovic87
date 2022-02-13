<?php

namespace App\Controller;

use App\Entity\InvoiceResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index()
    {
        // return $this->render('index.html.twig');

        return new Response("Not Found", Response::HTTP_NOT_FOUND);
    }


    /**
     * @Route(
     *     name="verify",
     *     path="/{serialNumber}/{invoiceId}/{signature}",
     *     methods={"GET"}
     * )
     */
    public function verify(EntityManagerInterface $entityManager, string $serialNumber, int $invoiceId, string $signature)
    {
        $thisUrl = $_ENV['APP_URL'] . '/' . $serialNumber . '/' . $invoiceId . '/' . $signature;

        $invoiceResponse = $entityManager->getRepository(InvoiceResponse::class)->findOneBy(['ecsdVerificationUrl' => $thisUrl]);
        
        if (!$invoiceResponse) {
            return new Response("Not Found", Response::HTTP_NOT_FOUND);
        }

        if (!$invoiceResponse->getVcsdVerificationUrl()) {
            return new Response("VPFR error", Response::HTTP_NOT_FOUND);
        }

        return $this->redirect($invoiceResponse->getVcsdVerificationUrl());
    }
}
