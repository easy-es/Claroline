<?php

namespace Claroline\CoreBundle\Controller;

use Claroline\CoreBundle\Library\Testing\FunctionalTestCase;

class TextListenerTest extends FunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadPlatformRolesFixture();
        $this->loadUserData(array('user' => 'user'));
        $this->client->followRedirects();
    }

    public function testAdd()
    {
        $this->logUser($this->getUser('user'));
        $text = $this->addText('This is a text', 'hello world', $this->getDirectory('user')->getId());
        $this->assertEquals('This is a text', $text->name);
    }

    public function testDefaultAction()
    {
        $this->logUser($this->getUser('user'));
        $text = $this->addText('This is a text', 'hello world', $this->getDirectory('user')->getId());
        $crawler = $this->client->request('GET', "/resource/open/text/{$text->id}");
        $node = $crawler->filter('#text_content');
        $this->assertTrue(strpos($node->text(), 'hello world') !== false);
    }

    public function testCreationFormCanBeDisplayed()
    {
        $this->logUser($this->getUser('user'));
        $crawler = $this->client->request('GET', 'resource/form/text');
        $form = $crawler->filter('#text_form');
        $this->assertEquals(count($form), 1);
    }

    public function testFormErrorsAreDisplayed()
    {
        $this->logUser($this->getUser('user'));
        $crawler = $this->client->request(
            'POST', "/resource/create/text/{$this->getDirectory('user')->getId()}"
        );

        $form = $crawler->filter('#text_form');
        $this->assertEquals(count($form), 1);
    }

    private function addText($name, $text, $parentId)
    {
        $this->client->request(
            'POST',
            "/resource/create/text/{$parentId}",
            array('text_form' => array('name' => $name, 'text' => $text))
        );

        $obj = json_decode($this->client->getResponse()->getContent());

        return $obj[0];
    }
}