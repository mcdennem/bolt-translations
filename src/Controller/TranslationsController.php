<?php

declare(strict_types=1);

namespace Mcdennem\BoltTranslations\Controller;

use Mcdennem\BoltTranslations\Repository\TranslationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TranslationsController extends AbstractController
{
    public function __construct(
        private readonly TranslationRepository $repository
    ) {
    }

    public function index(Request $request): Response
    {
        $this->repository->ensureTable();

        $search = trim((string) $request->query->get('q', ''));

        return $this->render('@bolt-translations/index.html.twig', [
            'locales' => $this->repository->getLocales(),
            'translations' => $this->repository->getOverview($search),
            'search' => $search,
        ]);
    }
}
