<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ErrorController extends AbstractController
{

    #[Route("/error/{slug}", name: "error_not_found", requirements: ["slug" => ".+"], defaults: ["slug" => null])]
    public function index(Request $request): Response
    {
        return $this->render('error/error.html.twig', [
            'url' => $request->getRequestUri(),
        ]);
    }
}