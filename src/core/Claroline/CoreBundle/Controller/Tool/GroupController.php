<?php

namespace Claroline\CoreBundle\Controller\Tool;

use Doctrine\ORM\EntityRepository;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;
use Claroline\CoreBundle\Library\Event\LogWorkspaceRoleSubscribeEvent;
use Claroline\CoreBundle\Library\Event\LogWorkspaceRoleUnsubscribeEvent;

class GroupController extends Controller
{
    const ABSTRACT_WS_CLASS = 'ClarolineCoreBundle:Workspace\AbstractWorkspace';
    const NUMBER_GROUP_PER_ITERATION = 25;

    /**
     * @Route(
     *     "/{id}/groups/registered/page/{page}",
     *     name="claro_workspace_registered_group_list",
     *     defaults={"page"=1, "search"=""},
     *     options = {"expose"=true}
     * )
     *
     * @Method("GET")
     *
     * @Route(
     *     "/{id}/groups/registered/page/{page}/search/{search}",
     *     name="claro_workspace_registered_group_list_search",
     *     defaults={"page"=1},
     *     options = {"expose"=true}
     * )
     *
     * @Method("GET")
     *
     * @Template("ClarolineCoreBundle:Tool\workspace\group_management:registeredGroups.html.twig")
     */
    public function registeredGroupsListAction(AbstractWorkspace $workspace, $page, $search)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $this->checkRegistration($workspace);
        $repo = $em->getRepository('ClarolineCoreBundle:Group');
        $query = ($search == "") ?
            $repo->findByWorkspace($workspace, true):
            $repo->findByWorkspaceAndName($workspace, $search, true);
        $adapter = new DoctrineORMAdapter($query);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage(20);
        $pager->setCurrentPage($page);

