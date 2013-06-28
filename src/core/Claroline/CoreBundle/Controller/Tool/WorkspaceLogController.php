<?php

namespace Claroline\CoreBundle\Controller\Tool;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response;
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;

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
     *     "/{id}",
     *     name="claro_workspace_logs_show",
     *     requirements={"id" = "\d+"},
     *     defaults={"page" = 1}
     * )
     * @Route(
     *     "/{id}/{page}",
     *     name="claro_workspace_logs_show_paginated",
     *     requirements={"id" = "\d+", "page" = "\d+"},
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
        $em = $this->container->get('doctrine.orm.entity_manager');
        $workspace = $em->getRepository('ClarolineCoreBundle:Workspace\AbstractWorkspace')->find($workspaceId);

        return $this->get('claroline.log.manager')->getWorkspaceList($workspace, $page);

    }
}
