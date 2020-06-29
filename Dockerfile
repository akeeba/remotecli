# Use the latest PHP 7 CLI based on Alpine Linux
FROM php:7-alpine

# Labels describing what this is all about
LABEL org.label-schema.name = "AkeebaRemoteCLI" \
      org.label-schema.description= "Akeeba Remote Control CLI (Dockerized) takes and download remote backups using Akeeba Backup and Akeeba Solo" \
      org.label-schema.usage = "https://www.akeeba.com/documentation/arccli.html" \
      org.label-schema.vcs-url = "https://github.com/akeeba/remotecli" \
      org.label-schema.vendor = "Akeeba Ltd" \
      org.label-schema.schema-version = "1.0" \
      org.label-schema.docker.cmd = "docker run --rm akeebaltd/remotecli --action backup"

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