SHELL := /bin/bash

.DEFAULT_GOAL := help

help: ## Display this help.
	@printf "\nUsage:\n  make \033[36m<target>\033[0m"
	@awk 'BEGIN {FS = ":.*##"; printf "\033[36m\033[0m\n\nTargets:\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-24s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)
.PHONY: help

install: ## Install the dependencies with Composer.
	composer install --prefer-dist --no-progress
.PHONY: install

switch-symfony: clean-dependencies remove-code-style-fixer install ## Switch the dependencies to another supported Symfony Framework version for testing.
ifndef version
	@printf "\nUsage:\n  make \033[36mswitch-symfony\033[0m version=\033[33m<version>\033[0m\n\n"
	@exit 1
endif

	composer require "symfony/symfony:$(version).*" --dev --no-update
	composer update symfony/* --prefer-dist --no-progress
.PHONY: switch-symfony-version

test: install ## Run the unit tests.
	./vendor/bin/phpunit
.PHONY: tests

test-with-coverage: install ## Run the unit tests with XML coverage report.
	./vendor/bin/phpunit --coverage-xml coverage-xml
.PHONY: tests-with-coverage

code-style-fix: install ## Fix the code style.
	./vendor/bin/php-cs-fixer fix --allow-risky=yes
.PHONY: code-style

code-style-check: install ## Check the code style.
	./vendor/bin/php-cs-fixer fix --allow-risky=yes --dry-run -v
.PHONY: code-style-check

validate-dependencies: ## Validates the Composer configuration and lockfile.
	composer validate --strict
.PHONY: validate-dependencies

clean-dependencies: ## Clears any changes made to the Composer configuration and removes installed dependencies.
	@git checkout -- composer.*
	@git clean -xf vendor
.PHONY: clean-dependencies

remove-code-style-fixer: ## Removes the PHP CS Fixer.
	composer remove friendsofphp/php-cs-fixer --dev
.PHONY: remove-code-style-fixer
