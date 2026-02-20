FROM redis:7.4-bookworm

# Install envsubst for environment variable substitution
RUN apt-get update && apt-get install -y gettext-base && \
    apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY ./docker/redis/redis.conf.template /usr/local/etc/redis/redis.conf.template

# Use envsubst at runtime to substitute variables
CMD ["sh", "-c", "envsubst < /usr/local/etc/redis/redis.conf.template > /usr/local/etc/redis/redis.conf && redis-server /usr/local/etc/redis/redis.conf"]
