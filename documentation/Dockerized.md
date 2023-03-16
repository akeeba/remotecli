## Release process

You must always create a new [GitHub Personal Access Token](https://github.com/settings/tokens/new?scopes=write:packages,read:packages,delete:packages) with the necessary package scopes and a short-lived expiration of 7 days. For more information see “[Working with the Container Registry](https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry)”.

Log into the GitHub Container Registry with

```bash
export CR_PAT=YOUR_GITHUB_PERSONAL_ACCESS_TOKEN
echo $CR_PAT | docker login ghcr.io -u USERNAME --password-stdin
```

Build and tag the image with

```bash
cd /path/to/this/repository
export ARCCLI_LATEST_TAG=`git describe --abbrev=0`
docker rmi ghcr.io/akeeba/remotecli:latest
docker rmi ghcr.io/akeeba/remotecli:$ARCCLI_LATEST_TAG
docker build -t ghcr.io/akeeba/remotecli:latest .
docker tag ghcr.io/akeeba/remotecli:latest ghcr.io/akeeba/remotecli:$ARCCLI_LATEST_TAG
```

Finally, push the image to the GitHub Container Registry with

```bash
docker push ghcr.io/akeeba/remotecli:$ARCCLI_LATEST_TAG
docker push ghcr.io/akeeba/remotecli:latest
```

## Using the image

```bash
docker run --rm ghcr.io/akeeba/remotecli test --host=... --secret=...
```

