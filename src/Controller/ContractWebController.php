<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContractWebController extends AbstractController
{
    #[Route('/contracts', name: 'app_contracts_web', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('contract/index.html.twig');
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->redirectToRoute('app_contracts_web');
    }
}
