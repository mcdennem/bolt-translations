<?php

declare(strict_types=1);

namespace Mcdennem\BoltTranslations;

use Bolt\Extension\BaseExtension;
use Mcdennem\BoltTranslations\Controller\TranslationsController;
use Symfony\Component\Routing\Route;

class Extension extends BaseExtension
{
    public function getName(): string
    {
        return 'Translations';
    }

    public function initialize(): void
    {
        $this->addTwigNamespace('bolt-translations');
    }

    public function getRoutes(): array
    {
        return [
            'bolt_translations' => new Route(
                '%bolt.backend_url%/translations',
                ['_controller' => TranslationsController::class . '::index'],
                [],
                [],
                '',
                [],
                ['GET']
            ),
            'bolt_translations_save' => new Route(
                '%bolt.backend_url%/translations/save',
                ['_controller' => TranslationsController::class . '::save'],
                [],
                [],
                '',
                [],
                ['POST']
            ),
            'bolt_translations_delete' => new Route(
                '%bolt.backend_url%/translations/delete',
                ['_controller' => TranslationsController::class . '::delete'],
                [],
                [],
                '',
                [],
                ['POST']
            ),
        ];
    }
}
