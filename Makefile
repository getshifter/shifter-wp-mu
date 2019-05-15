DIRS := \
  admin \
	api \
	global \
	includes \
	languages \
	public

FILES := \
	README.txt \
	LICENSE.txt

list:
	find $(DIRS) -type f > files
	ls *.php >> files
	ls $(FILES) >> files
pkg: clean
	mkdir -p pkg
	tar -cvzf pkg/shifter-wp-mu.tgz -T files

clean:
	rm -f pkg/shifter-artifact-helper.tgz

.PHONY: list pkg clean
