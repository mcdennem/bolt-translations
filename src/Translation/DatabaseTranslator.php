<?php

declare(strict_types=1);

namespace Mcdennem\BoltTranslations\Translation;

use Mcdennem\BoltTranslations\Repository\TranslationRepository;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * Looks up translations in the database first and falls back to the regular translator (.xlf files).
 */
#[AsDecorator('translator')]
class DatabaseTranslator implements TranslatorInterface, TranslatorBagInterface, LocaleAwareInterface, WarmableInterface
{
    /** @var array<string, array<string, array<string, string>>> */
    private array $messages = [];

    private readonly MessageFormatter $formatter;

    public function __construct(
        #[AutowireDecorated]
        private readonly TranslatorInterface&TranslatorBagInterface&LocaleAwareInterface $inner,
        private readonly TranslationRepository $repository
    ) {
        $this->formatter = new MessageFormatter();
    }

    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $lookupDomain = $domain ?? 'messages';
        $lookupLocale = $locale ?? $this->inner->getLocale();

        $message = $this->getDatabaseMessages($lookupLocale)[$lookupDomain][$id] ?? null;

        if ($message === null) {
            return $this->inner->trans($id, $parameters, $domain, $locale);
        }

        return $this->formatter->format($message, $lookupLocale, $parameters);
    }

    public function getCatalogue(?string $locale = null): MessageCatalogueInterface
    {
        return $this->inner->getCatalogue($locale);
    }

    public function getCatalogues(): array
    {
        return $this->inner->getCatalogues();
    }

    public function getLocale(): string
    {
        return $this->inner->getLocale();
    }

    public function setLocale(string $locale): void
    {
        $this->inner->setLocale($locale);
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        if ($this->inner instanceof WarmableInterface) {
            return $this->inner->warmUp($cacheDir, $buildDir);
        }

        return [];
    }

    /**
     * Loaded once per locale per request. A database problem never breaks the site.
     *
     * @return array<string, array<string, string>>
     */
    private function getDatabaseMessages(string $locale): array
    {
        if (!isset($this->messages[$locale])) {
            try {
                $this->messages[$locale] = $this->repository->getMessages($locale);
            } catch (Throwable) {
                $this->messages[$locale] = [];
            }
        }

        return $this->messages[$locale];
    }
}
