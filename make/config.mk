# =============================================================================
# make/config.mk — shell and variable definitions shared by every module
# =============================================================================

SHELL := /bin/bash

# A stale cwd (dir replaced from the Windows side under WSL) empties CURDIR and breaks every volume mount.
ifeq ($(CURDIR),)
$(error Cannot read the current directory. Run: cd "$$PWD" and try again)
endif

COMPOSER_IMAGE := composer:2
COMPOSER_CACHE_DIR := $(HOME)/.cache/composer

NODE_IMAGE := node:24
NPM_CACHE_DIR := $(HOME)/.npm

# docker --tty fails without a terminal (CI, pipes), so only request one when stdin is a TTY.
DOCKER_TTY := $(shell [[ -t 0 ]] && echo --interactive --tty)
