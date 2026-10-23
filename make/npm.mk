# =============================================================================
# make/npm.mk — run npm from Docker, no local Node needed
# =============================================================================

# Create the cache dir first: Docker makes a missing bind-mount source root-owned.
# HOME must be writable: the container user has no passwd entry, so npm's default HOME is /.
npm: ## Run npm in Docker (make npm ARGS="run build")
	@mkdir -p "$(NPM_CACHE_DIR)"
	@docker run --rm $(DOCKER_TTY) \
		--user "$$(id -u):$$(id -g)" \
		--env HOME=/tmp \
		--env npm_config_cache=/tmp/npm-cache \
		--volume "$(NPM_CACHE_DIR):/tmp/npm-cache" \
		--volume "$(CURDIR):/app" \
		--workdir /app \
		$(NODE_IMAGE) npm $(ARGS)
