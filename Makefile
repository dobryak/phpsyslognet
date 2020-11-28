DOCKER_IMG := phpsyslognet:latest
DOCKER_CMD := docker run --rm -ti -u docker -v $$(pwd):/home/docker/phpsyslognet/ $(DOCKER_IMG)

.PHONY: install
install: ./vendor

./vendor: composer.json composer.lock
	@docker image inspect $(DOCKER_IMG) &>/dev/null || $(MAKE) build
	@$(DOCKER_CMD) ./composer --working-dir=./phpsyslognet/ install

.PHONY: build
build:
	@docker build -t $(DOCKER_IMG) .

.PHONY: tests
tests: ./vendor
	@$(DOCKER_CMD) ./phpsyslognet/vendor/bin/phpunit ./phpsyslognet/tests/
