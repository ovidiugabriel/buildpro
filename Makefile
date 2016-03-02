
none:
	# nothing to be done

update-buildpro:
	wget --no-check-certificate https://raw.githubusercontent.com/ovidiugabriel/buildpro/master/src/buildpro.py
	mv buildpro.py src/buildpro.py

install-buildpro:
	wget http://pyyaml.org/download/pyyaml/PyYAML-3.11.tar.gz
	tar -zxvf PyYAML-3.11.tar.gz
	cd PyYAML-3.11/
	python setup.py install
	cd ..
	rm PyYAML-3.11.tar.gz
	chmod -R u+w PyYAML-3.11
	rmdir /s /q PyYAML-3.11

test:
	cd test
	bash $(BUILDPRO_HOME)/buildpro buildpro_test
