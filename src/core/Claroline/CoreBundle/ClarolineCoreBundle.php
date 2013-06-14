<?php

namespace Claroline\CoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Claroline\CoreBundle\DependencyInjection\Compiler\CustomCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

class ClarolineCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CustomCompilerPass(), PassConfig::TYPE_OPTIMIZE);
    }
}