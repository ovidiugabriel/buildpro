
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
	curl -s $(MASTER)/src/buildpro.py -o buildpro.py
	mkdir -p src
	mv ./buildpro.py src/

	curl -s $(MASTER)/src/prototyping.py -o prototyping.py
	mv ./prototyping.py src/

	curl -s $(MASTER)/src/sublime_folder.py -o sublime_folder.py
	mv ./sublime_folder.py src/

	curl -s $(MASTER)/src/sublime_project.py -o sublime_project.py
	mv ./sublime_project.py src/

	# compilers package
	curl -s $(MASTER)/src/compiler/__init__.py -o __init__.py
	mkdir -p src/compiler
	mv ./__init__.py src/compiler/

	curl -s $(MASTER)/src/compiler/base.py -o base.py
	mv ./base.py src/compiler/

	# gcc compiler
	curl -s $(MASTER)/src/compiler/gcc.py -o gcc.py
	mv ./gcc.py src/compiler

	# download tests project
	curl -s $(MASTER)/test/buildpro_test.project.yml -o buildpro_test.project.yml
	mkdir -p test
	mv ./buildpro_test.project.yml test/

	curl -s $(MASTER)/test/buildpro_test.cc -o buildpro_test.cc
	mv ./buildpro_test.cc test/

install-home:
	if [ -f setup.py ] ; then rm setup.py ; fi
	curl -s $(MASTER)/setup.py -o setup.py
	pip3 install pyyaml --user
        pip3 install termcolor --user

	if [ -e /usr/bin/python3 ] ; then /usr/bin/python3 setup.py ; else python setup.py ; fi
	chmod +x ./buildpro
	if [ ! -f ~/buildpro ] ; then ln -s $(shell realpath ./buildpro) ~/buildpro ; fi
	if [ ! -f ~/.bashrc ] ; then touch ~/.bashrc ; fi
	if [ $(shell cat ~/.bashrc  | grep buildpro | wc -l) == "0" ] ; then echo "alias buildpro='~/buildpro'" >> ~/.bashrc ; fi

test-gcc:
	cd test ; ~/buildpro buildpro_test
