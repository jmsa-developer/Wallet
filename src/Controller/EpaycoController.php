<?php

namespace App\Controller;

use App\Service\ClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EpaycoController extends AbstractController
{
    #[Route('/epayco', name: 'soap')]
    public function indexAction(ClientService $clientService) : Response
    {
        ini_set("soap.wsdl_cache_enabled", "0");

        $server = new \SoapServer('http://127.0.0.1:8000/wsdl/client.wsdl');
        $server->setObject($clientService);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=ISO-8859-1');

        ob_start();
        $server->handle();
        $response->setContent(ob_get_clean());

        return $response;
    }
}
