<?php
declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

return static function (ContainerConfigurator $configurator, ContainerBuilder $builder): void {
    $services = $configurator->services();
    $services->defaults()->autowire()->autoconfigure()->private();

    $services->load('TRAW\HreflangPages\\', '../Classes');
};
