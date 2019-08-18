<?php

declare(strict_types=1);

namespace TheIpster\IntegrationBranchBuilder\BitbucketServer;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use TheIpster\IntegrationBranchBuilder\Entities\Branch;

class FindPullRequestSourcesService
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * Constructor
     *
     * @param ClientInterface $client
     * @param RequestFactoryInterface $requestFactory
     */
    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
    }

    /**
     * @param string $apiDomain Bitbucket Server API domain name
     * @param string $projectKey Bitbucket project key
     * @param string $repositorySlug Bitbucket repository slug
     * @param string $targetBranch Pull request target branch name (e.g. "feature/ABC-123-target")
     * @param string $authHeaderValue Bitbucket Server HTTP Auth header value
     *
     * @return RequestInterface
     */
    private function createHttpRequest(
        string $apiDomain,
        string $projectKey,
        string $repositorySlug,
        string $targetBranch,
        string $authHeaderValue
    ): RequestInterface {

        // Build URI
        $pullRequestsUri = sprintf(
            'https://%s/rest/api/1.0/projects/%s/repos/%s/pull-requests?state=OPEN&order=OLDEST&at=refs/heads/%s',
            $apiDomain,
            $projectKey,
            $repositorySlug,
            $targetBranch
        );

        // Create request
        return $this->requestFactory->createRequest('GET', $pullRequestsUri)
            ->withHeader('Authorization', $authHeaderValue);
    }

    /**
     * @param string $repositoryUrl Bitbucket repository URL.
     * @param string $targetBranch Pull request target branch name (e.g. "feature/ABC-123-target")
     * @param string $authHeaderValue Bitbucket Server HTTP Auth header value
     *
     * @return Branch[]
     */
    public function getBranchesForPullRequestTarget(
        string $repositoryUrl,
        string $targetBranch,
        string $authHeaderValue
    ): array {

        // Parse repo URL
        $urlComponents = parse_url($repositoryUrl);
        if ($urlComponents === false
            || empty($urlComponents['host'])
            || empty($urlComponents['path'])
        ) {
            $errorMsg = sprintf('Could not parse repository URL: %s.', $repositoryUrl);
            throw new Exception($errorMsg);
        }
        $pathMatched = preg_match(
            '#^/(?P<projectKey>[a-z]+)/(?P<repoSlug>[a-z-]+)\.git$#',
            $urlComponents['path'],
            $pathMatches
        );
        if (!$pathMatched) {
            $errorMsg = sprintf('Could not parse repository URL (path): %s.', $repositoryUrl);
            throw new Exception($errorMsg);
        }

        // Create HTTP request
        $request = $this->createHttpRequest(
            $urlComponents['host'],
            $pathMatches['projectKey'],
            $pathMatches['repoSlug'],
            $targetBranch,
            $authHeaderValue
        );

        // Get HTTP response
        $response = $this->client->sendRequest($request);
        $responseCode = $response->getStatusCode();
        if ($responseCode !== 200) {
            $errorMsg = sprintf(
                'Bitbucket pull requests API returned non-success response: %u',
                $responseCode
            );
            throw new Exception($errorMsg);
        }

        // Parse pull requests response
        $responseBody = (string) $response->getBody();
        $branches = $this->parseBranchesFromPullRequests($responseBody);

        return $branches;
    }

    /**
     * @param string $pullRequestsJson Data returned from Bitbucket pull requests API.
     *
     * @return Branch[]
     */
    private function parseBranchesFromPullRequests(string $pullRequestsJson): array
    {
        $pullRequests = json_decode($pullRequestsJson, true);
        return array_map(
            function ($pullRequest) {
                $branchName = sprintf(
                    'origin/%s',
                    $pullRequest['fromRef']['displayId']
                );
                return new Branch($branchName);
            },
            $pullRequests['values']
        );
    }
}
