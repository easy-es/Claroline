<?php

namespace Claroline\CoreBundle\Library\Installation\Plugin;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResourceCheckerTest extends WebTestCase
{
    /** @var CommonChecker */
    private $checker;

    /** @var Loader */
    private $loader;

    protected function setUp()
    {
        $container = static::createClient()->getContainer();
        $this->checker = $container->get('claroline.plugin.resource_checker');
        $pluginDirectory = $container->getParameter('claroline.stub_plugin_directory');
        $this->loader = new Loader($pluginDirectory);
    }

    public function testCheckerReturnsAnErrorOnNonExistentResourceFile()
    {
        $pluginFqcn = 'Invalid\NonExistentResourceFile1\InvalidNonExistentResourceFile1';
        $errors = $this->checker->check($this->loader->load($pluginFqcn));
        $this->assertEquals(ResourceChecker::NON_EXISTENT_RESOURCE_FILE, $errors[0]->getCode());
    }

    public function testCheckerReturnsAnErrorOnInvalidResourceFileLocation()
    {
        $pluginFqcn = 'Invalid\UnexpectedResourceFileLocation1\InvalidUnexpectedResourceFileLocation1';
        $errors = $this->checker->check($this->loader->load($pluginFqcn));
        $this->assertEquals(ResourceChecker::INVALID_RESOURCE_FILE_LOCATION, $errors[0]->getCode());
    }

    public function testCheckerReturnsAnErrorOnNonYamlResourceFile()
    {
        $pluginFqcn = 'Invalid\NonYamlResourceFile1\InvalidNonYamlResourceFile1';
        $errors = $this->checker->check($this->loader->load($pluginFqcn));
        $this->assertEquals(ResourceChecker::INVALID_RESOURCE_FILE_EXTENSION, $errors[0]->getCode());
    }

    public function testCheckerReturnsAnErrorOnUnloadableYamlFile()
    {
        $pluginFqcn = 'Invalid\UnloadableResourceFile1\InvalidUnloadableResourceFile1';
        $errors = $this->checker->check($this->loader->load($pluginFqcn));
        $this->assertEquals(ResourceChecker::INVALID_YAML_RESOURCE_FILE, $errors[0]->getCode());
    }

    public function testCheckerReturnsAnErrorOnMissingResourceKey()
    {
        $pluginFqcn = 'Invalid\MissingResourceKey1\InvalidMissingResourceKey1';
        $errors = $this->checker->check($this->loader->load($pluginFqcn));
        $this->assertEquals(ResourceChecker::MISSING_RESOURCE_KEY, $errors[0]->getCode());
    }

    public function testCheckerReturnsAnErrorOnInvalidResourceValue()
    {
        $pluginFqcn = 'Invalid\UnexpectedResourceValue1\InvalidUnexpectedResourceValue1';
        $errors = $this->checker->check($this->loader->load($pluginFqcn));
        $this->assertEquals(ResourceChecker::INVALID_RESOURCE_VALUE, $errors[0]->getCode());
    }

    public function testCheckerReturnsAnErrorOnMissingResourceType()
    {
        $pluginFqcn = 'Invalid\MissingResourceType1\InvalidMissingResourceType1';
        $errors = $this->checker->check($this->loader->load($pluginFqcn));
        $this->assertEquals(ResourceChecker::MISSING_RESOURCE_TYPE, $errors[0]->getCode());
    }

    public function testCheckerReturnsAnErrorOnUnloadableResourceClass()
    {
        $pluginFqcn = 'Invalid\UnloadableResourceClass1\InvalidUnloadableResourceClass1';
        $errors = $this->checker->check($this->loader->load($pluginFqcn));
        $this->assertEquals(ResourceChecker::UNLOADABLE_RESOURCE_CLASS, $errors[0]->getCode());
    }

    public function testCheckerReturnsAnErrorOnInvalidResourceClass()
    {
        $pluginFqcn = 'Invalid\UnexpectedResourceClassType1\InvalidUnexpectedResourceClassType1';
        $errors = $this->checker->check($this->loader->load($pluginFqcn));
        $this->assertEquals(ResourceChecker::INVALID_RESOURCE_CLASS, $errors[0]->getCode());
    }

    public function testCheckerReturnsAnErrorOnInvalidParentResource()
    {
        $pluginFqcn = 'Invalid\UnloadableParentResource1\InvalidUnloadableParentResource1';
        $errors = $this->checker->check($this->loader->load($pluginFqcn));
        $this->assertEquals(ResourceChecker::UNLOADABLE_PARENT_RESOURCE, $errors[0]->getCode());
    }
}