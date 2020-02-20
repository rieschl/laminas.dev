<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\Slack\SlackClient;
use Psr\Container\ContainerInterface;

class GitHubIssueListenerFactory
{
    public function __invoke(ContainerInterface $container): GitHubIssueListener
    {
        return new GitHubIssueListener(
            $container->get('config')['slack']['channels']['github'],
            $container->get(SlackClient::class)
        );
    }
}
