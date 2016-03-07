
none:
	# nothing to be done

update-buildpro:
	wget --no-check-certificate https://raw.githubusercontent.com/ovidiugabriel/buildpro/master/src/buildpro.py
	mkdir src
	mv ./buildpro.py src/buildpro.py

install:
	make update-buildpro
	rm setup.py
	wget --no-check-certificate https://raw.githubusercontent.com/ovidiugabriel/buildpro/master/setup.py
	wget http://pyyaml.org/download/pyyaml/PyYAML-3.11.tar.gz
	tar -zxvf PyYAML-3.11.tar.gz
	cd PyYAML-3.11/
	python setup.py install
	cd ..
	rm PyYAML-3.11.tar.gz
	chmod -R u+w PyYAML-3.11
	if [ "`uname`" == "Linux" ] ; then rm -rf PyYAML-3.11 ; else rmdir /s /q PyYAML-3.11 ; fi
	python setup.py
	chmod +x ./buildpro
test:
	cd test
	buildpro buildpro_test
