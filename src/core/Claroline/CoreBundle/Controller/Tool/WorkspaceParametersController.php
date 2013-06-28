<?php

namespace Claroline\CoreBundle\Controller\Tool;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Claroline\CoreBundle\Controller\Tool\AbstractParametersController;
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;
use Claroline\CoreBundle\Entity\Tool\Tool;
use Claroline\CoreBundle\Library\Event\ConfigureWorkspaceToolEvent;
use Claroline\CoreBundle\Form\WorkspaceEditType;
use Claroline\CoreBundle\Form\WorkspaceTemplateType;

class WorkspaceParametersController extends AbstractParametersController
{
    /**
     * @Route(
     *     "/{id}/form/export",
     *     name="claro_workspace_export_form"
     * )
     * @Method("GET")
     *
     * @Template("ClarolineCoreBundle:Tool\workspace\parameters:template.html.twig")
     *
     * @param AbstractWorkspace $workspace
     *
     * @return Response
     */
    public function workspaceExportFormAction(AbstractWorkspace $workspace)
    {
        $this->checkAccess($workspace);
        $form = $this->get('form.factory')->create(new WorkspaceTemplateType());

        return array(
            'form' => $form->createView(),
            'workspace' => $workspace
        );
    }

    /**
     * @Route(
     *     "/{id}/export",
     *     name="claro_workspace_export"
     * )
     * @Method("POST")
     *
     * @Template("ClarolineCoreBundle:Tool\workspace\parameters:template.html.twig")
     *
     * @param AbstractWorkspace $workspace
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function workspaceExportAction(AbstractWorkspace $workspace)
    {
        $this->checkAccess($workspace);
        $request = $this->getRequest();
        $form = $this->createForm(new WorkspaceTemplateType());
        $form->handleRequest($request);

        if ($form->isValid()) {
            $name = $form->get('name')->getData();
            $this->get('claroline.workspace.exporter')->export($workspace, $name);
            $route = $this->get('router')->generate(
                'claro_workspace_open_tool',
                array('toolName' => 'parameters', 'workspaceId' => $workspace->getId())
            );

            return new RedirectResponse($route);
        }

        return array(
            'form' => $form->createView(),
            'workspace' => $workspace
        );
    }

    /**
     * @Route(
     *     "/{id}/editform",
     *     name="claro_workspace_edit_form"
     * )
     * @Method("GET")
     *
     * @Template("ClarolineCoreBundle:Tool\workspace\parameters:workspaceEdit.html.twig")
     *
     * @param AbstractWorkspace $workspace
     *
     * @return Response
     */
    public function workspaceEditFormAction(AbstractWorkspace $workspace)
    {
        $this->checkAccess($workspace);
        $form = $this->createForm(new WorkspaceEditType(), $workspace);

        return array(
            'form' => $form->createView(),
            'workspace' => $workspace
        );
    }


    /**
     * @Route(
     *     "/{id}/edit",
     *     name="claro_workspace_edit"
     * )
     * @Method("POST")
     *
     * @Template("ClarolineCoreBundle:Tool\workspace\parameters:workspaceEdit.html.twig")
     *
     * @param AbstractWorkspace $workspace
     *
     * @return Response
     */
    public function workspaceEditAction(AbstractWorkspace $workspace)
    {
        $em = $this->get('doctrine.orm.entity_manager');

        if (!$this->get('security.context')->isGranted('parameters', $workspace)) {
            throw new AccessDeniedException();
        }

        $wsRegisteredName = $workspace->getName();
        $wsRegisteredCode = $workspace->getCode();
        $form = $this->createForm(new WorkspaceEditType(), $workspace);
        $request = $this->getRequest();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em->persist($workspace);
            $em->flush();

            return $this->redirect(
                $this->generateUrl(
                    'claro_workspace_open_tool',
                    array(
                        'workspaceId' => $workspace->getId(),
                        'toolName' => 'parameters'
                    )
                )
            );
        } else {
            $workspace->setName($wsRegisteredName);
            $workspace->setCode($wsRegisteredCode);
        }

        return array(
            'form' => $form->createView(),
            'workspace' => $workspace
        );
    }

    /**
     * @Route(
     *     "/{id}/tool/{tool}/config",
     *     name="claro_workspace_tool_config"
     * )
     * @Method("GET")
     *
     * @param AbstractWorkspace $workspace
     * @param Tool $tool
     *
     * @return Response
     */
    public function openWorkspaceToolConfig(AbstractWorkspace $workspace, $toolId)
    {
        $this->checkAccess($workspace);
        $em = $this->get('doctrine.orm.entity_manager');
        $tool = $em->getRepository('ClarolineCoreBundle:Tool\Tool')->find($toolId);
        $event = new ConfigureWorkspaceToolEvent($tool, $workspace);
        $eventName = strtolower('configure_workspace_tool_' . $tool->getName());
        $this->get('event_dispatcher')->dispatch($eventName, $event);

        if (is_null($event->getContent())) {
            throw new \Exception(
                "Tool '{$tool->getName()}' didn't return any Response for tool event '{$eventName}'."
            );
        }

        return new Response($event->getContent());
    }
}
