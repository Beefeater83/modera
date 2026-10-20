<?php

declare(strict_types=1);

namespace App\Controller\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class ContractWebController extends AbstractController
{
    #[Route('/contracts', name: 'app_contracts_web', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if (!$request->cookies->has('refresh_token')) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('contract/index.html.twig');
    }
}
