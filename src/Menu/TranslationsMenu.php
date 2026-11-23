<?php

declare(strict_types=1);

namespace Mcdennem\BoltTranslations\Menu;

use Bolt\Menu\ExtensionBackendMenuInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AutoconfigureTag('bolt.extension_backend_menu')]
class TranslationsMenu implements ExtensionBackendMenuInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function addItems(MenuItem $menu): void
    {
        $menu->addChild('Translations', [
            'uri' => $this->urlGenerator->generate('bolt_translations'),
            'extras' => [
                'name' => 'Translations',
                'icon' => 'fa-language',
            ],
        ]);
    }
}
