<?php

namespace Claroline\CoreBundle\Controller\Tool;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
<<<<<<< HEAD
use Symfony\Component\HttpFoundation\Response;
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;
||||||| merged common ancestors
use Symfony\Component\HttpFoundation\Response;
=======
>>>>>>> c781053ab242327ed177c0ddf71709c274c414c9
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Display logs in workspace's tool.
 */
class WorkspaceLogController extends Controller
{
    /**
     * @Route(
     *     "/{workspaceId}",
     *     name="claro_workspace_logs_show",
     *     requirements={"workspaceId" = "\d+"},
     *     defaults={"page" = 1}
     * )
     * @Route(
     *     "/{workspaceId}/{page}",
     *     name="claro_workspace_logs_show_paginated",
     *     requirements={"workspaceId" = "\d+", "page" = "\d+"},
     *     defaults={"page" = 1}
     * )
     *
     * @Method("GET")
     *
     * @Template("ClarolineCoreBundle:Tool/workspace/logs:logList.html.twig")
     *
     * Displays logs list using filter parameteres and page number
     *
     * @param $page int The requested page number.
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function logListAction(AbstractWorkspace $workspace, $page)
    {
<<<<<<< HEAD
        return $this->render(
            'ClarolineCoreBundle:Tool/workspace/logs:log_list.html.twig',
            $this->get('claroline.log.manager')->getWorkspaceList($workspace, $page)
        );
||||||| merged common ancestors
        $em = $this->container->get('doctrine.orm.entity_manager');
        $workspace = $em->getRepository('ClarolineCoreBundle:Workspace\AbstractWorkspace')->find($workspaceId);

        return $this->render(
            'ClarolineCoreBundle:Tool/workspace/logs:log_list.html.twig',
            $this->get('claroline.log.manager')->getWorkspaceList($workspace, $page)
        );
=======
        $em = $this->container->get('doctrine.orm.entity_manager');
        $workspace = $em->getRepository('ClarolineCoreBundle:Workspace\AbstractWorkspace')->find($workspaceId);

        return $this->get('claroline.log.manager')->getWorkspaceList($workspace, $page);
>>>>>>> c781053ab242327ed177c0ddf71709c274c414c9
    }
}
