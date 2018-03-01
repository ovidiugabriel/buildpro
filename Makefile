
# Always use curl instead of wget, because curl is available also on MacOS

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
	curl $(MASTER)/src/buildpro.py -o buildpro.py
	mkdir -p src
	mv ./buildpro.py src/

	curl $(MASTER)/src/prototyping.py -o prototyping.py
	mv ./prototyping.py src/

	# compilers package
	curl $(MASTER)/src/compiler/__init__.py -o __init__.py
	mkdir -p src/compiler
	mv ./__init__.py src/compiler/

	curl $(MASTER)/src/compiler/base.py -o base.py
	mv ./base.py src/compiler/

	# gcc compiler
	curl $(MASTER)/src/compiler/gcc.py -o gcc.py 
	mv ./gcc.py src/compiler

	# download tests project
	curl $(MASTER)/test/buildpro_test.project.yml -o buildpro_test.project.yml
	mkdir -p test
	mv ./buildpro_test.project.yml test/

	curl $(MASTER)/test/buildpro_test.cc -o buildpro_test.cc
	mv ./buildpro_test.cc test/

install-home:
	make update-buildpro
	if [ -f setup.py ] ; then rm setup.py ; fi
	curl $(MASTER)/setup.py -o setup.py
	curl http://pyyaml.org/download/pyyaml/PyYAML-3.11.tar.gz -o PyYAML-3.11.tar.gz
	tar -zxvf PyYAML-3.11.tar.gz
	cd PyYAML-3.11/
	if [ -e /usr/bin/python3 ] ; then /usr/bin/python3 setup.py install ; else python setup.py install; fi
	cd ..
	rm PyYAML-3.11.tar.gz
	chmod -R u+w PyYAML-3.11
	if [ -e /usr/bin/python3 ] ; then /usr/bin/python3 setup.py ; else python setup.py ; fi
	chmod +x ./buildpro
	if [ -e ~/buildpro ] ; then unlink ~/buildpro ; fi
	ln -s $(realpath ./buildpro) ~/buildpro

test-gcc:
	cd test ; ../buildpro buildpro_test
