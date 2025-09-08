PU			:=	vendor/bin/phpunit

#
# No idea which version this is good for (maybe the ORIG version)...
#
#PLITE_ENV		:=	file_provider_local_root_dir=~/test/plite/fprov

#
# Uncomment this to test a ==CLOUD web== or a ==CLI(test)== config.
#
PLITE_ENV		:=	_plite_app=plite _plite_config=TestConfig

#
# Uncomment this to test a --LOCAL web-- config.
#
#PLITE_ENV		:=	plite_local_root=/Users/srv/plite/test plite_url_app_regex="[:alnum:_-]*"

#
# Uncomment this to test a --LOCAL CLI-- config.
#
#PLITE_ENV		:=	plite_local_root=/Users/srv plite_app=plite


.PHONY			:	all
all			:	test


.PHONY:				update
update:				composer-install

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
