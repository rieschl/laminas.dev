<?php

declare(strict_types=1);

namespace App\GitHub\Event;

use Assert\Assert;
use DateTimeImmutable;

use function in_array;
use function sprintf;

/**
 * @see https://developer.github.com/v3/activity/events/types/#issuesevent
 */
class GitHubIssue implements GitHubMessageInterface
{
    /** @var array */
    private $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function validate(): void
    {
        Assert::that($this->payload['action'])->notEmpty()->string();
        Assert::that($this->payload['issue'])->isArray();
        Assert::that($this->payload['issue'])->keyIsset('html_url');
        Assert::that($this->payload['issue'])->keyIsset('number');
        Assert::that($this->payload['issue'])->keyIsset('title');
        Assert::that($this->payload['issue'])->keyIsset('body');
        Assert::that($this->payload['repository'])->isArray();
        Assert::that($this->payload['repository'])->keyIsset('full_name');
        Assert::that($this->payload['repository'])->keyIsset('html_url');
        Assert::that($this->payload['sender'])->isArray();
        Assert::that($this->payload['sender'])->keyIsset('login');
        Assert::that($this->payload['sender'])->keyIsset('html_url');
    }

    public function ignore(): bool
    {
        return ! in_array($this->payload['action'], [
            'opened',
            'closed',
            'reopened',
        ], true);
    }

    public function getMessagePayload():  array
    {
        $payload = $this->payload;
        $issue   = $payload['issue'];
        $author  = $payload['sender'];
        $repo    = $payload['repository'];

        switch ($payload['action'])
        {
            case 'closed':
                $ts = (new DateTimeImmutable($issue['closed_at']))->getTimestamp();
                break;
            case 'reopened':
                $ts = (new DateTimeImmutable($issue['updated_at']))->getTimestamp();
                break;
            case 'opened':
            default:
                $ts = (new DateTimeImmutable($issue['created_at']))->getTimestamp();
                break;
        }

        return [
            'fallback' => sprintf(
                '[%s] Issue #%s %s by %s: %s',
                $repo['full_name'],
                $issue['number'],
                $payload['action'],
                $author['login'],
                $issue['html_url']
            ),
            'color'   => 'warning',
            'pretext' => sprintf(
                '[<%s|#%s>] Issue %s by <%s|%s>',
                $repo['html_url'],
                $repo['full_name'],
                $payload['action'],
                $author['html_url'],
                $author['login']
            ),
            'author_name' => sprintf('%s (GitHub)', $repo['full_name']),
            'author_link' => $repo['html_url'],
            'author_icon' => self::GITHUB_ICON,
            'title'       => sprintf('#%s %s', $issue['number'], $issue['title']),
            'title_link'  => $issue['html_url'],
            'text'        => $issue['body'],
            'fields'      => [
                [
                    'title' => 'Repository',
                    'value' => sprintf('<%s>', $repo['html_url']),
                    'short' => true,
                ],
                [
                    'title' => 'Reporter',
                    'value' => sprintf('<%s|%s>', $author['html_url'], $author['login']),
                    'short' => true,
                ],
                [
                    'title' => 'Status',
                    'value' => $payload['action'],
                    'short' => true,
                ],
            ],
            'footer'      => 'GitHub',
            'footer_icon' => self::GITHUB_ICON,
            'ts'          => $ts,
        ];
    }
}
