# Akeeba Remote CLI

The command line tool to take and download backups remotely using Akeeba Backup for Joomla!, Akeeba Backup for WordPress and Akeeba Solo.

## Prerequisites

In order to build the installation packages of this components you need to have
the following tools:

* A command line environment. bash under Linux / Mac OS X works best. On Windows
  you will need to run most tools using an elevated privileges (administrator)
  command prompt.

* The PHP CLI binary in your path

* Command line Git binaries

* Phing, with the Net_FTP and VersionControl_SVN PEAR packages installed

* libxml and libxslt tools if you intend to build the documentation PDF files

You will also need the following path structure on your system

* remotecli		This repository, a.k.a. MAIN directory
* buildfiles	Akeeba Build Tools (https://github.com/akeeba/buildfiles)

You will need to use the exact folder names specified here.

## Initialising the working copy

All of the following commands are to be run from the MAIN directory. Lines
starting with `$` indicate a Mac OS X / Linux / other UNIX system commands. Lines
starting with `>` indicate Windows commands. The starting character (`$` or `>`) MUST
NOT be typed.

1. You will first need to do the initial link with Akeeba Build Tools, running
   the following command (Mac OS X, Linux, other UNIX systems):

        $ php ../buildfiles/tools/link.php `pwd`

   or, on Windows:

        > php ../buildfiles/tools/link.php %CD%

2. After the initial linking takes place, go inside the build directory:

        $ cd build

   and run the link phing task:

        $ phing link

## Useful Phing tasks

All of the following commands are to be run from the MAIN/build directory.
Lines starting with `$` indicate a Mac OS X / Linux / other UNIX system commands.
Lines starting with `>` indicate Windows commands. The starting character (`$` or `>`)
MUST NOT be typed!

1. Relinking internal files

   This is required after every major upgrade in the component and/or when new plugins and modules are installed. It will create symlinks from the various external repositories to the MAIN directory.

		$ phing link
		> phing link

1. Creating a dev release installation package

   This creates the PHAR packages of the application inside the MAIN/release directory.

		$ phing git
		> phing git

1. Build the documentation in PDF format

		$ phing documentation
		> phing documentation

## Building the Dockerized version

After tagging a releasing a new version the old-fashioned way you can also release the Dockerized version:

    cd /path/to/this/repository
    export ARCCLI_LATEST_TAG=`git describe --abbrev=0`
    docker rmi akeebaltd/remotecli:latest
    docker build -t akeebaltd/remotecli:latest .
    docker tag akeebaltd/remotecli:latest akeebaltd/remotecli:$ARCCLI_LATEST_TAG 
    docker push akeebaltd/remotecli:$ARCCLI_LATEST_TAG
    docker push akeebaltd/remotecli:latest

Test before releasing! The Dockerfile is currently using PHP 7 latest. This might NOT be a version of PHP we have extensively tested in a different way.