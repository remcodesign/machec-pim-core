## pgvector for PIM Core's local Postgres (D37 — this is the only cluster
## in the system that ever needs it, since pim_products.embedding is the
## only vector column). The base ddev dbimage already has the PGDG apt
## repo configured, so the versioned pgvector package installs directly.
RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends postgresql-16-pgvector \
    && rm -rf /var/lib/apt/lists/*
