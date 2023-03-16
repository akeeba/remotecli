########################################################################################################################
# Akeeba Remote CLI
#
# Dockerized version
#
# See documentation/Dockerized.md for build instructions
########################################################################################################################

# Use the latest PHP 8 CLI based on Alpine Linux
FROM php:8-alpine

# Labels describing what this is all about
LABEL org.label-schema.name="AkeebaRemoteCLI"
LABEL org.label-schema.description="Akeeba Remote Control CLI (Dockerized) takes and download remote backups using Akeeba Backup and Akeeba Solo"
LABEL org.label-schema.usage="https://www.akeeba.com/documentation/arccli.html"
LABEL org.label-schema.vcs-url="https://github.com/akeeba/remotecli"
LABEL org.label-schema.vendor="Akeeba Ltd"
LABEL org.label-schema.schema-version="1.0"
LABEL org.label-schema.docker.cmd="docker run --rm ghcr.io/akeeba/remotecli backup"
# Labels specific to GitHub Container Registry
LABEL org.opencontainers.image.source="https://github.com/akeeba/remotecli"
LABEL org.opencontainers.image.licenses="GPL-3.0-or-later"
LABEL org.opencontainers.image.description="Akeeba Remote Control CLI (Dockerized) takes and download remote backups using Akeeba Backup and Akeeba Solo"


# Apply an infinite memory limit and set up the correct time zone
RUN echo "memory_limit=-1" > "$PHP_INI_DIR/conf.d/memory-limit.ini" \
&& echo "date.timezone=${PHP_TIMEZONE:-UTC}" > "$PHP_INI_DIR/conf.d/date_timezone.ini"

# Copy Remote CLI into the container and set up the working directory
COPY remotecli /opt/remotecli
WORKDIR /opt/remotecli

# The entry point is Remote CLI's PHP script...
ENTRYPOINT ["docker-php-entrypoint", "php", "/opt/remotecli/remote.php"]

# ...and the default command to run is --license (an alias to --action license)
CMD ["--license"]