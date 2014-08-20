# How to use self-compiled PHP libraries on cloudControl

cloudControl provides the most used PHP extensions like php5-mysql, php5-curl, php5-imagemagick and many more. But sometimes the developer needs a special extension that is not available by default. 
Some additional extensions are provided by PECL. [PECL](http://pecl.php.net/) is a repository of PHP extensions that are made available to you via the PEAR packaging system. The installation of PECL extensions requires compiling on the target system. However, on cloudControl it's not possible to compile in the container because the system-paths are mounted read only.

This ReadMe shows how you can prepare and use PECL-compiled libraries on cloudcontrol.de. 
Here, I'll use the [geoip package from PECL](http://pecl.php.net/package/geoip) as an example. The project is public on GitHub [geoipexample](https://github.com/MatthiasWiesnerCC/geoipexample)

## Preparing the extension

First, you have to compile the PHP extension on a [Ubuntu 12.04.4 LTS (Precise Pangolin)](http://releases.ubuntu.com/12.04/). To do this, I recommend using a virtualization tool, i.e. VirtualBox. Spawn an ubuntu instance, prepare the PHP extension within the instance and copy all necessary files to the shared folder (shared between the virtual instance and the host).

### Vagrant

You can use [Vagrant](http://www.vagrantup.com/) as tool to launch instances. It requires a virtualization tool like VirtualBox (but it uses others as well). Once it is installed, it requires a simple config file to launch an instance. Here's mine:
~~~ruby
# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.vm.box = "Official_Ubuntu_12.04_daily_Cloud_Image_amd64"
  config.vm.box_url = "https://cloud-images.ubuntu.com/vagrant/precise/current/precise-server-cloudimg-amd64-vagrant-disk1.box"

  config.vm.provider :virtualbox do |vb|
    vb.customize ["modifyvm", :id, "--memory", "1024"]
    vb.customize ["modifyvm", :id, "--cpus", "4"]
    vb.customize ["modifyvm", :id, "--ioapic", "on"]
  end
end
~~~

After launching, switch into the instance as root user. Then run the following commands:
~~~bash
add-apt-repository -y ppa:ondrej/php5-oldstable
apt-get --force-yes -y update
apt-get install --force-yes -y php-pear php5-dev libgeoip-dev
pecl install geoip
cp /usr/lib/php5/20100525/geoip.so /vagrant/
cp /usr/lib/libGeoIP.so.1 /vagrant/
~~~

Explanation:

- To compile the PHP extension to work with the cloudControl Pinky Stack, you have to use a newer PHP version that is not provided by Ubuntu precise. So add the php-ppa to the apt repository.
- You must have PHP development and the PEAR package installed.
- The geoip PHP extension needs the development package from libgeoip, so install it too.
- Compile and install the extension
- Copy the compiled PHP extension (geoip.so) and the dependencies that geoip.so uses (libGeoIP.so.1) to the shared folder.

## In your clouControl app

In your cloudControl app, you have to add the compiled PHP extension as well its dependencies. Create a folder i.e. `/lib` in your app's root folder and put the compiled libraries from the shared folder inside.

If you have dependencies like in this example, you need to add the `/lib` folder to the containers `LD_LIBRARY_PATH`. That assures that the dependencies can be found by the PHP extension. This can be done by using the cloudControl Custom Config Add-on. Two config values have to be set:
~~~bash
SET_ENV_VARS=True
LD_LIBRARY_PATH=/app/code/lib
~~~
(Warning: if `SET_ENV_VARS` is set, all config variables are visible on a phpinfo() page.)


Now you have to configure PHP to register the extension. For this, create a file (and folder) `.buildpack/php/conf/geoip.ini` with the content:
~~~config
[geoip]
extension=/app/code/lib/geoip.so
geoip.custom_directory=/app/code/data
~~~

To get the geoip PHP extension working, download the [GeoData from maxmind.com](http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz), extract and put them into a `data` folder. Rename the extracted file from `GeoLiteCity.dat` to `GeoIPCity.dat`.

Finally, add all the new files to the git repository, push and deploy on cloudControl. The extension should be usable. If not, then it is always wise to have a look at the error log.
