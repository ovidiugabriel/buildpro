
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
	wget --no-check-certificate https://raw.githubusercontent.com/ovidiugabriel/buildpro/master/src/buildpro.py
	mkdir -p src
	mv ./buildpro.py src/

	wget --no-check-certificate https://raw.githubusercontent.com/ovidiugabriel/buildpro/master/src/prototyping.py
	mv ./prototyping.py src/

	# compilers package
	wget --no-check-certificate https://raw.githubusercontent.com/ovidiugabriel/buildpro/master/src/compiler/__init__.py
	mv ./__init__.py src/compiler/

	wget --no-check-certificate https://raw.githubusercontent.com/ovidiugabriel/buildpro/master/src/compiler/base.py
	mkdir -p src/compiler
	mv ./base.py src/compiler/

	# gcc compiler
	wget --no-check-certificate https://raw.githubusercontent.com/ovidiugabriel/buildpro/master/src/compiler/gcc.py
	mv ./gcc.py src/compiler

	# download tests project
	wget --no-check-certificate https://raw.githubusercontent.com/ovidiugabriel/buildpro/master/test/buildpro_test.project.yml
	mkdir -p test
	mv ./buildpro_test.project.yml test/

	wget --no-check-certificate https://raw.githubusercontent.com/ovidiugabriel/buildpro/master/test/buildpro_test.cc
	mv ./buildpro_test.cc test/

install:
	make update-buildpro
	if [ -f setup.py ] ; then rm setup.py ; fi
	wget --no-check-certificate https://raw.githubusercontent.com/ovidiugabriel/buildpro/master/setup.py
	wget http://pyyaml.org/download/pyyaml/PyYAML-3.11.tar.gz
	tar -zxvf PyYAML-3.11.tar.gz
	cd PyYAML-3.11/
	export PYTHON=python
	if [ -e /usr/bin/python3 ] ; then PYTHON=/usr/bin/python3 ; fi
	$PYTHON setup.py install
	cd ..
	rm PyYAML-3.11.tar.gz
	chmod -R u+w PyYAML-3.11
	if [ "`uname`" == "Linux" ] ; then rm -rf PyYAML-3.11 ; else rmdir /s /q PyYAML-3.11 ; fi
	$PYTHON setup.py
	chmod +x ./buildpro

test-gcc:
	cd test ; ../buildpro buildpro_test
