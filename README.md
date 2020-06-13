# Akeeba Release Maker

Release software and make it available to your Joomla-based site's Akeeba Release System installation.

## Before you begin

Run `composer install` to update the external requirements.

## How to use

`php /path/to/releasemaker/index.php /other/path/to/release.json`

## Sample release.json file

See [`src/config.json`](src/config.json).

There are four sections: `common`, `pro`, `core`, `pdf`. Common settings apply to everything.

`core` and `pro` apply each to free (`core`) and paid (`pro`) versions. If you only have a free version set the `pro.pattern` to something nonexistent, e.g. "THERE-IS-NO-PRO" and leave the other `pro` keys blank. Likewise, if you only have a paid version set `core.pattern` to something nonexistent and let the other keys blank. If you only have one kind of version and you do not know what to do, consider it "pro". The overall idea is that only _one_ item can be published per core/pro position and that file _will_ have a corresponding update stream published.

The `pdf` section is designed for documentation but can be used to upload anything. All files you specify here will be published to either the `core` or the `pro` upload location. The idea is that the files uploaded through this section do *not* have a corresponding update stream (they are supporting files). Since the original supporting files we were publishing on our site were PDF documentation snapshots the name of the section ended up being "pdf".

### Common section

* *common.version* Version number, e.g. 1.2.3
* *common.date* Release date, e.g. 2015-04-03
* *common.arsapiurl* URL to the site with Akeeba Release System installed, e.g. http://www.example.com (do NOT include index.php?option=com_ars etc)
* *common.username* Your login username, needs to have core.manage privilege for ARS or core.admin (Super User) privilege
* *common.password* Your login password
* *common.token* Your FOF API Token. Used when username and password are left empty.
* *common.category* integer; the ARS category where things will be published.
* *common.releasedir* Absolute path to the local directory containing the files to publish.
* *common.releaseaccess* integer; numeric ID of Joomla! View Access Level which will have access to the release. Optional.
* *common.repodir* Absolute path to the local directory containing the Git directory of the files you're publishing
* *common.cacert* Absolute path to a custom CA cache file or PEM-encoded certificate to be considered valid for SSL/TLS connections.
* *common.steps* Array of the steps to execute during the release. Do not set to use the default steps.
* *common.update.method* How should I upload the update stream files? "none", "s3", "ftp", "ftps", "sftp", "ftpcurl", "ftpscurl", "sftpcurl". Use the cURL variants whenever possible (wider compatibility).
* *common.update.ftp.hostname* FTP(S) / SFTP hostname for update streams
* *common.update.ftp.port* FTP(S) / SFTP port for update streams (default: 21 for FTP(S), 22 for SFTP)
* *common.update.ftp.username* FTP(S) / SFTP username for update streams
* *common.update.ftp.password* FTP(S) / SFTP password for update streams
* *common.update.ftp.passive* Use FTP(S) passive mode for update streams
* *common.update.ftp.directory* FTP(S) / SFTP initial directory
* *common.update.ftp.pubkeyfile* Optional. For SFTP certificate authentication only. Absolute path to the Public Key file.
* *common.update.ftp.privkeyfile* Optional. For SFTP certificate authentication only. Absolute path to the Private Key file. Not required by newer cURL versions.
* *common.update.ftp.privkeyfile_pass* Optional. For SFTP certificate authentication only. Password for the Private Key file. Does not work when SSH2 or libcurl is compiled against GnuTLS. Always compile against OpenSSL or use unencrypted Private Key files.
* *common.update.ftp.passive_fix* Optional. For ftpcurl and ftpscurl only. When true ignores the IP sent by the server in response to PASV, using its public IP instead.
* *common.update.ftp.timeout* Optional. For sftpcurl, ftpcurl and ftpscurl only. Connection _and upload_ timeout in seconds. Default: 3600 (one hour). 
* *common.update.ftp.verbose* Optional. For sftpcurl, ftpcurl and ftpscurl only. Show cURL output (for debugging only). 
* *common.update.s3.access* S3 Access Key for update streams
* *common.update.s3.secret* S3 Secret Key for update streams
* *common.update.s3.bucket* S3 Bucket for update streams
* *common.update.s3.usessl* Use SSL with S3 for update streams
* *common.update.s3.signature* S3 signature method. "s3" for the legacy method, "v4" for the new AWSv4 API. If you use v4 you also need to specify the region below. Note: Frankfurt a.k.a. eu-central-1 and all newer (post-2014) regions REQUIRE v4 signatures.
* *common.update.s3.region* S3 region. The Amazon S3 region of your bucket, e.g. us-east-1, eu-west-1. The full list of regions can be found at http://docs.aws.amazon.com/general/latest/gr/rande.html#s3_region
* *common.update.s3.directory* S3 base directory for update streams
* *common.update.s3.cdnhostname* CloudFront CDN hostname for the S3 bucket for update streams

