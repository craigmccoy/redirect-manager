# =============================================================================
# make/composer.mk — run Composer from Docker, no local PHP needed
# =============================================================================

##@ Dependencies

# Create the cache dir first: Docker makes a missing bind-mount source root-owned.
composer: ## Run Composer in Docker (make composer ARGS="update")
	@mkdir -p "$(COMPOSER_CACHE_DIR)"
	@docker run --rm $(DOCKER_TTY) \
		--user "$$(id -u):$$(id -g)" \
		--env COMPOSER_HOME=/tmp/composer \
		--volume "$(COMPOSER_CACHE_DIR):/tmp/composer/cache" \
		--volume "$(CURDIR):/app" \
		--workdir /app \
		$(COMPOSER_IMAGE) $(ARGS)
