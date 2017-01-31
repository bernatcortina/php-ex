# PHP S2I Example

The `php` builder images provide [PHP](http://www.php.net) on OpenShift.

## Repository Organization

You do not need to change anything in your existing PHP project's repository.
However, some files will affect the behavior of the build process:

```
.htaccess                 Apache .htaccess file <1>
index.php                 Example PHP index page
composer.json             List of dependencies to install <2>
.s2i/                     
    bin/                  Location for OpenShift S2I scripts <3>
        assemble          Assemble script, builds your application <3>
        run               Run script, executes your application <3>
        save-artifacts    Save-artifacts script, gathers dependencies <3>
        usage             Usage script, provides end-user help/info <3>
    environment           Environment variables file <4>
openshift/
    templates/
        php-s2i.json      A pre-configured quickstart template for this example repo
```
1. See the Apache Configuration section below
2. See the Composer section below
3. [S2I scripts](https://docs.openshift.org/latest/creating_images/s2i.html#s2i-scripts) documentation
4. See the Environment Variables section below


## Apache Configuration
In case the **DocumentRoot** of the application is nested within the source directory `/opt/app-root/src`,
users can provide their own Apache **.htaccess** file.  This allows the overriding of Apache's behavior and
specifies how application requests should be handled. The **.htaccess** file needs to be located at the root
of the application source.


## Composer
During the build process, `composer install` is automatically executed over the root directory, installing 
dependencies listed in composer.json. The composer.json format is documented [here](https://getcomposer.org/doc/04-schema.md).


## Environment Variables
The PHP image supports a number of environment variables which can be set to
control the configuration and behavior of the PHP runtime.

To set these environment variables as part of your image, you can place them into
[a *_.s2i/environment_* file](https://docs.openshift.org/latest/dev_guide/builds.html#environment-files)
inside your source code repository, or define them in
[the environment section](https://docs.openshift.org/latest/dev_guide/builds.adoc#buildconfig-environment) of the build configuration's `*sourceStrategy*` definition.

You can also set environment variables to be used with an existing image when
[creating new applications](https://docs.openshift.org/latest/dev_guide/application_lifecycle/new_app.html#specifying-environment-variables), or by
[updating environment variables for existing objects](https://docs.openshift.org/latest/dev_guide/environment_variables.html#set-environment-variables) such as deployment configurations.

> **Note:** Environment variables that control build behavior must be set as part of the S2I build configuration or in the *_.s2i/environment_* file to make them available to the build steps.

The following environment variables set their equivalent property value in the php.ini file:

|Variable name |Description |Default
|------------- |----------- |-------
|ERROR_REPORTING |Informs PHP of which errors, warnings and notices you would like it to take action for |E_ALL & ~E_NOTICE
|DISPLAY_ERRORS |Controls whether or not and where PHP will output errors, notices and warnings |ON
|DISPLAY_STARTUP_ERRORS |Cause display errors which occur during PHP's startup sequence to be handled separately from display errors |OFF
|TRACK_ERRORS |Store the last error/warning message in $php_errormsg (boolean) |OFF
|HTML_ERRORS |Link errors to documentation related to the error |ON
|INCLUDE_PATH |Path for PHP source files |.:/opt/app-root/src:/opt/rh/rh-php70/root/usr/share/pear
|SESSION_PATH |Location for session data files |/tmp/sessions
|SHORT_OPEN_TAG |Determines whether or not PHP will recognize code between <? and ?> tags |OFF
|DOCUMENTROOT |Path that defines the DocumentRoot for your application (ie. /public) |/

The following environment variables set their equivalent property value in the opcache.ini file:

|Variable name |Description |Default
|------------- |----------- |-------
|OPCACHE_MEMORY_CONSUMPTION |The OPcache shared memory storage size |16M
|OPCACHE_REVALIDATE_FREQ |How often to check script timestamps for updates, in seconds. 0 will result in OPcache checking for updates on every request. |2

You can also override the entire directory used to load the PHP configuration by setting:

|Variable name |Description |Default
|------------- |----------- |-------
|PHPRC |Sets the path to the php.ini file |/etc/opt/rh/rh-php<php-version>/php.ini
|PHP_INI_SCAN_DIR |Path to scan for additional ini configuration files |/etc/opt/rh/rh-php<php-version>/php.d

You can override the Apache [MPM prefork](https://httpd.apache.org/docs/2.4/mod/mpm_common.html)
settings to increase the performance for of the PHP application. In case you set
the Cgroup limits in Docker, the image will attempt to automatically set the
optimal values. You can override this at any time by specifying the values
yourself:

|Variable name |Description |Default
|------------- |----------- |-------
|HTTPD_START_SERVERS |The [StartServers](https://httpd.apache.org/docs/2.4/mod/mpm_common.html#startservers) directive sets the number of child server processes created on startup. |Default: 8
|HTTPD_MAX_REQUEST_WORKERS |The [MaxRequestWorkers](https://httpd.apache.org/docs/2.4/mod/mpm_common.html#maxrequestworkers) directive sets the limit on the number of simultaneous requests that will be served. `MaxRequestWorkers` was called `MaxClients` before version httpd 2.3.13. |Default: 256 (this is automatically tuned by setting Cgroup limits for the container using this formula: `TOTAL_MEMORY / 15MB`. The 15MB is average size of a single httpd process.

You can use a custom composer repository mirror URL to download packages instead of the default 'packagist.org':

|Variable name |Description |Default
|------------- |----------- |-------
|COMPOSER_MIRROR |Adds a [custom Composer repository mirror URL](https://getcomposer.org/doc/05-repositories.md#types) to composer configuration. Note: This only affects packages listed in composer.json. |packagist.org


## Hot deploy
In order to immediately pick up changes made in your application source code, you need to run your built 
image with the `OPCACHE_REVALIDATE_FREQ=0` environment variable.

You can do so using the `oc` client:

```
$ oc get services
NAME                     CLUSTER-IP      EXTERNAL-IP   PORT(S)    AGE
php-example              172.30.79.234   <none>        8080/TCP   5m
$ oc set env dc/php-example OPCACHE_REVALIDATE_FREQ=0
```
Next, run `oc status` to confirm that an updated deployment has been kicked off.

> **Note:** You should only use this option while developing or debugging; it is not recommended to turn this on in your production environment.


## Accessing Logs
You can use the `oc logs` command to stream the latest log file entries from your running pod:

```
$ oc get pods
NAME                             READY     STATUS      RESTARTS   AGE
php-example-1-build   0/1       Completed   0          26m
php-example-1-hj2k1   1/1       Running     0          23m
$ oc logs php-example-1-hj2k1
```

To stop tailing the logs, press *Ctrl + c*.