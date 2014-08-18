# How to use self compiled php libraries on cloudcontrol.de

Cloudcontrol.de provides the most used PHP extensions like php5-mysql, php5-curl, php5-imagemagick and many more. But sometimes the developer needs a special extension that is not available by default. 
Some more extensions are provided by Pecl. [Pecl](http://pecl.php.net/) is a repository of PHP extensions that are made available to you via the PEAR packaging system. The installation of Pecl extensions requires the compiling on the target system. But on cloudcontrol.de the developer cannot compile in the container since the system-pathes are mounted read only.

Here I show, how you can prepare and use pecl compiled libraries on cloudcontrol.de. 
For this I use the [geoip package from pecl](http://pecl.php.net/package/geoip) as example. The project is public on github [geoipexample](https://github.com/MatthiasWiesnerCC/geoipexample)

## Prepare extensions

You have to compile the php extension on a [Ubuntu 12.04.4 LTS (Precise Pangolin)](http://releases.ubuntu.com/12.04/). To do this I recommend to us a virtualization tool, i.e. VirtualBox. You spawn an ubuntu instance, prepare the php extension within the instance and copy all necessary files to the shared folder (shared between the virtual instance and the host).

### Vagrant

You can use [Vagrant](http://www.vagrantup.com/) as tool to launch instances. It requires a virtualization tool like VirtualBox (but it uses others as well). Once it is installed, it requires a simple config file to launch an instance. Here is my:
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

After launching, ssh into the instance and make you to root. Then you have to run some commands:
~~~bash
add-apt-repository -y ppa:ondrej/php5-oldstable
apt-get --force-yes -y update
apt-get install --force-yes -y php-pear php5-dev libgeoip-dev
pecl install geoip
cp /usr/lib/php5/20100525/geoip.so /vagrant/
cp /usr/lib/libGeoIP.so.1 /vagrant/
~~~

Explanation:

- To compile the php extension according to the cloudcontrol.de pinky stack, you have to use a newer php version that is not provided by ubuntu precise. So add the php-ppa to the apt repository.
- According to the new php version you have to install the php development and the pear package.
- The geoip php extension needs the development package from libgeoip, so install it too.
- Compile and install the extension
- Copy the compiled php extension (geoip.so) and the dependencies that geoip.so is used (libGeoIP.so.1) to the shared folder.

## In your cloudcontrol app

In your cloudcontrol app you have to add the compiled php extension as well it's dependencies. Create a folder i.e. `/lib` in your app root folder and put the compiled libraries from the shared folder here inside.

If you have dependencies as in this example, you need to add the the `/lib` folder to the containers `LD_LIBRARY_PATH`. That assures that the dependencies can be found by the PHP extension. This can be done by using the cloudcontrol.de config addon. Two config values have to be set:
~~~bash
SET_ENV_VARS=True
LD_LIBRARY_PATH=/app/code/lib
~~~
(Warning: by this way all config variables are visible on a phpinfo() page.)


Now you have to configure php to register the extension. For this you create a file (and folder) `.buildpack/php/conf/geoip.ini` with the content:
~~~config
[geoip]
extension=/app/code/lib/geoip.so
geoip.custom_directory=/app/code/data
~~~

To get the geoip php extension working, you have to download the [GeoData from maxmind.com](http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz), extract and put them into a `data` folder. Rename the extracted file from `GeoLiteCity.dat` to `GeoIPCity.dat`.

Finally add all the new files to the git repository, push and deploy on cloudcontrol.de. The extension should be usable. If not, than it is always wise to have a look on the error log.
