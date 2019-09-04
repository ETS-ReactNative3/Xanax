<?php

namespace Xanax\Classes;

use Xanax\Classes\Encode as Encode;
use Xanax\Classes\FileObject as FileObject;
use Xanax\Classes\FileSystemHandler as FileSystemHandler;
use Xanax\Classes\DirectoryHandler as DirectoryHandler;
use Xanax\Exception\Stupid\StupidIdeaException as StupidIdeaException;
use Xanax\Exception\FileHandler\FileIsNotExistsException as FileIsNotExistsException;
use Xanax\Exception\FileHandler\TargetIsNotFileException as TargetIsNotFileException;
use Xanax\Implement\FileSystemInterface as FileSystemInterface;
use Xanax\Implement\FileHandlerInterface as FileHandlerInterface;
use Xanax\Implement\DirectoryHandlerInterface as DirectoryHandlerInterface;
use Xanax\Validation\FileValidation as FileValidation;
use Xanax\Message\FileHandler\FileHandlerMessage as FileHandlerMessage;

class FileHandler implements FileHandlerInterface {
	
	protected $useStatFunction = ["stat", "lstat", "file_exists", "is_writable", "is_readable", "is_executable", "is_file", "is_dir", "is_link", "filectime", "fileatime", "filemtime", "fileinode", "filegroup", "fileowner", "filesize", "filetype", "fileperms"];

	private static $lastError;
	private $strictMode = true;
	private $fileSystemHandler;
	private $directoryHandler;
	
	public function __construct ( $useStrictMode = true, FileHandlerInterface $fileSystemHandler = null, DirectoryHandlerInterface $directoryHandler = null  ) {
		$this->strictMode = $useStrictMode;
		$this->fileSystemHandler = $fileSystemHandler;// || new FileSystemHandler();
		$this->directoryHandler = $directoryHandler;// || new DirectoryHandler();
	}
	
	public function clearStatatusCache ( $filePath ) :void {
		clearstatcache(true, $filePath);
	}
	
	protected function convertToNomalizePath ( $filePath ) {
		return rtrim($filePath, DIRECTORY_SEPARATOR); // Remove last Directory separator
	}
	
	public function isValidHandler ( $fileHandler ) {
		if ( getType($fileHandler) !== "resource" ) {
			return false;
		}
		
		if ( get_resource_type ( $fileHandler) !== "stream" ) {
			return false;
		}
		
		return true;
	}
	
	public function createCache ( string $filePath, string $destination ) {
		$filePath = $this->convertToNomalizePath($filePath);
		$destination = $this->convertToNomalizePath($destination);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			return false;
		}
		
