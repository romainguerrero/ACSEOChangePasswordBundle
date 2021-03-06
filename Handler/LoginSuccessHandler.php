<?php

namespace ACSEO\ChangePasswordBundle\Handler;

use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;
use Doctrine\ORM\EntityManager;

class LoginSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    protected $router;
    protected $security;
    protected $passwordExpireAfter;
    protected $changePasswordRoute;
    protected $enableFlashbagMessage;

    public function __construct(HttpUtils $httpUtils, EntityManager $em, Router $router, SecurityContext $security, $passwordExpireAfter, $changePasswordRoute, $enableFlashbagMessage)
    {
        $this->httpUtils = $httpUtils;
        $this->em        = $em;
        $this->router    = $router;
        $this->security  = $security;
        $this->passwordExpireAfter = $passwordExpireAfter;
        $this->changePasswordRoute = $changePasswordRoute;
        $this->enableFlashbagMessage = $enableFlashbagMessage;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $lastUserPassword = $this->em->getRepository("ACSEOChangePasswordBundle:PasswordHistory")
            ->findOneBy(array("user" => $token->getUser()), array("createdAt" => "DESC"), 1);

        $lastPasswordDate = $lastUserPassword->getCreatedAt();

        if ($lastPasswordDate->add(new \DateInterval($this->passwordExpireAfter)) > new \Datetime()) {
            $session = $request->getSession();
            $session->set("mustchangepassword", true);
            if ($this->enableFlashbagMessage) {
                $session->getFlashBag()->add("danger", "Votre mot de passe a expiré, vous devez en saisir un nouveau");
            }
            $response = new RedirectResponse($this->router->generate($this->changePasswordRoute));
        }

        return parent::onAuthenticationSuccess($request, $token);
    }
}
