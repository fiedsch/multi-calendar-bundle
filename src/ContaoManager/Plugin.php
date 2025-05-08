<?php

declare(strict_types=1);

namespace Fiedsch\MultiCalendarBundle\ContaoManager;

use Fiedsch\MultiCalendarBundle\FiedschMultiCalendarBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            (new BundleConfig(FiedschMultiCalendarBundle::class))
                ->setLoadAfter([ContaoCoreBundle::class, ContaoCalendarBundle::class]),
        ];
    }
}