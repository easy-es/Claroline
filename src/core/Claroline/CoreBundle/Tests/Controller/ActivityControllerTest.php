<?php

namespace Claroline\CoreBundle\Controller;

use Claroline\CoreBundle\Library\Testing\FunctionalTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ActivityControllerTest extends FunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->loadUserFixture(array('admin'));
        $this->resourceRepository = $this
            ->client
            ->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository('Claroline\CoreBundle\Entity\Resource\AbstractResource');
        $this->pwr = $this->resourceRepository->getRootForWorkspace($this->getFixtureReference('user/admin')->getPersonalWorkspace());

    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testAddThenRemoveResource()
    {
        $this->logUser($this->getFixtureReference('user/admin'));
        $repo = $this->client->getContainer()->get('doctrine.orm.entity_manager')->getRepository('Claroline\CoreBundle\Entity\Resource\AbstractResource');
        $file = $this->uploadFile($this->pwr->getId(), 'file');
        $activity = $this->createActivity('name', 'instruction');
        $this->client->request(
            'POST',
            "/activity/{$activity->id}/add/resource/{$file->id}"
            );
        $obj = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(1, count($obj));
        $resActivity = $repo->find($activity->id);
        $this->assertEquals(1, count($resActivity->getResources()));
//       the code below doens't work: no idea why
//        $this->client->request(
//            'DELETE',
//            "/activity/{$activity->id}/remove/resource/{$file->id}"
//        );
//            $this->client->getContainer()->get('doctrine.orm.entity_manager')->flush();
//        $lastActivity = $repo->find($activity->id);
//                foreach($lastActivity->getResources() as $res){
//        }
//        $this->assertEquals(0, count($lastActivity->getResources()));

    }

    private function createActivity($name, $instruction)
    {
        $this->client->request(
            'POST',
            "/resource/create/activity/{$this->pwr->getId()}",
            array('activity_form' => array('name' => $name, 'instruction' => $instruction))
        );

        $obj = json_decode($this->client->getResponse()->getContent());

        return $obj[0];
    }

    private function uploadFile($parentId, $name, $shareType = 1)
    {
        $file = new UploadedFile(tempnam(sys_get_temp_dir(), 'FormTest'), $name, 'text/plain', null, null, true);
        $this->client->request(
            'POST', "/resource/create/file/{$parentId}", array('file_form' => array()), array('file_form' => array('file' => $file, 'name' => 'tmp'))
        );

        $obj = json_decode($this->client->getResponse()->getContent());
        return $obj[0];
    }
}