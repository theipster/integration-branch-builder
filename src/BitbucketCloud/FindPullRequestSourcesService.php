<?php

declare(strict_types=1);

namespace TheIpster\IntegrationBranchBuilder\BitbucketCloud;

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
     * @param string $workspace
     * @param string $repositorySlug
     * @param string $targetBranch
     * @param string $authHeaderValue
     *
     * @return RequestInterface
     */
    private function createHttpRequest(
        string $workspace,
        string $repositorySlug,
        string $targetBranch,
        string $authHeaderValue
    ): RequestInterface {

        // Build URI
        $pullRequestsUri = sprintf(
            'https://bitbucket.org/api/2.0/repositories/%s/%s/pullrequests?q=%s&fields=%s&pagelen=%u',
            $workspace,
            $repositorySlug,
            sprintf(
                'state="OPEN"+AND+destination.branch.name="%s"',
                urlencode($targetBranch)
            ),
            'values.source.branch.name',
            50
        );

        // Create request
        return $this->requestFactory->createRequest('GET', $pullRequestsUri)
            ->withHeader('Authorization', $authHeaderValue);
    }

    /**
     * @param string $workspace Bitbucket workspace
     * @param string $repositorySlug Bitbucket repository slug
     * @param string $targetBranch Target branch name
     * @param string $authHeaderValue Bitbucket Cloud API HTTP Auth value
     *
     * @return Branch[]
     */
    public function getBranchesForPullRequestTarget(
        string $workspace,
        string $repositorySlug,
        string $targetBranch,
        string $authHeaderValue
    ): array {

        // Create HTTP request
        $request = $this->createHttpRequest(
            $workspace,
            $repositorySlug,
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
                    $pullRequest['source']['branch']['name']
                );
                return new Branch($branchName);
            },
            $pullRequests['values']
        );
    }
}
