<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service;

final class SinaWeiboOAuth2LoginController extends AbstractController
{
    public function __construct(
        private SinaWeiboOAuth2Service $oauth2Service,
    ) {
    }

    #[Route(path: '/sina-weibo-oauth2/login', name: 'sina_weibo_oauth2_login', methods: ['GET'])]
    public function __invoke(Request $request): RedirectResponse
    {
        try {
            $sessionId = $request->getSession()->getId();
            $authUrl = $this->oauth2Service->generateAuthorizationUrl($sessionId);

            return new RedirectResponse($authUrl);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
