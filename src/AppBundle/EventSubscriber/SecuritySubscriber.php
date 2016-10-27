<?php

/*
 * Listens to security related events like log-ins, failed logins, etc,
 * and sends them to ThisData.
 *
 */

namespace AppBundle\EventSubscriber;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class SecuritySubscriber implements EventSubscriberInterface
{

    private $entityManager;
    private $tokenStorage;
    private $authenticationUtils;

    public function __construct(EntityManager $entityManager, TokenStorageInterface $tokenStorage, AuthenticationUtils $authenticationUtils)
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->authenticationUtils = $authenticationUtils;
    }

    public static function getSubscribedEvents()
    {
        return array(
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
        );
    }

    public function onAuthenticationFailure( AuthenticationFailureEvent $event )
    {
        $username = $this->authenticationUtils->getLastUsername();
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUser) {
           error_log("Log In Denied: Wrong password for User #" . $existingUser->getId()  . " (" . $existingUser->getEmail() . ")");
        } else {
           error_log("Log In Denied: User doesn't exist: " . $username);
        }
    }

    public function onSecurityInteractiveLogin( InteractiveLoginEvent $event )
    {
        $user = $this->tokenStorage->getToken()->getUser();
        error_log("Log In: User #" . $user->getId()  . " (" . $user->getEmail() . ")");
    }
}