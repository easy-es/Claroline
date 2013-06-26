<?php

namespace Claroline\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Claroline\CoreBundle\Form\MailType;
use Claroline\CoreBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class MailController extends Controller
{
    /**
     * @Route(
     *     "/form/{id}",
     *     name="claro_mail_form"
     * )
     *
     * @Template()
     *
     * Displays the mail form.
     *
     * @param integer $userId
     *
     * @return Response
     */
    public function formAction(User $user)
    {
        $form = $this->createForm(new MailType());

        return array(
            'form' => $form->createView(),
            'userId' => $userId
        );
    }

    /**
     * @Route(
     *     "/send/{userId}",
     *     name="claro_mail_send"
     * )
     *
     * @Template()
     *
     * Handles the mail form submission (sends a mail).
     *
     * @param integer $userId
     *
     * @return Response
     */
    public function sendAction(User $user)
    {
        $request = $this->get('request');
        $form = $this->get('form.factory')->create(new MailType());
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $user = $this->get('doctrine.orm.entity_manager')
                ->getRepository('ClarolineCoreBundle:User')
                ->find($user);
            $message = \Swift_Message::newInstance()
                ->setSubject($data['object'])
                ->setFrom('noreply@claroline.net')
                ->setTo($user->getMail())
                ->setBody($data['content'], 'text/html');
            $this->get('mailer')->send($message);
        }

        // add success/error message...

        return array(
            'form' => $form->createView(),
            'userId' => $userId
        );
    }
}
