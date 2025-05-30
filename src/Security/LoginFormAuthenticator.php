<?php

namespace App\Security;

use App\Controller\Admin\UserCrudController;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Service\Attribute\Required;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;
    private AdminUrlGenerator $adminUrlGenerator;

    #[Required]
    public function setAdminUrlGenerator(AdminUrlGenerator $adminUrlGenerator): void
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }
    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator, private UserRepository $userRepository)
    {
    }

    public function authenticate(Request $request): Passport
    {
         $email = $request->getPayload()->getString('email');

        $user = $this->userRepository->findOneByEmail($email);
        // Si l'utilisateur n'existe pas, lancer une exception personnalisée
        if (!$user) {
            throw new AuthenticationException('Cet email n\'est pas enregistré. Souhaitez-vous créer un compte ?', 401);
        }
        if ($user->getDeletedAt()) {
            throw new AuthenticationException('🔒 Votre compte est désactivé. Veuillez contacter l\'administrateur du site pour réactiver votre compte 📞 .', 401);
        }

        if (!$user || !$user->isVerified()) {
            throw new AuthenticationException('Veuillez vérifier votre boîte de réception pour trouver le lien de vérification.', 401);
        }


        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->getPayload()->getString('password')),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles)) {
            return new RedirectResponse($this->adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
        }  else  {
            return new RedirectResponse($this->urlGenerator->generate('app_home'));
        }
        return new RedirectResponse($this->adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
