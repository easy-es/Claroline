<?php

namespace Claroline\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Claroline\CoreBundle\Entity\Resource\File;

class FileControlller extends Controller
{
	/**
     * @Route(
     *     "resource/img/{imageId}",
     *     name="claro_file_get_image",
     *     options={"expose"=true}
     * )
     * @Method("GET")
     *
     * @param integer $id
     *
     * @return Response
     */
    public function getImg(File $file)
    {
        $imgpath = $this->container->getParameter('claroline.param.files_directory') . DIRECTORY_SEPARATOR
            . $file->getHashName();

        $response = new StreamedResponse();
        $response->setCallBack(
            function () use ($imgpath) {
                readfile($imgpath);
            }
        );

        $response->headers->set('Content-Type', $file->getMimeType());

        return $response;
    }
}
