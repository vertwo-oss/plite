PU			:=	vendor/bin/phpunit

#PLITE_ENV		:=	file_provider_local_root_dir=~/test/plite/fprov
PLITE_ENV		:=	plite_app=plite plite_config=TestConfig



.PHONY			:	composer-install
composer-install	:
	rm -rf vendor
	composer install
	composer dump-autoload -o



.PHONY			:	composer-distclean
	rm -rf composer.lock



.PHONY			: test
test			:
	echo $(PLITE_ENV)
	$(PLITE_ENV) $(PU) --bootstrap vendor/autoload.php test
