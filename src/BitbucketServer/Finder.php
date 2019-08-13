<?php

declare(strict_types=1);

namespace TheIpster\IntegrationBranchBuilder\BitbucketServer;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use TheIpster\IntegrationBranchBuilder\Entities\Branch;

class Finder
{
    /**
     * @var string
     */
    private $apiDomain;

    /**
     * Either "Bearer {token}" / "Basic {token}", depending on Bitbucket setup.
     *
     * @var string
     */
    private $authHeaderValue;

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
     * @param string $apiDomain
     * @param string $authHeaderValue
     */
    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        string $apiDomain,
        string $authHeaderValue
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->apiDomain = $apiDomain;
        $this->authHeaderValue = $authHeaderValue;
    }

    /**
     * @param string $projectKey Bitbucket project key (e.g. "WS")
     * @param string $repositorySlug Bitbucket repository slug (e.g. "servicelayer")
     * @param string $targetBranch Pull request target branch name (e.g. "feature/ABC-123-target")
     *
     * @return RequestInterface
     */
    private function createHttpRequest(
        string $projectKey,
        string $repositorySlug,
        string $targetBranch
    ): RequestInterface {

        // Build URI
        $pullRequestsUri = sprintf(
            '%s/rest/api/1.0/projects/%s/repos/%s/pull-requests?state=OPEN&order=OLDEST&at=refs/heads/%s',
            $this->apiDomain,
            $projectKey,
            $repositorySlug,
            $targetBranch
        );

        // Create request
        return $this->requestFactory->createRequest('GET', $pullRequestsUri)
            ->withHeader('Authorization', $this->authHeaderValue);
    }

    /**
     * @param string $projectKey Bitbucket project key (e.g. "WS")
     * @param string $repositorySlug Bitbucket repository slug (e.g. "servicelayer")
     * @param string $targetBranch Pull request target branch name (e.g. "feature/ABC-123-target")
     *
     * @return Branch[]
     */
    public function getBranchesForPullRequestTarget(
        string $projectKey,
        string $repositorySlug,
        string $targetBranch
    ): array {

        // Create HTTP request
        $request = $this->createHttpRequest(
            $projectKey,
            $repositorySlug,
            $targetBranch
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
