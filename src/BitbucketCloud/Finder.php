<?php

declare(strict_types=1);

namespace TheIpster\IntegrationBranchBuilder\BitbucketCloud;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use TheIpster\IntegrationBranchBuilder\Entities\Branch;

class Finder
{
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
     * @var string
     */
    private $organizationName;

    /**
     * @var string
     */
    private $projectName;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * Constructor
     *
     * @param ClientInterface $client
     * @param RequestFactoryInterface $requestFactory
     * @param string $organizationName
     * @param string $projectName
     * @param string $authHeaderValue
     */
    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        string $organizationName,
        string $projectName,
        string $authHeaderValue
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->organizationName = $organizationName;
        $this->projectName = $projectName;
        $this->authHeaderValue = $authHeaderValue;
    }

    /**
     * @param string $pullRequestTarget
     *
     * @return RequestInterface
     */
    private function createHttpRequest(string $pullRequestTarget): RequestInterface
    {
        $pullRequestsUri = sprintf(
            'https://bitbucket.org/api/2.0/repositories/%s/%s/pullrequests?q=%s&fields=%s&pagelen=%u',
            $this->organizationName,
            $this->projectName,
            sprintf(
                'state="OPEN"+AND+destination.branch.name="%s"',
                urlencode($pullRequestTarget)
            ),
            'values.source.branch.name',
            50
        );
        return $this->requestFactory->createRequest('GET', $pullRequestsUri)
            ->withHeader('Authorization', $this->authHeaderValue);
    }

    /**
     * @param string $pullRequestTarget Target branch name
     *
     * @return Branch[]
     */
    public function getBranchesForPullRequestTarget(string $pullRequestTarget): array
    {
        // Create HTTP request
        $request = $this->createHttpRequest($pullRequestTarget);

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
