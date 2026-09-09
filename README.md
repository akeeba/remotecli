# Akeeba Remote CLI

[Documentation](https://www.akeeba.com/documentation/arccli.html) • [Download (PHAR)](https://www.akeeba.com/download.html#remotecli) • [Docker image](https://github.com/akeeba/remotecli/pkgs/container/remotecli)

The command line tool to take and download backups remotely using Akeeba Backup for Joomla!, Akeeba Backup for WordPress and Akeeba Solo.

> [!IMPORTANT]
> Developing and maintaining world-class software is neither easy nor free. The development of this software is subsidised by sales of our commercial offerings. If you like this software and would like to see it maintained in the future, please consider [purchasing a subscription](https://www.akeeba.com/subscribe.html) to one of our commercial offerings. _Thank you!_

> [!NOTE]
> The software is free of charge, support is not. We do provide support for this software to subscribers of our commercial backup software offerings: Akeeba Backup Professional for Joomla, Akeeba Backup Professional for WordPress, and Akeeba Solo.

## Using the PHAR version

You can download Akeeba Remote CLI as a PHAR file from [our downloads page](https://www.akeeba.com/download.html#remotecli)/  

To test the connection to a site use

```bash
php remote.phar test --host="https://www.example.com" --token="YOUR_JOOMLA_API_TOKEN"
```

where `https://www.example.com` is your site's URL and `YOUR_JOOMLA_API_TOKEN` is the Joomla API Token of the user account the tool will act as, found in your site's backend under User Menu, Edit Account, Joomla API Token.

Likewise, to take a backup with profile #2 use

```bash
php remote.phar backup --profile=2 --host="https://www.example.com" --token="YOUR_JOOMLA_API_TOKEN"
```

For more information, including how to use Akeeba Remote CLI with older versions of Akeeba Backup and Akeeba Solo, please consult the [documentation](https://www.akeeba.com/documentation/arccli.html).

## Using the Dockerized version

Container images for the Dockerized version are now on GitHub Container Repository. You can find all tagged versions in https://github.com/akeeba/remotecli/pkgs/container/remotecli

To test the connection to a site use

```bash
docker run --rm ghcr.io/akeeba/remotecli test --host="https://www.example.com" --token="YOUR_JOOMLA_API_TOKEN"
```

where `https://www.example.com` is your site's URL and `YOUR_JOOMLA_API_TOKEN` is the Joomla API Token of the user account the tool will act as, found in your site's backend under User Menu, Edit Account, Joomla API Token.

Likewise, to take a backup with profile #2 use

```bash
docker run --rm ghcr.io/akeeba/remotecli backup --profile=2 --host="https://www.example.com" --token="YOUR_JOOMLA_API_TOKEN"
```

For more information, including how to use Akeeba Remote CLI with older versions of Akeeba Backup and Akeeba Solo, please consult the [documentation](https://www.akeeba.com/documentation/arccli.html).

## Authentication

There are two credentials. Provide at least one of them; providing both is allowed, and the token is tried first.

| Option     | Credential                        | API v1 | API v2 | API v3 |
|------------|-----------------------------------|--------|--------|--------|
| `--token`  | Joomla API Token (**recommended**) | no     | no     | yes    |
| `--secret` | Akeeba Backup JSON API Secret Word | yes    | yes    | yes    |

Prefer the **Joomla API Token**. It identifies a Joomla user account, and Akeeba Backup 10.4.0 and later enforce that account's privileges per API method, so you can give an unattended job a least-privilege account which may take backups but not download or delete them. The Secret Word is an unscoped grant over the whole component, and it is deprecated along with the JSON API v2.

The token needs the "API Authentication - Web Services Joomla Token" plugin enabled on your site, and the account holding it needs the API Login privilege. Tokens are a Joomla feature, so Akeeba Solo and Akeeba Backup for WordPress still require the Secret Word.

If you already have automation passing a token to `--secret`, it keeps working: a Secret Word is tried as a token first and as a Secret Word second.

## Requirements

Akeeba Remote CLI runs on PHP 8.2 up to and including PHP 8.6, with the `phar`, `curl` and `json` extensions. Both ends of that range are enforced at startup: the tool refuses to run below PHP 8.2, and equally on PHP 8.7 or later, which it has not been tested against.

The supported range is declared once, as the `require.php` constraint in `composer.json`, and propagated into the code by `phing version-constraints`. Do not edit the version numbers in `remotecli/remote.php` by hand.

## Supported backup software versions

Akeeba Remote CLI supports the Akeeba Backup JSON API v3, v2, and v1 (unencrypted). It works with all Akeeba Backup and Akeeba Solo versions released since _July 2011_, picking the newest API version your site answers on.

The JSON API v3 is a route in Joomla's API application, at `https://www.example.com/api/index.php`. It is available on Joomla sites running Akeeba Backup 9.6.0 and later, and it is the only version which accepts a Joomla API Token.

Give `--host` your site's root URL and we will find the API application ourselves. If you paste the API application's URL instead we will split it for you, into a site and an `--api-endpoint`. The one case that confuses is a site actually installed in a directory called `api`: pass `--api-endpoint` explicitly there.

The minimum supported versions of our backup software for use with this tool are:
* Akeeba Backup for Joomla 3.3.0
* Akeeba Backup for WordPress 1.0.0
* Akeeba Solo 1.0.0

Kindly note that older versions would only run on historic versions of Joomla (below 1.5.20) and PHP (4.4 and 5.0) which should not be used on live sites. 

There was no Akeeba Backup and Remote JSON API for Joomla 1.0 sites; these sites would only run Akeeba Backup's predecessor, JoomlaPack, which used an integration with Joomla 1.0's XML-RPC application. These ancient versions are _not_ supported by Akeeba Remote CLI. 

## Development

To build this software, you need to install [Akeeba Build Tools — Public Packager](https://github.com/akeeba/buildfiles-public) in a directory called `buildfiles` above the working copy of this repository. This lets you use Phing to build the software with `phing git`.

## Regulatory status (EU Cyber Resilience Act)

Akeeba Remote CLI is not monetised and is not placed on the market within the meaning of Regulation (EU) 2024/2847.