        return array('workspace' => $workspace, 'pager' => $pager, 'search' => $search);
    }

    /**
     * @Route(
     *     "/{id}/groups/unregistered/page/{page}",
     *     name="claro_workspace_unregistered_group_list",
     *     defaults={"page"=1, "search"=""},
     *     options = {"expose"=true}
     * )
     *
     * @Method("GET")
     *
     * @Route(
     *     "/{id}/groups/unregistered/page/{page}/search/{search}",
     *     name="claro_workspace_unregistered_group_list_search",
     *     defaults={"page"=1},
     *     options = {"expose"=true}
     * )
     *
     * @Method("GET")
     *
     * @Template("ClarolineCoreBundle:Tool\workspace\group_management:unregisteredGroups.html.twig")
     */
    public function unregiseredGroupsListAction(AbstractWorkspace $workspace, $page, $search)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $this->checkRegistration($workspace, false);
        $repo = $em->getRepository('ClarolineCoreBundle:Group');
        $query = ($search == "") ?
            $repo->findWorkspaceOutsiders($workspace, true):
            $repo->findWorkspaceOutsidersByName($workspace, $search, true);
        $adapter = new DoctrineORMAdapter($query);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage(20);
        $pager->setCurrentPage($page);

        return array('workspace' => $workspace, 'pager' => $pager, 'search' => $search);
    }

    /**
     * @Route(
     *     "/{id}/tools/group/{group}",
     *     name="claro_workspace_tools_show_group_parameters",
     *     options={"expose"=true},
     *     requirements={"workspaceId"="^(?=.*[1-9].*$)\d*$", "groupId"="^(?=.*[1-9].*$)\d*$"}
     * )
     *
     * @Route(
     *     "/{id}/group/{groupId}",
     *     name="claro_workspace_tools_edit_group_parameters",
     *     options={"expose"=true},
     *     requirements={"workspaceId"="^(?=.*[1-9].*$)\d*$", "groupId"="^(?=.*[1-9].*$)\d*$" }
     * )
     * @Method({"POST", "GET"})
     *
     * @Template("ClarolineCoreBundle:Tool\workspace\group_management:groupParameters.html.twig")
     *
     * Renders the group parameter page with its layout and
     * edit the group parameters for the selected workspace.
     *
     * @param integer $workspaceId the workspace id
     * @param integer $groupId the group id
     *
     * @return Response
     */
    public function groupParametersAction(AbstractWorkspace $workspace,Group $group)
    {
        $this->checkRegistration($workspace, false);
        $roleRepo = $em->getRepository('ClarolineCoreBundle:Role');
        $role = $roleRepo->findWorkspaceRole($group, $workspace);
        $defaultData = array('role' => $role);
        $form = $this->createFormBuilder($defaultData, array('translation_domain' => 'platform'))
            ->add(
                'role',
                'entity',
                array(
                    'class' => 'Claroline\CoreBundle\Entity\Role',
                    'property' => 'translationKey',
                    'query_builder' => function (EntityRepository $er) use ($workspaceId) {
                        return $er->createQueryBuilder('wr')
                            ->select('role')
                            ->from('Claroline\CoreBundle\Entity\Role', 'role')
                            ->leftJoin('role.workspace', 'workspace')
                            ->where('workspace.id = :workspaceId')
                            ->andWhere("role.name != 'ROLE_ANONYMOUS'")
                            ->setParameter('workspaceId', $workspaceId);
                    }
                )
            )
            ->getForm();

        if ($this->getRequest()->getMethod() == 'POST') {
            $request = $this->getRequest();
            $parameters = $request->request->all();
            //cannot bind request: why ?
            $newRole = $em->getRepository('ClarolineCoreBundle:Role')
                ->find($parameters['form']['role']);

            //verifications: can we change his role.
            if ($newRole->getId() != $roleRepo->findManagerRole($workspace)->getId()) {
                $this->checkRemoveManagerRoleIsValid(array($group->getId()), $workspace);
            }

            $group->removeRole($role, false);
            $group->addRole($newRole);
            $em->persist($group);
            $em->flush();
            $route = $this->get('router')->generate(
                'claro_workspace_open_tool',
                array('workspaceId' => $workspaceId, 'toolName' => 'group_management')
            );

            $log = new LogWorkspaceRoleUnsubscribeEvent($role, null, $group);
            $this->get('event_dispatcher')->dispatch('log', $log);

            $log = new LogWorkspaceRoleSubscribeEvent($newRole, null, $group);
            $this->get('event_dispatcher')->dispatch('log', $log);
            $em->flush();

            return new RedirectResponse($route);
        }

        return array(
            'workspace' => $workspace,
            'group' => $group,
            'form' => $form->createView()
        );
    }

    /**
     * @Route(
     *     "/{id}/groups",
     *     name="claro_workspace_delete_groups",
     *     options={"expose"=true},
     *     requirements={"workspaceId"="^(?=.*[1-9].*$)\d*$"}
     * )
     * @Method("DELETE")
     *
     * Removes many groups from a workspace.
     * It uses a query string of groupIds as parameter (groupIds[]=1&groupIds[]=2)
     *
     * @param integer $workspaceId the workspace id
     *
     * @return Response
     */
    public function removeGroupsAction(AbstractWorkspace $workspace)
    {
        $this->checkRegistration($workspace, false);
        $roles = $em->getRepository('ClarolineCoreBundle:Role')
            ->findByWorkspace($workspace);
        $params = $this->get('request')->query->all();

        $groups = array();
        $rolesForGroups = array();
        if (isset($params['ids'])) {
            $this->checkRemoveManagerRoleIsValid($params['ids'], $workspace);
            foreach ($params['ids'] as $groupId) {
                $group = $em->find('ClarolineCoreBundle:Group', $groupId);

                if (null != $group) {
                    $rolesForGroup = array();
                    foreach ($roles as $role) {
                        if ($group->hasRole($role->getName())) {
                            $group->removeRole($role);
                            $rolesForGroup[] = $role;
                        }
                    }
                    $groups[] = $group;
                    $rolesForGroups['group_'.$group->getId()] = $rolesForGroup;
                }
            }
        }

        foreach ($groups as $group) {
            foreach ($rolesForGroups['group_'.$group->getId()] as $role) {
                $log = new LogWorkspaceRoleUnsubscribeEvent($role, null, $group);
                $this->get('event_dispatcher')->dispatch('log', $log);
            }
        }

        $em->flush();

        return new Response("success", 204);
    }

    /**
     * @Route(
     *     "/{id}/add/group",
     *     name="claro_workspace_multiadd_group",
     *     options={"expose"=true},
     *     requirements={"id"="^(?=.*[1-9].*$)\d*$"}
     * )
     * @Method("PUT")
     *
     * Adds many groups to a workspace.
     * It uses a query string of groupIds as parameter (groupIds[]=1&groupIds[]=2)
     *
     * @param integer $workspaceId the workspace id
     *
     * @return Response
     */
    public function addGroupsAction(AbstractWorkspace $workspace)
    {
        $params = $this->get('request')->query->all();
        $em = $this->get('doctrine.orm.entity_manager');
        $this->checkRegistration($workspace, false);
        $role = $em->getRepository('ClarolineCoreBundle:Role')
                        ->findCollaboratorRole($workspace);
        $groups = array();

        if (isset($params['ids'])) {
            foreach ($params['ids'] as $groupId) {
                $group = $em->find('ClarolineCoreBundle:Group', $groupId);
                $groups[] = $group;
                $group->addRole($role);
                $em->flush();
            }
        }

        foreach ($groups as $group) {
            $log = new LogWorkspaceRoleSubscribeEvent($role, null, $group);
            $this->get('event_dispatcher')->dispatch('log', $log);
        }

        $response = new Response($this->get('claroline.resource.converter')->jsonEncodeGroups($groups));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Checks if the role manager of the group can be changed.
     * There should be awlays at least one manager of a workspace.
     *
     * @param array $groupIds an array of group ids.
     * @param AbstractWorkspace $workspace the relevant workspace
     *
     * @throws LogicException
     */
    private function checkRemoveManagerRoleIsValid($groupIds, $workspace)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $managerRole = $em->getRepository('ClarolineCoreBundle:Role')
            ->findManagerRole($workspace);
        $countRemovedManagers = 0;

        foreach ($groupIds as $groupId) {
            $group = $em->find('ClarolineCoreBundle:Group', $groupId);

            if (null !== $group) {
                if ($group->hasRole($managerRole->getName())) {
                    $countRemovedManagers += count($group->getUsers());
                }
            }
        }

        $userManagers = $em->getRepository('ClarolineCoreBundle:User')
            ->findByWorkspaceAndRole($workspace, $managerRole);
        $countUserManagers = count($userManagers);

        if ($countRemovedManagers >= $countUserManagers) {
            throw new LogicException(
                "You can't remove every managers(you're trying to remove {$countRemovedManagers} "
                . "manager(s) out of {$countUserManagers})"
            );
        }
    }

    /**
     * Checks if the current user has access to the group management tool.
     *
     * @param AbstractWorkspace $workspace
     * @param boolean           $allowAnonymous
     *
     * @throws AccessDeniedException
     */
    private function checkRegistration(AbstractWorkspace $workspace, $allowAnonymous = true)
    {
        $security = $this->get('security.context');

        if (($security->getToken()->getUser() === 'anon.' && !$allowAnonymous)
            || !$security->isGranted('group_management', $workspace)) {
            throw new AccessDeniedException();
        }
    }

    /**
     * Most dql request required by this controller are paginated.
     * This function transform the results of the repository in an array.
     *
     * @param Paginator $paginator the return value of the Repository using a paginator.
     *
     * @return array.
     */
    private function paginatorToArray($paginator)
    {
        return $this->get('claroline.utilities.paginator_parser')
            ->paginatorToArray($paginator);
    }
}
