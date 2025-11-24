FROM mysql:8.4

COPY database_clone.sql /docker-entrypoint-initdb.d/