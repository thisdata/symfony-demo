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
use ThisData\Api\ThisData;
use ThisData\Api\Endpoint\EventsEndpoint;

class SecuritySubscriber implements EventSubscriberInterface
{

    private $entityManager;
    private $tokenStorage;
    private $authenticationUtils;
    private $thisData;

    public function __construct(EntityManager $entityManager, TokenStorageInterface $tokenStorage, AuthenticationUtils $authenticationUtils, $thisDataApiKey)
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->authenticationUtils = $authenticationUtils;
        $this->thisDataApiKey = $thisDataApiKey;
        $this->thisData = ThisData::create($thisDataApiKey);
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
            $userDetails = [
                'id' =>             $existingUser->getId(),
                'name' =>           $existingUser->getUsername(),
                'email' =>          $existingUser->getEmail(),
                'authenticated' =>  false
            ];
        } else {
            $userDetails = [
                'name' =>           $username,
                'authenticated' =>  false
            ];
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $endpoint = $this->thisData->getEventsEndpoint();
        $endpoint->trackEvent(EventsEndpoint::VERB_LOG_IN_DENIED, $ip, $userDetails, $userAgent);
    }

    public function onSecurityInteractiveLogin( InteractiveLoginEvent $event )
    {
        $user = $this->tokenStorage->getToken()->getUser();

        $userDetails = [
            'id' =>             $user->getId(),
            'name' =>           $user->getUsername(),
            'email' =>          $user->getEmail()
        ];
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $endpoint = $this->thisData->getEventsEndpoint();
        $endpoint->trackEvent(EventsEndpoint::VERB_LOG_IN, $ip, $userDetails, $userAgent);
    }
}