# The `connections` section

This section is a dictionary (key-value array). The key of each entry is the connection name you will use in update and file sources. You can use any key name you want, there is no preset list (unlike Akeeba Release Maker 1.x).

The value of each entry is in itself a dictionary (key-value array) which describes how Release Maker will connect to a remote file storage server, such as an (S)FTP server or Amazon S3, to upload your software package and / or update files.

There are three kinds of connection value dictionaries you can use, each one with its own keys. As a result this section is documented a bit differently than the other ones.

## FTP and FTPS connections

Specifies a plain old FTP or an FTP over Explicit SSL/TLS (FTPS) connection.

The following keys are understood:

* `type` Connection type. One of 'ftp', 'ftps', 'ftpcurl', or 'ftpscurl'. `ftp` and `ftps` are actually the same and use PHP's FTP extension which is built-in on most PHP installaitons. Their only difference is that the default value of `secure` in the former case is FALSE while in the latter case is TRUE. Of course if you override the `secure` option you can use the two interchangeably. The `ftpcurl` and `ftpscurl` are likewise treated the same except for the default value of the `secure` option. They use the PHP cURL extension to access the FTP server as long as the cURL extension is enabled and the cURL library it's linked against supports FTP and FTPS access (check your phpinfo).
* `hostname` FTP(S) hostname, e.g. 'ftp.example.com'
* `port` Optional. FTP(S) port. Default: 21.
* `username` FTP(S) username.
* `password` FTP(S) password.
* `passive` Optional. Use FTP(S) passive mode for update streams. Default true.
* `secure` Optional. Is this an FTPS connection? Default: `false` for types 'ftp, 'ftpcurl'; `true` for types 'ftps', 'ftpscurl'.
* `directory` FTP(S) directory where files are uploaded to. This can be overridden in update and file sources.
* `passive_fix` Only valid when using the `ftpcurl` or `ftpscurl` type. When `true` ignores the IP sent by the server in response to PASV, using its public IP instead. Default `true`.
* `timeout` Optional. Total (connection and upload) timeout in seconds. Default: 3600 (one hour).

## SFTP connections

Specifies and SFTP connection. This is NOT the same as FTPS. An SFTP connection provides secure file transfer over the SSH protocol. Strongly recommended instead of FTP/FTPS whenever you have the change to use it.

The following keys are understood:

