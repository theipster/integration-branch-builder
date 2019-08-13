# Integration branch builder

Tool for building disposable integration branches

## Rationale

For large teams working on multiple projects simultaneously, where the SDLC isn't mature enough (e.g. poor test coverage, manual risk assessment process, etc.) to support modern branching models such as trunk-based development, the next best thing is to minimise the friction required to create an integration branch in preparation for a deployment.

## Install

```bash
$ composer install -o
```

## Usage

To build a new branch from Bitbucket (Cloud) pull requests:

1. Configure parameters within `/config/packages/BitbucketCloud/parameters.yaml`.
2. Run `php bin/app bitbucketcloud`.
