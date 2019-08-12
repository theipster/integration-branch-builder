# Integration branch builder

Tool for building disposable integration branches

## Rationale

For large teams working on multiple projects simultaneously, where the SDLC isn't mature enough (e.g. poor test coverage, manual risk assessment process, etc.) to support modern branching models such as trunk-based development, the next best thing is to minimise the friction required to create an integration branch in preparation for a deployment.

## Usage

To build a new branch from Bitbucket pull requests:

```php
$ php application.php build:bitbucket
```
