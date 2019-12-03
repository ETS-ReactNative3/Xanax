<?php

declare(strict_types=1);

namespace Xanax\Classes;

class ClientURLLastTransferInformation
{
	
	private static $session;
	
	public function __construct( $session = '' )
	{
		self::$session = $session;
	}
	
	public function getContentType()
	{
		return curl_getinfo(self::$session, CURLINFO_CONTENT_TYPE);
	}
	
	public function getHeaderSize()
	{
		return curl_getinfo(self::$session, CURLINFO_HEADER_SIZE);
	}
	
	public function getUploadedSize()
	{
		return curl_getinfo(self::$session, CURLINFO_SIZE_UPLOAD );
	}
	
	public function getDownloadedSize()
	{
		return curl_getinfo(self::$session, CURLINFO_SIZE_DOWNLOAD );
	}
	
	public function getAverageUploadSpeed()
	{
		return curl_getinfo(self::$session, CURLINFO_SPEED_UPLOAD );
	}
	
	public function getAverageDownloadSpeed()
	{
		return curl_getinfo(self::$session, CURLINFO_SPEED_DOWNLOAD );
	}
	
	public function getUploadContentLength()
	{
		return curl_getinfo(self::$session, CURLINFO_CONTENT_LENGTH_UPLOAD );
	}
	
	public function getDownloadContentLength()
	{
		return curl_getinfo(self::$session, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
	}
	
	public function getHeaderOutput()
	{
		return curl_getinfo(self::$session, CURLINFO_HEADER_OUT);
	}
	
	public function getStatusCode()
	{
		return curl_getinfo(self::$session, CURLINFO_HTTP_CODE);
	}
	
}
