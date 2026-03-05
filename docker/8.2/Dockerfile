# Base image
FROM serversideup/php:8.2-cli-alpine

# Set working directory
WORKDIR /code

# Enable opcache
ENV PHP_OPCACHE_ENABLE=1

# Switch to root user
USER root

# Install PHP extensions
RUN install-php-extensions gd

# Drop to bash shell
ENTRYPOINT [ "sh" ]
