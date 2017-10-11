# Help
.SILENT:
.PHONY: help

help: ## Display this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

# Docker
docker-up: ## start docker elasticsearch server
	docker-compose up -d elasticsearch
docker-down: ## start docker elasticsearch server
	docker-compose down

# Testing
test: test-unit test-behavior ## launch full test suite
test-unit: ## launch unit tests
	./vendor/bin/atoum
test-behavior: docker-up ## launch behavior tests
	@/bin/echo -n "Waiting for elasticsearch to be ready ..."
	n=`curl --write-out %{http_code} --silent --output /dev/null http://localhost:9200/_cluster/health`; \
    while [ $${n} -ne 200 ] ; do \
		/bin/echo -n "." ; \
        n=`curl --write-out %{http_code} --silent --output /dev/null http://localhost:9200/_cluster/health`; \
	done; \
	echo " READY !" ; \
	true
	./vendor/bin/behat
	make docker-down
