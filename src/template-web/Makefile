.PHONY:			update
update:
	composer update

.PHONY:			init
init:
	composer init



.PHONY:			vanilla
vanilla:
	/bin/rm -fv .htaccess

.PHONY:			routed
routed:
	/bin/cp -fv ROUTE.access .htaccess



.PHONY:			install
install:
	eb deploy --staged
