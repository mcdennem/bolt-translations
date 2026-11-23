<?php

declare(strict_types=1);

namespace Mcdennem\BoltTranslations\Controller;

use Bolt\Extension\ExtensionRegistry;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Exception\TypesException;
use Mcdennem\BoltTranslations\Extension;
use Mcdennem\BoltTranslations\Import\XliffImporter;
use Mcdennem\BoltTranslations\Repository\TranslationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TranslationsController extends AbstractController
{
    private const string CSRF_ID = 'bolt_translations';

    public function __construct(
        private readonly TranslationRepository $repository,
        private readonly XliffImporter $importer,
        private readonly ExtensionRegistry $extensionRegistry
    ) {
    }

    /**
     * @throws Exception
     * @throws TypesException
     */
    public function index(Request $request): Response
    {
        $this->repository->ensureTable();

        $search = trim((string) $request->query->get('q', ''));

        return $this->render('@bolt-translations/index.html.twig', [
            'locales' => $this->getLocales(),
            'translations' => $this->filterByPrefix($this->repository->getOverview($search)),
            'search' => $search,
            'csrf_id' => self::CSRF_ID,
        ]);
    }

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
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

    /**
     * Only admins may import, because an import overwrites existing translations.
     *
     * @throws Exception
     * @throws TypesException
     */
    public function import(Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid(self::CSRF_ID, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid token, please try again.');

            return $this->backToOverview($request);
        }

        $result = $this->importer->import();

        if ($result === []) {
            $this->addFlash('warning', 'No .xlf files found in translations/.');

            return $this->backToOverview($request);
        }

        $this->addFlash('success', sprintf(
            '%d translations imported from %d files.',
            array_sum($result),
            count($result)
        ));

        return $this->backToOverview($request);
    }

    /**
     * Locales from the extension config first, then any extra locales found in the database.
     *
     * @return string[]
     * @throws Exception
     */
    private function getLocales(): array
    {
        return array_values(array_unique(array_merge(
            $this->getConfigValue('locales'),
            $this->repository->getLocales()
        )));
    }

    /**
     * Keeps only keys that start with one of the configured prefixes. No prefixes means all keys.
     *
     * @param array<int, array{domain: string, key: string, messages: array<string, ?string>}> $translations
     * @return array<int, array{domain: string, key: string, messages: array<string, ?string>}>
     */
    private function filterByPrefix(array $translations): array
    {
        $prefixes = array_filter($this->getConfigValue('prefixes'));

        if ($prefixes === []) {
            return $translations;
        }

        return array_values(array_filter(
            $translations,
            fn (array $item): bool => array_any($prefixes, fn ($prefix): bool => str_starts_with($item['key'], (string) $prefix))
        ));
    }

    /**
     * A list setting from the extension config, or an empty array when it is not set.
     */
    private function getConfigValue(string $name): array
    {
        $extension = $this->extensionRegistry->getExtension(Extension::class);

        return (array) $extension?->getConfig()->get($name, []);
    }

    private function backToOverview(Request $request): RedirectResponse
    {
        $search = (string) $request->request->get('q', '');

        return $this->redirectToRoute('bolt_translations', $search !== '' ? ['q' => $search] : []);
    }
}
