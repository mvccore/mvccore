<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Request;

interface IConstants {

	/**
	 * Non-secured HTTP scheme (`http:`).
	 * @see https://en.wikipedia.org/wiki/Hypertext_Transfer_Protocol
	 */
	const SCHEME_HTTP = 'http:';

	/**
	 * Secured HTTPS scheme (`https:`).
	 * @see https://en.wikipedia.org/wiki/HTTP_Secure
	 */
	const SCHEME_HTTPS = 'https:';
	/**
	 * Non-secured FTP scheme (`ftp:`).
	 * @see https://en.wikipedia.org/wiki/File_Transfer_Protocol
	 */
	const SCHEME_FTP = 'ftp:';

	/**
	 * Secured FTP scheme (`ftps:`).
	 * @see https://en.wikipedia.org/wiki/File_Transfer_Protocol
	 */
	const SCHEME_FTPS = 'ftps:';

	/**
	 * Non-secured IRC scheme (`irc:`).
	 * @see https://en.wikipedia.org/wiki/Internet_Relay_Chat#URI_scheme
	 */
	const SCHEME_IRC = 'irc:';

	/**
	 * Secured IRC scheme (`ircs:`).
	 * @see https://en.wikipedia.org/wiki/Internet_Relay_Chat#URI_scheme
	 */
	const SCHEME_IRCS = 'ircs:';

	/**
	 * Email scheme (`mailto:`).
	 * @see https://en.wikipedia.org/wiki/Mailto
	 */
	const SCHEME_MAILTO = 'mailto:';

	/**
	 * File scheme (`file:`).
	 * @see https://en.wikipedia.org/wiki/File_URI_scheme
	 */
	const SCHEME_FILE = 'file:';

	/**
	 * Data scheme (`data:`).
	 * @see https://en.wikipedia.org/wiki/Data_URI_scheme
	 */
	const SCHEME_DATA = 'data:';

	/**
	 * Telephone scheme (`tel:`).
	 * @see https://developer.apple.com/library/archive/featuredarticles/iPhoneURLScheme_Reference/PhoneLinks/PhoneLinks.html
	 */
	const SCHEME_TEL = 'tel:';

	/**
	 * Telnet scheme (`telnet:`).
	 * @see https://en.wikipedia.org/wiki/Telnet
	 */
	const SCHEME_TELNET = 'telnet:';

	/**
	 * LDAP scheme (`ldap:`).
	 * @see https://en.wikipedia.org/wiki/Lightweight_Directory_Access_Protocol
	 */
	const SCHEME_LDAP = 'ldap:';

	/**
	 * SSH scheme (`ssh:`).
	 * @see https://en.wikipedia.org/wiki/Secure_Shell
	 */
	const SCHEME_SSH = 'ssh:';

	/**
	 * RTSP scheme (`rtsp:`).
	 * @see https://en.wikipedia.org/wiki/Real_Time_Streaming_Protocol
	 */
	const SCHEME_RTSP = 'rtsp:';

	/**
	 * @see https://en.wikipedia.org/wiki/Real-time_Transport_Protocol
	 * RTP scheme (`rtp:`).
	 */
	const SCHEME_RTP = 'rtp:';


	/**
	 * Retrieves the information or entity that is identified by the URI of the request.
	 */
	const METHOD_GET = 'GET';

	/**
	 * Posts a new entity as an addition to a URI.
	 */
	const METHOD_POST = 'POST';

	/**
	 * Replaces an entity that is identified by a URI.
	 */
	const METHOD_PUT = 'PUT';

	/**
	 * Requests that a specified URI be deleted.
	 */
	const METHOD_DELETE = 'DELETE';

	/**
	 * Retrieves the message headers for the information or entity that is identified by the URI of the request.
	 */
	const METHOD_HEAD = 'HEAD';

	/**
	 * Represents a request for information about the communication options available on the request/response chain identified by the Request-URI.
	 */
	const METHOD_OPTIONS = 'OPTIONS';

	/**
	 * Requests that a set of changes described in the request entity be applied to the resource identified by the Request- URI.
	 */
	const METHOD_PATCH = 'PATCH';

	/**
	 * Requests that performs a message loop-back test along the path to the target resource, providing a useful debugging mechanism.
	 */
	const METHOD_TRACE = 'TRACE';

	
	/**
	 * Param type from URL query string, from URL rewrite, from `$_POST` or from `php://input`.
	 */
	const PARAM_TYPE_ANY			= 0;

	/**
	 * Param type from URL query string.
	 */
	const PARAM_TYPE_QUERY_STRING	= 1;

	/**
	 * Param type declared always from router instance, from URL rewrite process.
	 */
	const PARAM_TYPE_URL_REWRITE	= 2;
	
	/**
	 * Param type from other sources like `$_POST` or `php://input`.
	 */
	const PARAM_TYPE_INPUT			= 4;


	/**
	 * Lower case and upper case alphabet characters only.
	 */
	const PARAM_FILTER_ALPHABETS = 'a-zA-Z';

	/**
	 * Lower case alphabet characters only.
	 */
	const PARAM_FILTER_ALPHABETS_LOWER = 'a-z';

	/**
	 * Upper case alphabet characters only.
	 */
	const PARAM_FILTER_ALPHABETS_UPPER = 'A-Z';

	/**
	 * Lower case and upper case alphabet characters and digits only.
	 */
	const PARAM_FILTER_ALPHABETS_DIGITS = 'a-zA-Z0-9';

	/**
	 * Lower case and upper case alphabet characters and punctuation characters:
	 * - . , SPACE ; ` " ' : ? !
	 */
	const PARAM_FILTER_ALPHABETS_PUNCT = 'a-zA-Z\-\.\, ;`"\'\:\?\!';

	/**
	 * Lower case and upper case alphabet characters, digits with dot, comma, minus
	 * and plus sign and punctuation characters: - . , SPACE ; ` " ' : ? !
	 */
	const PARAM_FILTER_ALPHABETS_NUMERICS_PUNCT = 'a-zA-Z0-9\+\-\.\, ;`"\'\:\?\!';

	/**
	 * Lower case and upper case alphabet characters, digits with dot, comma, minus
	 * and plus sign, punctuation characters: - . , SPACE ; ` " ' : ? !
	 * and special characters: % _ / @ ~ # & $ [ ] ( ) { } | = * ^
	 */
	const PARAM_FILTER_ALPHABETS_NUMERICS_PUNCT_SPECIAL = 'a-zA-Z0-9\+\-\.\, ;`"\'\:\?\!%_/@~\#\&\$\[\]\(\)\{\}\|\=\*\^';

	/**
	 * Punctuation characters only: - . , SPACE ; ` " ' : ? !
	 */
	const PARAM_FILTER_PUNCT = '\-\.\, ;`"\'\:\?\!';

	/**
	 * Special characters only: % _ / @ ~ # & $ [ ] ( ) { } | = * ^
	 */
	const PARAM_FILTER_SPECIAL = '%_/@~\#\&\$\[\]\(\)\{\}\|\=\*\^';

	/**
	 * Digits only from 0 to 9.
	 */
	const PARAM_FILTER_DIGITS = '0-9';

	/**
	 * Digits from 0 to 9 with dot, comma, minus and plus sign and exponent letters.
	 */
	const PARAM_FILTER_NUMERICS = '-\+0-9\.\,eE';
}