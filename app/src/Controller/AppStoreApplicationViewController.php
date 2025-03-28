<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppStoreApplicationViewController extends AbstractController
{
    /** The index path is defined in the app configuration in the devshop or admin panel. */
    #[Route('/app-store/view/hello-world', methods: ['GET'])]
    public function iframeViewHelloWorldAction(): Response
    {
        /**
         * There you can host whatever you want - a simple HTML page, a React app, etc.
         */
        return $this->render('hello-world.html.twig');
    }
}