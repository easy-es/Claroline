<?php

namespace Claroline\CoreBundle\Form;

use Symfony\Component\Form\FormBuilder;
use Claroline\CoreBundle\Entity\ResourceType;
use Symfony\Component\Form\AbstractType;

class ChooseResourceType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
         $builder->add('type', 'entity', array('class' => 'ClarolineCoreBundle:ResourceType', 'expanded' => false, 'multiple' => false, 'property' => 'type', 'read_only' => false));
    }
    
    public function getName()
    {
        return 'choose_resource_form';
    }
}