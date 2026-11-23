<?php

declare(strict_types=1);

namespace Mcdennem\BoltTranslations\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class TranslationsController extends AbstractController
{
    public function index(): Response
    {
        return $this->render('@bolt-translations/index.html.twig');
    }
}
