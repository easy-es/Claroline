<?php

namespace Claroline\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CustomCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->setParameter('doctrine.orm.entity_manager', 'troll');
    }
}