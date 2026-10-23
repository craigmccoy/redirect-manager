# =============================================================================
# Makefile — entry point: includes make/*.mk and serves the help menu
# =============================================================================

include make/config.mk
include make/composer.mk
include make/npm.mk

.DEFAULT_GOAL := help

.PHONY: help composer npm

help: ## Show this help menu
	@awk 'BEGIN {FS = ":.*## "} \
		/^##@ / {printf "\n\033[1;38;2;201;182;255m%s\033[0m\n", substr($$0, 5)} \
		/^[a-zA-Z_-]+:.*## / {printf "  \033[38;2;255;179;222m%-12s\033[0m %s\n", $$1, $$2}' \
		$(MAKEFILE_LIST)
