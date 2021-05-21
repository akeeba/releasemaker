# The `api` section

This section describes how Akeeba Release Maker will connect to Akeeba Release System on your site.

The section is a dictionary (key-value array). It has the following keys.

## `type`

**Required**: No.

**Type**: String. One of `fof` or `joomla`.

The type of JSON API implementation on your site.

* For ARS 2.x to 6.x inclusive use `fof`. This uses the JSON API provided by the FOF framework.
* For ARS 7.x and later use `joomla`. This uses the separate Joomla API application available in Joomla 4.0 and later. 

## `endpoint`

**Required**: Yes.

**Type**: String.

Akeeba Release System endpoint URL. 
* For ARS 2.x to 6.x inclusive use your site's homepage URL plus `/index.php`. For example `https://www.example.com/index.php`. 
* For ARS 7.x and later use your site's homepage URL plus `/api`. For example `https://www.example.com/api`.

## `connector`

**Required**: No.

**Type**: String. One of `php` or `curl`.

The HTTP connector which will be used when communicating to Akeeba Release System. If not set the default value of `php` will be used.

There are two options:

* `curl` (deprecated). Uses the PHP cURL extension. This connector does not relay the ARS error messages. We also found it to not be working reliably with some sites on Apple computers with Apple Silicon processors.
* `php` (default, recommended). Uses the native HTTP stream wrappers to access ARS. It will also relay ARS error messages where applicable.

## `username`

**Required**: Only when using the `fof` JSON API type and `token` is empty.

**Type**: String.

A Super User username, used to authenticate to ARS.

This option is deprecated. We strongly recommend using the `token` authentication method instead. See also the [Authentication](#authentication) section below.

## `password`

**Required**: Only when using the `fof` JSON API type and `token` is empty.

**Type**: String.

The password to the Super User defined with `username`, used to authenticate to ARS.

This option is deprecated. We strongly recommend using the `token` authentication method instead. See also the [Authentication](#authentication) section below.

## `token`

**Required**: Always when using the `joomla` JSON API type. Otherwise only when both `username` and `password` are empty.

**Type**: String.

* For ARS 5.x to 6.x inclusive this is the FOF personal access token for a user which has `core.create` and `core.edit` privileges to ARS Releases and Items.
* For ARS 7.x this is the Joomla API Token for a Super User.

See also the [Authentication](#authentication) section below.

## `cacert`

**Required**: No.

**Type**: String.

Optional. Absolute path to a file containing on or more PEM-encoded TLS certificate to be considered trusted
Certification Authorities (CA) when making TLS connections. This will be merged with the default cacert.pem
retrieved from cURL's site.

It is typically used to make releases to servers using self-signed certificates, e.g. to your application server when it's behind CloudFlare and uses a CloudFlare issued TLS certificate, or a test or intranet site using a self-signed TLS certificate.

This will be used in all Akeeba Release Maker communications to external servers, not just when contacting your Joomla site where ARS is installed. Therefore you can use this file to provide the TLS certificate of an FTPS server if the Common Name used in the certificate does not match the domain name you are connecting to, as is very common with commercial hosting.

Example: '/home/myuser/my_certificate.pem'

# Authentication

When Akeeba Release Maker is creating or updating an ARS Release and its Items it's using ARS' JSON API. It is essentially POSTing information to URLs directly accessing ARS on your site. These requests happen outside the context of your browser and in a separate Joomla user session each. As a result, Joomla and ARS don't know who you are.

Clearly, we can't allow just about anybody to create releases on our sites. It would be a massive security blunder. ARS integrates with Joomla's access control to determine which user has create, publish and edit privileges for releases and items.

If these requests were sent as-is they would appear to come from an unauthenticated (guest) user which has no privileges to create or edit ARS releases. Therefore the requests need to be authenticated. This can happen in one of two ways:

**Username and password**. This is the legacy method, introduced in 2011. You are essentially sending your Super User username and password in plaintext to your site. Your user will be logged in without checking for an anti-CSRF token or Two Factor Authentication and without going through any code which could prevent password logins for your user. As a result this method carries a lot of risk, even if you are using HTTPS on your site. It is removed in ARS 7.

**FOF Token**. Starting in 2019, the FOF repository includes a [user plugin](https://github.com/akeeba/fof/tree/development/plugins/user/foftoken) which allows you to create a personal access token. The token is never stored directly in your site. Instead, it is constructed from the site's secret (stored in Joomla's `configuration.php`) and a seed created with a cryptographically secure random number generator which is stored in your Joomla user profile information. The token check is performed using time-safe comparisons to prevent timing attacks. This is the most secure method for authenticating to a site. It is also the basis of the Joomla API Token which we contributed to Joomla 4.0. This only applies to ARS 2.x to 6.x inclusive.

  We strongly recommend installing the User – FOF Token plugin on your site and using the FOF token of a sufficiently privileged user to create releases on your site. Always use HTTPS with a valid, commercially-signed TLS certificate on your site to prevent man in the middle attacks.

**Joomla API Token**. This applies to ARS 7 and later running on Joomla 4 and later. We no longer use FOF for these ARS versions. Instead, we use the core MVC and the Joomla API application. Therefore you need the Joomla API token for a Super User on your site. Please note that Joomla 4.0 grants access to the API application **only to Super Users**, long before we can any further access checks. Always use HTTPS with a valid, commercially-signed TLS certificate on your site to prevent man in the middle attacks.

