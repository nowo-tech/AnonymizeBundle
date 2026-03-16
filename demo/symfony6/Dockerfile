# FrankenPHP + PHP 8.2 for Symfony 6 demo
FROM dunglas/frankenphp:1-php8.2

WORKDIR /app

# Install system deps + PHP extensions (Debian base)
RUN apt-get update && apt-get install -y --no-install-recommends \
	git \
	unzip \
	default-mysql-client \
	postgresql-client \
	libzip-dev \
	libpq-dev \
	netcat-openbsd \
	&& rm -rf /var/lib/apt/lists/* \
	&& install-php-extensions \
		zip \
		pdo_mysql \
		pdo_pgsql \
		pdo_sqlite \
		opcache \
		intl \
	&& pecl install mongodb \
	&& docker-php-ext-enable mongodb

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="/app/vendor/bin:${PATH}"
ENV SERVER_NAME=:80

RUN git config --global --add safe.directory /app && \
	git config --global --add safe.directory /var/anonymize-bundle

# Caddyfile for Symfony (root /app/public). Caddyfile.dev used when APP_ENV=dev.
COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/frankenphp/Caddyfile.dev /etc/frankenphp/Caddyfile.dev

# Entrypoint: wait for DBs + run migrations/fixtures, then start FrankenPHP
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
