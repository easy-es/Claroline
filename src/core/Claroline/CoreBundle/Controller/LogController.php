<?php

namespace Claroline\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Claroline\CoreBundle\Library\Event\LogCreateDelegateViewEvent;
use Claroline\CoreBundle\Library\Event\LogResourceChildUpdateEvent;
use Claroline\CoreBundle\Form\LogWorkspaceWidgetConfigType;
use Claroline\CoreBundle\Form\LogDesktopWidgetConfigType;
use Claroline\CoreBundle\Entity\Logger\LogWorkspaceWidgetConfig;
use Claroline\CoreBundle\Entity\Logger\Log;
use Claroline\CoreBundle\Entity\Logger\LogDesktopWidgetConfig;
use Claroline\CoreBundle\Entity\Logger\LogHiddenWorkspaceWidgetConfig;
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;

/**
 * Controller of the user profile.
 */
class LogController extends Controller
{

    private function convertFormDataToConfig($config, $data, $workspace, $isDefault)
    {
        if ($config === null) {
            $config = new LogWorkspaceWidgetConfig();    
        }
        $config->setIsDefault($isDefault);
        
        $config->setResourceCopy($data['creation'] === true);
        $config->setResourceCreate($data['creation'] === true);
        $config->setResourceShortcut($data['creation'] === true);
        
        $config->setResourceRead($data['read'] === true);
        $config->setWsToolRead($data['read'] === true);

        $config->setResourceExport($data['export'] === true);

        $config->setResourceUpdate($data['update'] === true);
        $config->setResourceUpdateRename($data['update'] === true);

        $config->setResourceChildUpdate($data['updateChild'] === true);

        $config->setResourceDelete($data['delete'] === true);

        $config->setResourceMove($data['move'] === true);

        $config->setWsRoleSubscribeUser($data['subscribe'] === true);
        $config->setWsRoleSubscribeGroup($data['subscribe'] === true);
        $config->setWsRoleUnsubscribeUser($data['subscribe'] === true);
        $config->setWsRoleUnsubscribeGroup($data['subscribe'] === true);
        $config->setWsRoleChangeRight($data['subscribe'] === true);
        $config->setWsRoleCreate($data['subscribe'] === true);
        $config->setWsRoleDelete($data['subscribe'] === true);
        $config->setWsRoleUpdate($data['subscribe'] === true);

        $config->setAmount($data['amount']);

        $config->setWorkspace($workspace);

        return $config;
    }

