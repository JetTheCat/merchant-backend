.PHONY: up upd down rebuild ssh ssh-bash ssh-db ssh-root

up:
	docker compose up

upd:
	docker compose up -d

down:
	docker compose down --remove-orphans

rebuild:
	docker builder prune && \
	docker compose down --remove-orphans && \
	docker compose build --no-cache --progress=plain

ssh:
	docker exec -it --user www workspace bash

ssh-zsh:
	docker exec -it --user www workspace zsh

ssh-db:
	docker exec -it --user root postgres-db bash

ssh-root:
	docker exec -it --user root workspace bash