		$cached = fopen($filePath, 'w');
		fwrite($destination, ob_get_contents());
		fclose($destination);
		ob_end_flush();
	}
	
	/**
	 * Gets whether the file can be read.
	 *
	 * @param string $filePath    : Path of the file to check
	 *
	 * @return bool
	 */
	public function isReadable ( string $filePath ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		$return = is_readable ($filePath);
		
		return $return;
	}
	
	public function parseINI ( $filePath ) {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		return parse_ini_file( $filePath );
	}
	
	public function getMIMEType ( $filePath ) {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		return mime_content_type ( $filePath );
	}
	
	/**
	 * Gets whether the file is locked.
	 *
	 * @param string $filePath    : Path of the file to check
	 *
	 * @return bool
	 */
	public function isLocked ( $filePath ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( $this->strictMode && !$this->isValidHandler( $filePath ) && !$this->isFile( $filePath ) ) {
			return false;
		}
		
		if ( !$this->isValidHandler($filePath) ) {
			$filePath = fopen($filePath, "r+");
		}
		
		if ( !flock($filePath, LOCK_EX) ) {
			return true;
		}
		
		return false;
	}
	
	public function createLock ( $filePath ) {
		touch( $filePath );
	}
	
	public function Lock ( $fileHandler ) {
		@flock($fileHandler, LOCK_EX);
	}
	
	/**
	 * Check if the file empty.
	 *
	 * @param string $filePath    : Path of the file
	 *
	 * @return bool
	 */
	public function isEmpty ( string $filePath ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isFile( $filePath ) ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		$return = $this->Size( $filePath ) !== 0;
		
		return $return;
	}
	
	/**
	 * Check if the file exists.
	 *
	 * @param string $filePath    : Path of the file
	 *
	 * @return bool
	 */
	public function isExists ( string $filePath ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		$return = file_exists( $filePath );
		
		return $return;
	}
	
	public function isUnknownFile ( string $filePath ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( $this->getType ( $filePath ) === "unknown" ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Gets the symbolic link
	 *
	 * @param string $symbolicLink : Path of symbolic link
	 *
	 * @return bool
	 */
	public function getSymbolicLink ( string $symbolicLink ) {
		if ( !$this->isSymbolicLink( $symbolicLink ) ) {
		}
		
		$return = readlink( $symbolicLink );
		
		return $return;
	}

	public function isSymbolicLink ( string $filePath ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( is_link( $filePath ) && $this->getType ( $filePath ) === "link" ) {
			return true;
		}
		
		return false;
	}
	
	public function isRegularFile ( string $filePath ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( $this->getType ( $filePath ) === "file" ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Make sure the file location exists under a specific folder.
	 *
	 * @param string $basePath : Lowest folder location
	 * @param string $filePath : File path
	 *
	 * @return bool
	 */
	public function isContainFolder ( string $basePath, string $filePath ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		$realBasePath = realpath( $basePath );
		$realFilePath = realpath( dirname ($filePath ) );
		
		if ( $realFilePath === false || strncmp($realFilePath, $realBasePath, strlen($realBasePath)) !== 0 ) {
			return false;
		}
		
		return true;
	}
	
	public function isFile ( string $filePath, array $containDirectory = null ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( FileValidation::isReadable( $filePath ) ) {
			
		}
		
		if ( FileValidation::hasSubfolderSyntax( $filePath ) ) {
			if ( $targetDirectory === null ) {
				throw new StupidIdeaException ( FileHandlerMessage::getDoNotUseSubDirectorySyntaxMessage() );
			} else if ( !$this->isContainFolder( $containDirectory, $filePath ) ) {
				return false;
			}
		}
		
		if ( FileValidation::isPharProtocol( $filePath ) ) {
			throw new StupidIdeaException ( FileHandlerMessage::getDoNotUsePharProtocolMessage() );
		}
		
		$return = is_file ( $filePath );
		
		return $return;
	}
	
	/**
	 * Checks for a match on a line in the file.
	 *
	 * @param string $filePath : Path of file
	 * @param string $string   : Text to compare
	 *
	 * @return bool
	 */
	public function isEqualByLine (  string $filePath, string $string = null ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		$fileObject = new FileObject( $filePath, false, "r" );
		$fileObject->startHandle();
		$bool = $fileObject->isEqualByLine( $string );
		$fileObject->closeFileHandle();
		
		return $bool;
	}
	
	/**
	 * Make sure the file is executable on your system.
	 *
	 * @param string $filePath : Path of file
	 *
	 * @return bool
	 */
	public function isExecutable ( $filePath ) {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			return false;
		}
		
		$this->clearStatatusCache( $filePath );
		$return = is_executable ( $filePath );
		
		return $return;
	}
	
	/**
	 * Gets whether the file can be written to.
	 *
	 * @param string $filePath    : Path of the file to check
	 *
	 * @return bool
	 */
	public function isWritable ( string $filePath ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			return true;
		}
		
		$this->clearStatatusCache( $filePath );
		$return = is_writable ( $filePath );
		
		return $return;
	}
	
	/**
	 * Delete the file.
	 *
	 * @param string $filePath    : Path of the file to delete
	 *
	 * @return bool
	 */
	public function Delete ( string $filePath ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		unlink($filePath);
		
		return true;
	}
	
	/**
	 * Check the size of the file.
	 *
	 * @param string $filePath    : Path of the file to get size
	 *
	 * @return int
	 */
	public function getSize ( string $filePath, bool $humanReadable ) :int {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( $humanReadable ) {
			$this->clearStatatusCache( $filePath );
			
			if (file_exists($file)) {
				$bytes = filesize($file);
			} else {
				$bytes = $file;
			}
			
			$bytes = 0;
			
			if ($bytes > 0) {
				$sizes = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
				$measure = strlen($bytes >> 10);
				$factor = $bytes < (1024 ** 6) ? ($measure > 1 ? floor((($measure - 1) / 3) + 1) : 1) : floor((strlen($bytes) - 1) / 3);
				$capacity = $bytes / pow(1024, $factor);
				$multiBytesPrefix = ($capacity === intval($capacity) ?: "ytes");
				$bytes = sprintf("%s%s%s", $capacity, $sizes[$factor], $multiBytesPrefix);
			}
			
			return $bytes;
		}
		
		$this->clearStatatusCache( $filePath );
		$return = filesize( $filePath );
		
		return $return >= 0 ? $return : -1;
	}
	
	/**
	 * Copy the file.
	 *
	 * @param string $filePath    : Path of the file to copy
	 * @param string $destination : Path to which copied files are to be saved
	 *
	 * @return bool
	 */
	public function Copy ( string $filePath, string $destination ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		$return = copy ( $filePath, $destination );
		
		return $return;
	}

	/**
	 * Combine the two files.
	 *
	 * @param string $filePath  : Path of file
	 * @param string $mergeFile : Path of the file to merge
	 *
	 * @return bool
	 */
	public function Merge ( string $filePath, string $mergeFile ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		$fileObject = new FileObject( $filePath, false, "a" );
		$fileObject->startHandle();
		
		$fileObject->appendContent( $mergeFile );
		
		$fileObject->closeFileHandle();
		
		return true;
	}
	
	/**
	 * Read the file.
	 *
	 * @param string $filePath  : Path of the file
	 * @param int    $length    : Read length
	 * @param int    $writeMode : Mode of file handler
	 *
	 * @return bool
	 */
	public function Read ( string $filePath, int $length = -1, string $mode = 'r' ) {
		$filePath = $this->convertToNomalizePath($filePath);
		
		$fileObject = new FileObject( $filePath, false, $mode );
		if ( !$fileObject->isEnoughFreeSpace() ) {
			$this::$lastError = "Disk space is not enough";
			return false;
		}
		
		$fileObject->startHandle();
		
		if ( !$fileObject->successToStartHandle() ) {
			return false;
		}
		
		if ( !$fileObject->hasReadedContent() ) {
			return "";
		}
		
		if ( $length === -1 ) {
			$fileObject->readAllContent ();
		} else {
			$fileObject->readContent ( $length );
		}
		
		$content = $fileObject->getReadedContent();
		
		$fileObject->closeFileHandle();
		
		return $content;
	}
	
	public function readAllContent ( string $filePath, string $writeMode = 'r' ) {
		$filePath = $this->convertToNomalizePath($filePath);
		
		return $this->Read( $filePath, -1 );
	}
	
	/**
	 * Create a file.
	 *
	 * @param string $filePath   : Path of the file to create
	 * @param string $content    : File contents
	 * @param string $writeMode  : File creation mode
	 *
	 * @return bool
	 */
	public function Write ( string $filePath, string $content = null, string $writeMode = 'w' ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		$fileObject = new FileObject( $filePath, true, $writeMode );
		$fileObject->startHandle();
		
		if ( !$fileObject->successToStartHandle() ) {
			return false;
		}
		
		$fileObject->writeContent( $content );
		
		if ( !$fileObject->successToWriteContent() ) {
			return false;
		}
		
		$fileObject->closeFileHandle();
		
		return true;
	}
	
	/**
	 * Append the contents to the file.
	 *
	 * @param string $filePath    : Path of the file to append contents
	 * @param string $content     : File contents
	 * @param bool   $makeNewFile : If the file does not exist, create a new file.
	 *
	 * @return bool
	 */
	public function appendFileContent( string $filePath, string $content = null, bool $makeNewFile = true ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isFile( $filePath ) && !$makeNewFile ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) && $makeNewFile ) {
			$this->Write($filePath, "", 'w');
		}

		$this->Write($filePath, $content, 'a');
		
		return true;
	}
	
	/**
	 * Bring the last modified time.
	 *
	 * @param string $filePath    : Path of the file to check
	 *
	 * @return string
	 */
	public function getLastModifiedTime ( string $filePath ) :string {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		$this->clearStatatusCache( $filePath );
		$return = fileatime($filePath);
		
		return $return;
	}
	
	/**
	 * Get the file type.
	 *
	 * @param string $filePath    : Path of the file to check
	 *
	 * @return string
	 */
	public function getType ( string $filePath ) :string {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( FileValidation::isPharProtocol( $filePath ) ) {
			throw new StupidIdeaException ( FileHandlerMessage::getDoNotUsePharProtocolMessage() );
		}
		
		$this->clearStatatusCache( $filePath );
		$return = filetype( $filePath );
		
		return $return;
	}
	
	/**
	 * Write the contents of the file backwards.
	 *
	 * @param string $filePath    : Path of the file to write
	 *
	 * @return bool
	 */
	public function reverseContent ( string $filePath ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		$fileLines = file( $filePath );
		$invertedLines = strrev ( array_shift( $fileLines ) );
		
		return $this->Write( $filePath, $invertedLines, 'w' );
	}
	
	public function getBasename ( string $fileName, $extension = null ) :string {
		return basename($fileName, $extension).PHP_EOL;
	}
	
	/**
	 * Get the file's extension.
	 *
	 * @param string $filePath  : Path of the file
	 *
	 * @return string
	 */
	public function getExtention ( string $filePath ) :string {
		$filePath = $this->convertToNomalizePath($filePath);
		
		$return = pathinfo($filePath, PATHINFO_EXTENSION);
		
		return $return;
	}
	
	public function getContent( string $filePath ) :string {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		$fileHandler = fopen( $filePath, 'r' );
		$fileSize = $this->getSize( $filePath );
		$return = fread( $fileHandler, $fileSize );
		fclose( $fileHandler );
		
		return $return;
	}
	
	/**
	 * Download the file.
	 *
	 * @param string $filePath     : Path of the file
	 *
	 * @return string
	 */
	public function Download ( string $filePath ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		$fileHandler = @fopen($filePath, 'rb');
		if ($fileHandler === false) {
			return false;
		}
		
		if ($fileHandler) {
			while(!feof($fileHandler)) {
				print(@fread($fileHandler, 1024 * 8));
				ob_flush();
				flush();
			}
		}
		
		fclose($file);
	}
	
	public function isCorrectInode ( $filePath ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( $this->fileSystemHandler->getCurrentInode() === $this->getInode( $filePath ) ) {
			return true;
		}
		
		return false;
	}
	
	public function getInode ( string $filePath ) {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		return $this->fileSystemHandler->getInodeNumber( $filePath );
	}
	
	/**
	 * Gets the interpreted file content.
	 *
	 * @param string $filePath     : Path of the file
	 *
	 * @return string
	 */
	public function getInterpretedContent ( string $filePath ) :string {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		ob_start();
			
		if (isset( $filePath )) {
			if ( file_exists( $filePath ) ) {
				@include( $filePath );
			} else {
				throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
			}
		}
		
		$return = ob_get_clean();
		
		return $return;
	}
	
	public function requireOnce( string $filePath ) :void {
		$filePath = $this->convertToNomalizePath($filePath);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		require_once $filePath;
	}
	
	/**
	 * Move the file to a specific location.
	 *
	 * @param string $filePath     : Path of the file
	 * @param string $destination  : Where to move the file
	 *
	 * @return string
	 */
	public function Move ( string $source, string $destination ) :bool {
		$filePath = $this->convertToNomalizePath($filePath);
		$destination = $this->convertToNomalizePath($destination);
		
		if ( !$this->isExists( $filePath ) ) {
			throw new FileIsNotExistsException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		if ( !$this->isFile( $source ) ) {
			throw new TargetIsNotFileException ( FileHandlerMessage::getFileIsNotExistsMessage() );
		}
		
		$return = rename( $source, $destination );
		
		return $return;
	}
	
}
