<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Contracts;

/**
 * Exception error codes
 *
 * This is the canonical reference of all the exception codes you will see being thrown. These are also the exit codes
 * for the application.
 */
class ExceptionCode
{
	// Error originating on the remote deployment server
	public const DEPLOYMENT_ERROR_GENERIC = 220;

	// Error originating on the remote Akeeba Release System installation
	public const DEPLOYMENT_ERROR_ARS_GENERIC = 221;

	// Failed to create or update an ARS Item for an uploaded file
	public const DEPLOYMENT_ERROR_ARS_ITEM_EDIT_FAILED = 222;

	// Failed to publish an ARS Item
	public const DEPLOYMENT_ERROR_ARS_ITEM_PUBLISH_FAILED = 223;

	// No files found to make a release
	public const NO_FILES_FOUND = 23;

	// Runtime error trying to upload files to remote storage
	public const UPLOADER_ERROR = 80;

	// Invalid configuration file format
	public const CONFIG_INVALID_FORMAT = 90;

	// Miscellaneous configuration issue. This is caused by a configuration mistake.
	public const CONFIG_GENERIC_ERROR = 100;

	// Configuration error: no version defined in release
	public const CONFIG_NO_VERSION = 101;

	// Configuration error: no ARS endpoint specified
	public const CONFIG_NO_ENDPOINT = 102;

	// Configuration error: the specified ARS endpoint is not a URL
	public const CONFIG_INVALID_ARS_ENDPOINT = 103;

	// Configuration error: it's not possible to authenticate to ARS with the given configuration
	public const CONFIG_NO_ARS_AUTHENTICATION = 104;

	// Configuration error: no category defined in release
	public const CONFIG_NO_CATEGORY = 105;

	// Configuration error: invalid step
	public const INVALID_STEP = 106;

	// Configuration error: Invalid connection type
	public const INVALID_CONNECTION_TYPE = 107;

	// Configuration error: Invalid connection hostname
	public const INVALID_HOSTNAME = 108;

	// Configuration error: Invalid CDN hostname (for S3)
	public const INVALID_CDN_HOSTNAME = 109;

	// Configuration error: Invalid connection authentication
	public const INVALID_CONNECTION_AUTH = 110;

	// Configuration error: No S3 bucket was specified
	public const CONFIG_NO_BUCKET = 111;

	// Configuration error: Invalid S3 signature type
	public const CONFIG_INVALID_SIGNATURE = 112;

	// Configuration error: No S3 region was specified for the v4 signature type
	public const CONFIG_NO_REGION = 113;

	// Configuration error: Invalid S3 ACL
	public const CONFIG_INVALID_S3_ACL = 114;

	// Configuration error: Invalid S3 Storage Class
	public const CONFIG_INVALID_S3_STORAGECLASS = 115;

	// Configuration error: Invalid ARS update stream ID
	public const CONFIG_INVALID_UPDATE_STREAM = 116;

	// Configuration error: Invalid connection key
	public const CONFIG_INVALID_CONNECTION_KEY = 117;

	// Configuration error: Invalid update format
	public const CONFIG_INVALID_UPDATE_FORMAT = 118;

	// Configuration error: Using the Joomla API application requires using a Joomla! API token
	public const CONFIG_ARS_JOOMLA_REQUIRES_TOKEN = 119;

	// Logic error whcih doesn't fall into any other category. These are logic errors which require fixing the code.
	public const GENERIC_LOGIC_ERROR = 240;

	// Invalid class property access.
	public const INVALID_PROPERTY = 241;

}