<?php namespace Xaamin\Curl\Curl;

use Xaamin\Helpers\Arr;
use UnexpectedValueException;

/**
 * CURL Exception handler
 */
class Exception extends \Exception
{
	private $errors = [
		CURLE_ABORTED_BY_CALLBACK => 'CURLE_ABORTED_BY_CALLBACK',
		CURLE_BAD_CALLING_ORDER => 'CURLE_BAD_CALLING_ORDER',
		CURLE_BAD_CONTENT_ENCODING => 'CURLE_BAD_CONTENT_ENCODING',
		CURLE_BAD_FUNCTION_ARGUMENT => 'CURLE_BAD_FUNCTION_ARGUMENT',
		CURLE_BAD_PASSWORD_ENTERED => 'CURLE_BAD_PASSWORD_ENTERED',
		CURLE_COULDNT_CONNECT => 'CURLE_COULDNT_CONNECT',
		CURLE_COULDNT_RESOLVE_HOST => 'CURLE_COULDNT_RESOLVE_HOST',
		CURLE_COULDNT_RESOLVE_PROXY => 'CURLE_COULDNT_RESOLVE_PROXY',
		CURLE_FAILED_INIT => 'CURLE_FAILED_INIT',
		CURLE_FILE_COULDNT_READ_FILE => 'CURLE_FILE_COULDNT_READ_FILE',
		CURLE_FILESIZE_EXCEEDED => 'CURLE_FILESIZE_EXCEEDED',
		CURLE_FTP_ACCESS_DENIED => 'CURLE_FTP_ACCESS_DENIED',
		CURLE_FTP_BAD_DOWNLOAD_RESUME => 'CURLE_FTP_BAD_DOWNLOAD_RESUME',
		CURLE_FTP_CANT_GET_HOST => 'CURLE_FTP_CANT_GET_HOST',
		CURLE_FTP_CANT_RECONNECT => 'CURLE_FTP_CANT_RECONNECT',
		CURLE_FTP_COULDNT_GET_SIZE => 'CURLE_FTP_COULDNT_GET_SIZE',
		CURLE_FTP_COULDNT_RETR_FILE => 'CURLE_FTP_COULDNT_RETR_FILE',
		CURLE_FTP_COULDNT_SET_ASCII => 'CURLE_FTP_COULDNT_SET_ASCII',
		CURLE_FTP_COULDNT_SET_BINARY => 'CURLE_FTP_COULDNT_SET_BINARY',
		CURLE_FTP_COULDNT_STOR_FILE => 'CURLE_FTP_COULDNT_STOR_FILE',
		CURLE_FTP_COULDNT_USE_REST => 'CURLE_FTP_COULDNT_USE_REST',
		CURLE_FTP_PORT_FAILED => 'CURLE_FTP_PORT_FAILED',
		CURLE_FTP_QUOTE_ERROR => 'CURLE_FTP_QUOTE_ERROR',
		CURLE_FTP_SSL_FAILED => 'CURLE_FTP_SSL_FAILED',
		CURLE_FTP_USER_PASSWORD_INCORRECT => 'CURLE_FTP_USER_PASSWORD_INCORRECT',
		CURLE_FTP_WEIRD_227_FORMAT => 'CURLE_FTP_WEIRD_227_FORMAT',
		CURLE_FTP_WEIRD_PASS_REPLY => 'CURLE_FTP_WEIRD_PASS_REPLY',
		CURLE_FTP_WEIRD_PASV_REPLY => 'CURLE_FTP_WEIRD_PASV_REPLY',
		CURLE_FTP_WEIRD_SERVER_REPLY => 'CURLE_FTP_WEIRD_SERVER_REPLY',
		CURLE_FTP_WEIRD_USER_REPLY => 'CURLE_FTP_WEIRD_USER_REPLY',
		CURLE_FTP_WRITE_ERROR => 'CURLE_FTP_WRITE_ERROR',
		CURLE_FUNCTION_NOT_FOUND => 'CURLE_FUNCTION_NOT_FOUND',
		CURLE_GOT_NOTHING => 'CURLE_GOT_NOTHING',
		CURLE_HTTP_NOT_FOUND => 'CURLE_HTTP_NOT_FOUND',
		CURLE_HTTP_PORT_FAILED => 'CURLE_HTTP_PORT_FAILED',
		CURLE_HTTP_POST_ERROR => 'CURLE_HTTP_POST_ERROR',
		CURLE_HTTP_RANGE_ERROR => 'CURLE_HTTP_RANGE_ERROR',
		CURLE_LDAP_CANNOT_BIND => 'CURLE_LDAP_CANNOT_BIND',
		CURLE_LDAP_INVALID_URL => 'CURLE_LDAP_INVALID_URL',
		CURLE_LDAP_SEARCH_FAILED => 'CURLE_LDAP_SEARCH_FAILED',
		CURLE_LIBRARY_NOT_FOUND => 'CURLE_LIBRARY_NOT_FOUND',
		CURLE_MALFORMAT_USER => 'CURLE_MALFORMAT_USER',
		CURLE_OBSOLETE => 'CURLE_OBSOLETE',
		CURLE_OPERATION_TIMEOUTED => 'CURLE_OPERATION_TIMEOUTED',
		CURLE_OUT_OF_MEMORY => 'CURLE_OUT_OF_MEMORY',
		CURLE_PARTIAL_FILE => 'CURLE_PARTIAL_FILE',
		CURLE_READ_ERROR => 'CURLE_READ_ERROR',
		CURLE_RECV_ERROR => 'CURLE_RECV_ERROR',
		CURLE_SEND_ERROR => 'CURLE_SEND_ERROR',
		CURLE_SHARE_IN_USE => 'CURLE_SHARE_IN_USE',
		CURLE_SSH => 'CURLE_SSH',
		CURLE_SSL_CACERT => 'CURLE_SSL_CACERT',
		CURLE_SSL_CERTPROBLEM => 'CURLE_SSL_CERTPROBLEM',
		CURLE_SSL_CIPHER => 'CURLE_SSL_CIPHER',
		CURLE_SSL_CONNECT_ERROR => 'CURLE_SSL_CONNECT_ERROR',
		CURLE_SSL_ENGINE_NOTFOUND => 'CURLE_SSL_ENGINE_NOTFOUND',
		CURLE_SSL_ENGINE_SETFAILED => 'CURLE_SSL_ENGINE_SETFAILED',
		CURLE_SSL_PEER_CERTIFICATE => 'CURLE_SSL_PEER_CERTIFICATE',
		CURLE_TELNET_OPTION_SYNTAX => 'CURLE_TELNET_OPTION_SYNTAX',
		CURLE_TOO_MANY_REDIRECTS => 'CURLE_TOO_MANY_REDIRECTS',
		CURLE_UNKNOWN_TELNET_OPTION => 'CURLE_UNKNOWN_TELNET_OPTION',
		CURLE_UNSUPPORTED_PROTOCOL => 'CURLE_UNSUPPORTED_PROTOCOL',
		CURLE_URL_MALFORMAT => 'CURLE_URL_MALFORMAT',
		CURLE_URL_MALFORMAT_USER => 'CURLE_URL_MALFORMAT_USER',
		CURLE_WRITE_ERROR => 'CURLE_WRITE_ERROR'
	];
	
	/**
	 * Constructor 
	 * 
	 * @param string 	$message
	 * @param int 		$code
	 */
	function __construct($message, $code)
	{
		if(!Arr::get($this->errors, $code))
		{
			throw new UnexpectedValueException( "Unknown CURL code: $code" );
		}
		
		parent::__construct($this->errors[$code] . ": $message", $code);
	}
	
}