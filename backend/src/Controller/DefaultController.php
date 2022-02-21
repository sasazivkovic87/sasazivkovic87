<?php

namespace App\Controller;

use App\Entity\Invoice\Invoice;
use App\Entity\Invoice\EcsdResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\CsdService;
use App\Service\SecurityCardService;
use Symfony\Component\Finder\Finder;
use Doctrine\Persistence\ManagerRegistry;


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
     *     name="copyUnreadInvoices",
     *     path="copy-unread-invoices",
     *     methods={"GET"}
     * )
     */
    public function copyUnreadInvoices(CsdService $csdService, Request $request)
    {
        $message = $this->get('session')->getFlashBag()->get('message')[0] ?? null;
        $succcessMessage = $this->get('session')->getFlashBag()->get('succcessMessage')[0] ?? null;

        return new Response(
            '<h2>Pokreni kopiranje neisčitanih računa</h2>
            <form method="POST" action="/copy-unread-invoices-post">
                <button type="submit" onclick="var e=this; setTimeout(function(){ e.disabled=true; e.innerText=\'Kopiranje je u toku...\';},0); return true;">Pokreni kopiranje</button>
            </form>'
            . '<p style="color:red;">' . ($message ?? '') . '</p>'
            . '<p style="color:green;">' . ($succcessMessage ?? '') . '</p>',
            Response::HTTP_OK
        );
    }

    /**
     * @Route(
     *     name="copyUnreadInvoicesPost",
     *     path="copy-unread-invoices-post",
     *     methods={"POST"}
     * )
     */
    public function copyUnreadInvoicesPost(ManagerRegistry $doctrine, CsdService $csdService, Request $request)
    {
		$finder = new Finder();
		$finder->directories()->in('/media')->depth('== 1');

		if (!$finder->hasResults()) {
            $this->addFlash('message', 'Ne postoji ni jedna spoljašnja memorijska jedinica.');
		    return $this->redirectToRoute('copyUnreadInvoices');
		}

		foreach ($finder as $dir) {
		    $dirPath = $dir->getRealPath();
		    break;
		}

        $unreadInvoices = $doctrine->getManager()->getRepository(Invoice::class)->getAllWithoutVcsdResponse();

        if (count($unreadInvoices) == 0) {
            $this->addFlash('message', 'Nema računa za eksportovanje.');
		    return $this->redirectToRoute('copyUnreadInvoices');
        }

		$unreadInvoicesChunk = array_chunk($unreadInvoices, 1000);

		try {
    		foreach ($unreadInvoicesChunk as $invoicesChunk) {
	    		$csdService->export($invoicesChunk, $dirPath);

	    		$invoicesSql = array_map(function ($invoice) {
	    			return "'{\"invoiceId\":" . $invoice->getId() . "}'";
	    		}, $invoicesChunk);

			    $doctrine->getManager('messenger')->getConnection()->prepare("DELETE FROM messenger_messages WHERE body IN (" . implode(", ", $invoicesSql) . ")")->execute();
    		}
		} catch (\Exception $e) {
            $this->addFlash('message', 'Neuspešno kreiranje i kopiranje fajlova. Proverite ovlašćenja na folderima.');
		    return $this->redirectToRoute('copyUnreadInvoices');
		}

        $this->addFlash('succcessMessage', 'Kopiranje fajlova je uspešno završeno.');

        return $this->redirectToRoute('copyUnreadInvoices');
    }

    /**
     * @Route(
     *     name="lastSignedInvoice",
     *     path="api/last-signed-invoice",
     *     methods={"GET"}
     * )
     */
    public function lastSignedInvoice(ManagerRegistry $doctrine, CsdService $csdService)
    {
        $invoice = $doctrine->getManager()->getRepository(Invoice::class)->getLastSignedInvoice();
        if (!$invoice) {
            return new Response('NOT FOUND', Response::HTTP_NOT_FOUND);
        }

        $csdResponse = $csdService->getCsdResponse($invoice, EcsdResponse::class);

        return new JsonResponse($csdResponse, Response::HTTP_OK);
    }

    /**
     * @Route(
     *     name="status",
     *     path="api/status",
     *     methods={"GET"}
     * )
     */
    public function status(SecurityCardService $securityCardService)
    {
        $cardErrors = $securityCardService->cardValidation();
        if (count($cardErrors) > 0) {
            return new JsonResponse($cardErrors['errors'][0], Response::HTTP_OK);
        }

        return new JsonResponse("0210", Response::HTTP_OK);
    }
}
