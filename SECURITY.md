# Security Policy

## Security Measures

We are very concerned about security. Therefore we have taken some precautions:

- We use [Snyk](https://app.snyk.io/org/adrolli/project/dd7d7d2c-7a0c-4741-ab01-e3d11ea18fa0), a platform that regularly checks our security
- We have enabled all [security features on Github](https://github.com/usetall/tallui/security)
- We use [Dependabot Security](https://github.com/usetall/tallui/security/dependabot) to be immediately aware of security issues in our dependencies and prevent security vulnerabilities throughout the dependency chain
- We use [Renovate](https://renovatebot.com/) to automatically check all dependencies and create automatic pull requests for updates
- We use [PHPStan / Larastan](https://github.com/usetall/tallui/actions/workflows/phpstan.yml), the best static analyzer for PHP and Laravel to catch every bug in our codebase
- We have branch protection enabled and run all check before merging to ```main```
- We always stay on the current stable versions and choose our dependencies with great care

## Supported Versions

We currently support the current version, means the ```main```-Branch and the current packages on packagist.

| Version   | Supported          |
| --------- | ------------------ |
| dev-main  | :white_check_mark: |

## Reporting a Vulnerability

If you spot a potential vulnerability, please go to https://github.com/usetall/tallui/security/advisories and click on the ```Report a vulnerability```-Button or send an email to dev@tallui.io.

Please do not create a GitHub issue for security vulnerabilities. This could allow potential attackers to exploit the vulnerability and cause damage before we've had a chance to patch it.
