
MASTER = https://raw.githubusercontent.com/ovidiugabriel/buildpro/master

none:
	# nothing to be done
	#
	#	Options:
	#		update-buildpro
	#		install
	# 		test-gcc
	#

update-buildpro:
	# since the repository contains more projects right now (that are not separated)
	# just pick-up the needed files from the repo
	wget --no-check-certificate $(MASTER)/src/buildpro.py
	mkdir -p src
	mv ./buildpro.py src/

	wget --no-check-certificate $(MASTER)/src/prototyping.py
	mv ./prototyping.py src/

	# compilers package
	wget --no-check-certificate $(MASTER)/src/compiler/__init__.py
	mkdir -p src/compiler
	mv ./__init__.py src/compiler/

	wget --no-check-certificate $(MASTER)/src/compiler/base.py
	mv ./base.py src/compiler/

	# gcc compiler
	wget --no-check-certificate $(MASTER)/src/compiler/gcc.py
	mv ./gcc.py src/compiler

	# download tests project
	wget --no-check-certificate $(MASTER)/test/buildpro_test.project.yml
	mkdir -p test
	mv ./buildpro_test.project.yml test/

	wget --no-check-certificate $(MASTER)/test/buildpro_test.cc
	mv ./buildpro_test.cc test/

install:
	make update-buildpro
	if [ -f setup.py ] ; then rm setup.py ; fi
	wget --no-check-certificate $(MASTER)/setup.py
	wget http://pyyaml.org/download/pyyaml/PyYAML-3.11.tar.gz
	tar -zxvf PyYAML-3.11.tar.gz
	cd PyYAML-3.11/
	if [ -e /usr/bin/python3 ] ; then /usr/bin/python3 setup.py install ; else python setup.py install; fi
	cd ..
	rm PyYAML-3.11.tar.gz
	chmod -R u+w PyYAML-3.11
	if [ "`uname`" == "Linux" ] ; then rm -rf PyYAML-3.11 ; else rmdir /s /q PyYAML-3.11 ; fi
	if [ -e /usr/bin/python3 ] ; then /usr/bin/python3 setup.py ; else python setup.py ; fi
	chmod +x ./buildpro
	sudo ln -s $(realpath ./buildpro) /usr/bin/buildpro

test-gcc:
	cd test ; ../buildpro buildpro_test
