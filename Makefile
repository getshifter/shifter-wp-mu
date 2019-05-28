DIRS := \
  admin \
	api \
	global \
	includes \
	languages \
	public

FILES := \
	README.md \
	LICENSE.txt

list:
	find $(DIRS) -type f > files
	ls *.php >> files
	ls $(FILES) >> files
pkg: clean
	mkdir -p pkg
	tar -cvzf pkg/shifter-wp-mu.tgz -T files

clean:
	rm -f pkg/shifter-wp-mu.tgz

.PHONY: list pkg clean
