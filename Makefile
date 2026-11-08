.PHONY: verify lint

verify: lint

lint:
	php scripts/lint-php.php
