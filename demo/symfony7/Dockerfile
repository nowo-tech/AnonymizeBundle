# FrankenPHP + PHP 8.2 for Symfony 7 demo
FROM dunglas/frankenphp:1-php8.2

WORKDIR /app

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

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="/app/vendor/bin:${PATH}"
ENV SERVER_NAME=:80

RUN git config --global --add safe.directory /app

COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
