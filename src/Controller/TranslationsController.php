<?php

declare(strict_types=1);

namespace Mcdennem\BoltTranslations\Controller;

use Doctrine\DBAL\Exception;
use Mcdennem\BoltTranslations\Repository\TranslationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TranslationsController extends AbstractController
{
    private const string CSRF_ID = 'bolt_translations';

    public function __construct(
        private readonly TranslationRepository $repository
    ) {
    }

    /**
     * @throws Exception
     */
    public function index(Request $request): Response
    {
        $this->repository->ensureTable();

        $search = trim((string) $request->query->get('q', ''));

        return $this->render('@bolt-translations/index.html.twig', [
            'locales' => $this->repository->getLocales(),
            'translations' => $this->repository->getOverview($search),
            'search' => $search,
            'csrf_id' => self::CSRF_ID,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid(self::CSRF_ID, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid token, please try again.');

            return $this->backToOverview($request);
        }

        $domain = trim((string) $request->request->get('domain', 'messages')) ?: 'messages';
        $key = trim((string) $request->request->get('key', ''));
        $messages = $request->request->all('messages');

        if ($key === '') {
            $this->addFlash('danger', 'Key is required.');

            return $this->backToOverview($request);
        }

        foreach ($messages as $locale => $message) {
            $this->repository->save($domain, $key, (string) $locale, (string) $message);
        }

        $this->addFlash('success', sprintf('Translation "%s" saved.', $key));

        return $this->backToOverview($request);
    }

    public function delete(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid(self::CSRF_ID, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid token, please try again.');

            return $this->backToOverview($request);
        }

        $domain = (string) $request->request->get('domain', 'messages');
        $key = (string) $request->request->get('key', '');

        $this->repository->delete($domain, $key);

        $this->addFlash('success', sprintf('Translation "%s" deleted.', $key));

        return $this->backToOverview($request);
    }

    private function backToOverview(Request $request): RedirectResponse
    {
        $search = (string) $request->request->get('q', '');

        return $this->redirectToRoute('bolt_translations', $search !== '' ? ['q' => $search] : []);
    }
}
