<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\CsdService;

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

        return new Response(
            ($message ?? '') .
            '<h2>Pokreni kopiranje neisčitanih računa</h2>
            <form method="POST" action="/copy-unread-invoices-post">
                <input type="submit" value="Pojkreni kopiranje">
            </form>',
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
    public function copyUnreadInvoicesPost(CsdService $csdService, Request $request)
    {
    	$csdService->export();
    }
}
