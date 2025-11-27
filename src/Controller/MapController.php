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

class MapController extends AbstractController
{
    #[Route('/{mapName}', name: 'map')]
    public function __invoke(MapConfigBuilder $configBuilder, string $mapName, LoggerInterface $logger, Request $request): Response
    {
        try {
            return $this->render('map.html.twig', [
                'mapName' => $mapName,
                'height' => $request->query->getInt('height', 800),
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