* `type` Connection type. One of `sftp` or `sftpcurl`. The `sftp` connection type uses the PHP SSH2 extension. This extension may not be available on all servers or PHP versions. If the SSH2 extension is not installed or enabled this will fall back to `sftpcurl`. The `sftpcurl` uses the PHP cURL extension, as long as it's compiled against a cURL library with SFTP support. Check your phpinfo. 
* `hostname` SFTP hostname
* `port` Optional. SFTP port for update streams. Default: 22.
* `username` SFTP username. Please check the [SFTP Authentication](#sftp-authentication) section below.
* `password` SFTP password. Not used when public_key is set. Please check the [SFTP Authentication](#sftp-authentication) section below.
* `public_key` Optional. For SFTP certificate authentication only. Absolute path to the Private Key file. Not required by newer cURL versions. Required when using the `sftp` connection type. Please check the [SFTP Authentication](#sftp-authentication) section below.
* `private_key` Optional. For SFTP certificate authentication only. Password for the Private Key file. Does not work when SSH2 or libcurl is compiled against GnuTLS. Always compile against OpenSSL or use unencrypted Private Key files. Please check the [SFTP Authentication](#sftp-authentication) section below. 
* `private_key_password` Optional. For SFTP certificate authentication only. Absolute path to the Public Key file. Please check the [SFTP Authentication](#sftp-authentication) section below.
* `directory` SFTP directory where files are uploaded to.
* `timeout`. Optional. Total (connection and upload) timeout in seconds. Default: 3600 (one hour).

### SFTP Authentication

There are three supported authentication modes for SFTP connections.

#### Password authentication

Uses a username and password.

This is the simplest authentication method but also the least secure one.

Set the `username` and `password` keys.

#### Public Key (certificate) authentication

Uses a public and private certificate pair.

This is more secure than a password. However, see the Caveat below.

Set the respective `username`, `public_key`, `private_key`, and `private_key_password` keys. Leave the `password` key empty.

If your private key file is password protected you need to also set the `private_key_password` key.

Caveat: Ubuntu compiles libssh2 against GnuTLS which _does not_ support password-protected key files. You either need a decrypted private key file (bad idea!) or use the SSH Agent authentication method.

#### SSH Agent authentication

Uses the SSH Agent and your SSH configuration file (typically: `~/.ssh/config`). On Windows it usually means using PuTTY.

This is compatible with GPG and GPG SmartCards, including YubiKey Neo or newer. Using this method your secret key can be securely stored in a SmartCard. You will be asked to unlock it with your PIN on SFTP authentication. **VERY STRONGLY RECOMMENDED**.

Only set the respective `username` key. DO NOT set a `password`, DO NOT set `public_key` or `private_key`.

Caveat: you must ALWAYS provide the username, hostname (and port, if it's other than 22). Neither SSH2 nor cURL can use values for these settings specified in the SSH configuration file.

## Amazon S3 connections

Specifies a connection to upload files to Amazon S3 or a compatible third party service. The Amazon S3 bucket can be linked to an Amazon CloudFront CDN.

* `type`. Must be `s3`. 
* `endpoint`. Optional. Custom endpoint URL for S3-compatible services. Omit to use Amazon S3 proper. 
* `access`. S3 Access Key. Recommended to use an IAM user with write-only privileges on the specific bucket only. 
* `secret`. S3 Secret Key. Note that you MUST specify the secret, even if you're deploying from EC2. 
* `bucket`. The Bucket where the files will be uploaded to. 
* `tls`. Optional. Should I use HTTPS? Default `true`. Set false to use HTTP (unencrypted connections) — strongly discouraged! 
* `signature`. Optional. S3 request signature method: `v4` or `v2`. Amazon and some third party services use 'v4'. Only use 'v2' with third party services implementing an S3-compatible API which only understands legacy v2 signatures. Default: v4. 
* `region`. Mandatory when `signature` is `v4`. The Amazon S3 region where your bucket lives. Some third party services will need this to be set as well if they are using v4 signatures, e.g. DreamObjects. You can see the bucket region in your AWS console, in the bucket's information tab.
* `directory`. The common prefix (directory in the bucket) where the files will be uploaded. It can be overridden in update and file resources. 
* `cdnhostname`. The hostname of the CDN where the files are publicly accessible from. Used when your Amazon S3 bucket is the source of an Amazon CloudFront distribution. If you are going to allow direct access to your files in your Amazon S3 bucket (BAD IDEA! FAR MORE EXPENSIVE!) you need to enter the hostname of your bucket in the format BUCKETNAME.s3.REGION.amazonaws.com  Please note that buckets with dots in their name are NOT SUPPORTED and [WILL NOT WORK](https://docs.aws.amazon.com/AmazonS3/latest/dev/VirtualHosting.html#VirtualHostingLimitations). Kindly note that leaving this empty will fail to create an Item as of ARS 5.0.0 since support for pre-signed S3 download URLs has been removed as impractical and too expensive for real-world use.
* `acl`. Optional. Amazon S3 [ACL](https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-overview.html#canned-acl) for the uploaded files. 'public-read' is the only one that makes practical sense.
* `storage_class`. Optional. Amazon S3 [storage class key](https://s3browser.com/amazon-s3-storage-classes.aspx). Use 'STANDARD' or omit unless you have a specific reason to use a different storage class. 
* `maximum_age`. Optional. Optional. Cache control maximum age in seconds. Default: 600 seconds. Amazon S3 and Amazon CloudFront will re-read the file from the bucket before returning it after this time. Keep it low to prevent update streams being outdated a long time after you make a new release. Values between 300 and 1800 seconds are recommended.
