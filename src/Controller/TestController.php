<?php

declare(strict_types=1);


namespace App\Controller;

use App\Exception\MapConfigNotFoundException;
use App\Service\MapConfigBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\RuntimeError;

class TestController extends AbstractController
{
    #[Route('/test/{mapName}', name: 'app_test')]
    public function __invoke(MapConfigBuilder $configBuilder, LoggerInterface $logger, Request $request, string $mapName): Response
    {
        try {
            return $this->render('test.html.twig', [
                'mapName' => $mapName,
                'height' => $request->get('height', 500),
            ]);
        } catch (RuntimeError $exception) {
            if ($exception->getPrevious() instanceof MapConfigNotFoundException) {
                $logger->info(sprintf('Map config "%s" not found.', $mapName));
                throw $this->createNotFoundException();
            }
            throw $exception; // rethrow other runtime errors
        }
    }
}
