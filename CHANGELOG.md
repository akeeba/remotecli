# Akeeba Remote CLI 2.1.2

* Download after backup was broken with newer Akeeba Backup / Akeeba Solo versions

# Akeeba Remote CLI 2.1.1

* `--certificate` option allows you to specify self-signed SSL certificates
* Downloading archives without specifying a part number was broken
* Using local configuration files wouldn't work under PHP 7.4

# Akeeba Remote CLI 2.1.0

* Automatically detect the correct encapsulation. The --encapsulation option is ignored.
* Would not work without cURL
* Spoof the User Agent string to work around servers blocking cURL or PHP stream wrappers

# Akeeba Remote CLI 2.0.2

* Connection problems with Akeeba Backup for WordPress and sites hosted in subdirectories
* Machine readable format only worked with the long --machine-readable switch but not the short switch (-m)
* Copyright banner shown even when the machine readable format is being used 

# Akeeba Remote CLI 2.0.1

* delete and deletefiles did not work
* listbackups does not list the latest backup

# Akeeba Remote CLI 2.0.0

* Refactored engine to ensure better support for future versions of the remote JSON API 