    /**
     * @Route(
     *     "/view_details/{logId}",
     *     name="claro_log_view_details",
     *     options={"expose"=true}
     * )
     *
     * Displays the public profile of an user.
     *
     * @param integer $userId The id of the user we want to see the profile
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewDetailsAction(Log $log)
    {

        if ($log->getAction() === LogResourceChildUpdateEvent::ACTION ) {
            $eventName = 'create_log_details_'.$log->getResourceType()->getName();
            $event = new LogCreateDelegateViewEvent($log);
            $this->container->get('event_dispatcher')->dispatch($eventName, $event);

            if ($event->getResponseContent() === "") {
                throw new \Exception(
                    "Event '{$eventName}' didn't receive any response."
                );
            }

            return new Response($event->getResponseContent());
        }

        return $this->render(
            'ClarolineCoreBundle:Log:view_details.html.twig',
            array('log' => $log)
        );
    }

    /**
     * @Route(
     *     "/update_workspace_widget_config/{isDefault}/{id}/{redirectToHome}",
     *     name="claro_log_update_workspace_widget_config",
     *     defaults={"isDefault" = 0, "workspaceId" = 0, "redirectToHome" = 0}
     * )
     * @Method("POST")
     */
    public function updateLogWorkspaceWidgetConfig($isDefault, $workspace, $redirectToHome)
    {
        $isDefault = (boolean)$isDefault;
        $redirectToHome = (boolean)$redirectToHome;

        $em = $this->getDoctrine()->getManager();

        $config = $em->getRepository('ClarolineCoreBundle:Logger\LogWorkspaceWidgetConfig')
            ->findOneBy(array('workspace' => $workspace));


        if ($isDefault) {
            $workspace = null;
            $config = $this->get('claroline.log.manager')->getDefaultWorkspaceWidgetConfig();
        } else {
            $workspace = $em->getRepository('ClarolineCoreBundle:Workspace\AbstractWorkspace')->find($workspaceId);
            $config = $this->get('claroline.log.manager')->getWorkspaceWidgetConfig($workspace);
        }

        if ($config === null) {
            $config = new LogWorkspaceWidgetConfig();
            $config->setIsDefault($isDefault);
            $config->setWorkspace($workspace);
        }


        $form = $this->get('form.factory')->create(new LogWorkspaceWidgetConfigType(), null);

        $form->bind($this->getRequest());
        $translator = $this->get('translator');
        if ($form->isValid()) {
            $data = $form->getData();
            $config = $this->convertFormDataToConfig($config, $data, $workspace, $isDefault);

            $em->persist($config);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $translator->trans('Your changes have been saved', array(), 'platform'));
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $translator->trans('The form is not valid', array(), 'platform'));
        }
        $tool = $em->getRepository('ClarolineCoreBundle:Tool\Tool')->findOneByName('home');

        if ($isDefault === true) {
            $widget = $em->getRepository('ClarolineCoreBundle:Widget\Widget')
                ->findOneBy(array('name' => 'core_resource_logger'));

            return $this->redirect(
                $this->generateUrl(
                    'claro_admin_widget_configuration_workspace', array('widgetId' => $widget->getId())
                )
            );
        } else if ($redirectToHome === false) {

            return $this->render(
                'ClarolineCoreBundle:Log:config_workspace_form_update.html.twig', array(
                'form' => $form->createView(),
                'workspace' => $workspace,
                'tool' => $tool,
                'isDefault' => $config->getIsDefault() ? 1 : 0
                )
            );
        } else {

            return $this->redirect(
                $this->generateUrl(
                    'claro_workspace_open_tool', array('workspaceId' => $workspaceId, 'toolName' => 'home')
                )
            );
        }
    }

    /**
     * @Route(
     *     "/update_desktop_widget_config/{isDefault}/{redirectToHome}",
     *     name="claro_log_update_desktop_widget_config",
     *     defaults={"isDefault" = 0, "redirectToHome" = 0}
     * )
     * @Method("POST")
     */
    public function updateDesktopWidgetConfig($isDefault, $redirectToHome)
    {
        $isDefault = (bool)$isDefault;
        $redirectToHome = (bool)$redirectToHome;

        $em = $this->getDoctrine()->getManager();

        if ($isDefault) {
            $user = null;
            $hiddenConfigs = array();
            $workspaces = array();
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $hiddenConfigs = $em->getRepository('ClarolineCoreBundle:Logger\LogHiddenWorkspaceWidgetConfig')
                ->findBy(array('user' => $user));
            $workspaces = $em
                ->getRepository('ClarolineCoreBundle:Workspace\AbstractWorkspace')
                ->findByUserAndRoleNames($user, array('ROLE_WS_COLLABORATOR', 'ROLE_WS_MANAGER'));
        }
        if ($isDefault === true) {
            $config = $this->get('claroline.log.manager')->getDefaultDesktopWidgetConfig();
        } else {
            $config = $this->get('claroline.log.manager')->getDesktopWidgetConfig($user);
        }

        if ($config === null) {
            $config = new LogDesktopWidgetConfig();
            $config->setIsDefault($isDefault);
            $config->setUser($user);
        }

        $form = $this->get('form.factory')->create(new LogDesktopWidgetConfigType(), null, array('workspaces' => $workspaces));
        $form->bind($this->getRequest());

        $translator = $this->get('translator');
        if ($form->isValid()) {
            $data = $form->getData();
            // remove all hiddenConfigs for user
            foreach ($hiddenConfigs as $hiddenConfig) {
                $em->remove($hiddenConfig);
            }
            $em->flush();
            // add hiddenConfigs from formData for user
            foreach ($data as $workspaceId => $visible) {
                if ($workspaceId != 'amount' and $visible !== true) {
                    $hiddenConfig = new LogHiddenWorkspaceWidgetConfig();
                    $hiddenConfig->setUser($user);
                    $hiddenConfig->setWorkspaceId($workspaceId);
                    $em->persist($hiddenConfig);
                }
            }
            // save amount
            $config->setAmount($data['amount']);
            $em->persist($config);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', $translator->trans('Your changes have been saved', array(), 'platform'));
        } else {
            $this->get('session')->getFlashBag()->add('error', $translator->trans('The form is not valid', array(), 'platform'));
        }
        $tool = $em->getRepository('ClarolineCoreBundle:Tool\Tool')->findOneByName('home');

        if ($isDefault === true) {
            $widget = $em->getRepository('ClarolineCoreBundle:Widget\Widget')
                ->findOneBy(array('name' => 'core_resource_logger'));

            return $this->redirect(
                $this->generateUrl(
                    'claro_admin_widget_configuration_desktop', array('widgetId' => $widget->getId())
                )
            );
        } else if ($redirectToHome === false) {

            return $this->render(
                'ClarolineCoreBundle:Log:config_desktop_form_update.html.twig', array(
                    'form' => $form->createView(),
                    'tool' => $tool,
                    'isDefault' => $config->getIsDefault() ? 1 : 0
                )
            );
        } else {

            return $this->redirect($this->generateUrl('claro_desktop_open', array()));
        }
    }
}