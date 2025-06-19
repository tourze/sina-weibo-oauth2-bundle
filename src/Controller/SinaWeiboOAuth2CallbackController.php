<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service;

#[Route('/sina-weibo-oauth2/callback', name: 'sina_weibo_oauth2_callback', methods: ['GET'])]
class SinaWeiboOAuth2CallbackController extends AbstractController
{
    public function __construct(
        private SinaWeiboOAuth2Service $oauth2Service,
        private ?LoggerInterface $logger = null
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $code = $request->query->get('code');
        $state = $request->query->get('state');
        $error = $request->query->get('error');
        
        // Check for OAuth error response
        if ($error) {
            $errorDescription = $request->query->get('error_description', 'Unknown error');
            $this->logger?->warning('Sina Weibo OAuth2 error response', [
                'error' => $error,
                'error_description' => $errorDescription,
                'ip' => $request->getClientIp(),
            ]);
            return new Response(sprintf('OAuth2 Error: %s', $errorDescription), Response::HTTP_BAD_REQUEST);
        }
        
        // Validate required parameters
        if (!$code || !$state) {
            $this->logger?->warning('Invalid Sina Weibo OAuth2 callback parameters', [
                'has_code' => !empty($code),
                'has_state' => !empty($state),
                'ip' => $request->getClientIp(),
            ]);
            return new Response('Invalid callback parameters', Response::HTTP_BAD_REQUEST);
        }

        // Validate parameter format
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $code) || !preg_match('/^[a-fA-F0-9]{32}$/', $state)) {
            $this->logger?->warning('Malformed Sina Weibo OAuth2 callback parameters', [
                'ip' => $request->getClientIp(),
            ]);
            return new Response('Malformed callback parameters', Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->oauth2Service->handleCallback($code, $state);
            
            $this->logger?->info('Sina Weibo OAuth2 login successful', [
                'uid' => $user->getUid(),
                'nickname' => $user->getNickname(),
                'ip' => $request->getClientIp(),
            ]);
            
            // Here you can integrate with your application's user system
            // For example, create or update local user, set authentication, etc.
            
            return new Response(sprintf('Successfully logged in as %s', $user->getNickname() ?: $user->getUid()));
        } catch (\Exception $e) {
            $this->logger?->error('Sina Weibo OAuth2 login failed', [
                'error' => $e->getMessage(),
                'ip' => $request->getClientIp(),
                'code_prefix' => substr($code, 0, 8) . '...',
                'state' => $state,
            ]);
            return new Response('Login failed: Authentication error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}