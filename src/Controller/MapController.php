<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\MapConfigNotFoundException;
use App\Service\MapConfigBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\WebLink\Link;
use Twig\Error\RuntimeError;

class MapController extends AbstractController
{
    #[Route('/{mapName}', name: 'map')]
    public function __invoke(
        MapConfigBuilder $configBuilder,
        string $mapName,
        LoggerInterface $logger,
        Request $request,
        Packages $assetPackages,
    ): Response {
        // Send Early Hints (HTTP 103) to preload critical Leaflet assets
        $this->sendEarlyHints([
            (new Link('preload', $assetPackages->getUrl('vendor/leaflet/dist/leaflet.min.css')))->withAttribute('as', 'style'),
            (new Link('preload', $assetPackages->getUrl('vendor/leaflet/dist/images/marker-icon.png')))->withAttribute('as', 'image'),
        ]);

        try {
            return $this->render('map.html.twig', [
                'mapName' => $mapName,
                'height' => $request->query->has('height') ? $request->query->getInt('height') : null,
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
