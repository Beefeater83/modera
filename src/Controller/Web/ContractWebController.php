<?php

declare(strict_types=1);

namespace App\Controller\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContractWebController extends AbstractController
{
    #[Route('/contracts', name: 'app_contracts_web', methods: ['GET'])]
    public function index(): Response
    {
        /*
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        */
        return $this->render('contract/index.html.twig');
    }
}
