FROM postgres:17.4-bookworm

# Install PostGIS and dependencies
RUN apt-get update && apt-get install -y \
    postgresql-17-postgis-3 \
    postgresql-17-postgis-3-scripts \
    && rm -rf /var/lib/apt/lists/*

# Switch to postgres user to run init scripts
USER postgres