### Pro and core section

* *pro.pattern* Filesystem match pattern for Pro file to publish, e.g. `com_foobar*.zip`
* *pro.method* How should I upload Pro files? "s3", "ftp", "ftps", "sftp", "ftpcurl", "ftpscurl", "sftpcurl", "github"
* *pro.update.stream* Numeric ID of ARS update stream for the Pro file
* *pro.update.basename* Base filename for the update stream file, e.g. `something`. Used in conjunction with pro.update.formats to decide the file name of the uploaded update stream files.
* *pro.update.formats* Which update stream format(s) should I upload for the Pro version? One or more of "ini" (`something.ini` containing Live Update INI data), "inibare" (`something` containing Live Update INI data), "xml" (`something.xml` containing Joomla! XML update stream data)
* *pro.groups* array of integers; numeric IDs of Akeeba Subscriptions levels which will have access to the published file. Optional.
* *pro.access* integer; numeric ID of Joomla! View Access Level which will have access to the published file. Optional.

All pro.ftp.* options are used when you use pro.method = "ftp", "ftps", "sftp", "ftpcurl", "ftpscurl", "sftpcurl". They work like common.update.s3.*

All pro.s3.* options are used when you use pro.method = "s3". They work like common.update.s3.* On top of that you have:

* *pro.s3.reldir* The relative directory of pro.s3.directory to the ARS Category directory. Let's say that the ARS Category is configured to use `s3://foo` and you're uploading to the directory `foo/bar/` of the S3 bucket. The pro.s3.reldir must be `bar` since the `foo` directory was included in the ARS Category already.

All core.* options work the same as pro.* options but refer to the "Core" file.

### PDF section

* *pdf.where* How to publish the files. Use "core" or "pro". The publish method and access control will match the corresponding section as configured above.
* *pdf.files* A list of additional files to publish. They don't have to be PDF files. Any type of files will do.

## SFTP authentication modes

There are three supported authentication modes for SFTP connections.

### Password authentication

Uses a username and password.

This is the simplest authentication method but also the least secure one.

Set the respective `username` and `password` keys.

### Public Key (certificate) authentication

Uses a public and private certificate pair.

This is more secure than a password. However, see the Caveat below.

Set the respective `username`, `pubkeyfile`, `privkeyfile` keys. Leave the `password` key empty. 

If your private key file is password protected you need to also set the `privkeyfile_pass` key. 

Caveat: Ubuntu compiles libssh2 against GnuTLS which _does not_ support password-protected key files. You either need a decrypted private key file (bad idea!) or use the SSH Agent authentication method.

### SSH Agent authentication

Uses the SSH Agent and your SSH configuration file (typically: `~/.ssh/config`). On Windows it usually means using PuTTY.

This is compatible with GPG and GPG SmartCards, including YubiKey Neo or newer. Using this method your secret key can be securely stored in a SmartCard. You will be asked to unlock it with your PIN on SFTP authentication. **VERY STRONGLY RECOMMENDED**.

Only set the respective `username` key. DO NOT set a `password`, DO NOT set `pubkeyfile` or `privkeyfile`.

Caveat: you must ALWAYS provide the username, hostname (and port, if it's other than 22). Neither SSH2 nor cURL can use values for these settings specified in the SSH configuration file.

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
