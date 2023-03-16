# Akeeba Remote CLI

[Documentation](https://www.akeeba.com/documentation/arccli.html) • [Download (PHAR)](https://www.akeeba.com/download.html#remotecli) • [Docker image](https://github.com/akeeba/remotecli/pkgs/container/remotecli)

The command line tool to take and download backups remotely using Akeeba Backup for Joomla!, Akeeba Backup for WordPress and Akeeba Solo.

## Using the PHAR version

You can download Akeeba Remote CLI as a PHAR file from [our downloads page](https://www.akeeba.com/download.html#remotecli)/  

To test the connection to a site use

```bash
php remote.phar test --host="https://www.example.com" --secret="YOUR_SECRET"
```

where `https://www.example.com` is the endpoint URL and `YOUR_SECRET` is the secret key, both displayed in the Schedule Automatic Backups page of recent versions of Akeeba Backup or Akeeba Solo.

Likewise, to take a backup with profile #2 use

```bash
php remote.phar backup --profile=2 --host="https://www.example.com" --secret="YOUR_SECRET"
```

For more information, including how to use Akeeba Remote CLI with older versions of Akeeba Backup and Akeeba Solo, please consult the [documentation](https://www.akeeba.com/documentation/arccli.html).

## Using the Dockerized version

Container images for the Dockerized version are now on GitHub Container Repository. You can find all tagged versions in https://github.com/akeeba/remotecli/pkgs/container/remotecli

To test the connection to a site use

```bash
docker run --rm ghcr.io/akeeba/remotecli test --host="https://www.example.com" --secret="YOUR_SECRET"
```

where `https://www.example.com` is the endpoint URL and `YOUR_SECRET` is the secret key, both displayed in the Schedule Automatic Backups page of recent versions of Akeeba Backup or Akeeba Solo.

Likewise, to take a backup with profile #2 use

```bash
docker run --rm ghcr.io/akeeba/remotecli backup --profile=2 --host="https://www.example.com" --secret="YOUR_SECRET"
```

For more information, including how to use Akeeba Remote CLI with older versions of Akeeba Backup and Akeeba Solo, please consult the [documentation](https://www.akeeba.com/documentation/arccli.html).

## Supported backup software versions

Akeeba Remote CLI supports the Akeeba Remote JSON API v1 (unencrypted) and v2 on all Akeeba Backup and Akeeba Solo versions released since _July 2011_.

The minimum supported versions of our backup software for use with this tool are:
* Akeeba Backup for Joomla 3.3.0
* Akeeba Backup for WordPress 1.0.0
* Akeeba Solo 1.0.0

Kindly note that older versions would only run on historic versions of Joomla (below 1.5.20) and PHP (4.4 and 5.0) which should not be used on live sites. 

There was no Akeeba Backup and Remote JSON API for Joomla 1.0 sites; these sites would only run Akeeba Backup's predecessor, JoomlaPack, which used an integration with Joomla 1.0's XML-RPC application. These ancient versions are _not_ supported by Akeeba Remote CLI. 

## Important note on the Akeeba Backup JSON API support status

This is one of the two official and supported clients for the Akeeba Backup JSON API, the other being Akeeba UNiTE.

Akeeba Ltd does not provide any kind of support whatsoever for unofficial, third party consumers of the Akeeba Backup JSON API.