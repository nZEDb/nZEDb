<?php
namespace nzedb;

use nzedb\db\Settings;
use nzedb\utility\Misc;

/**
 * Resize/save/delete images to disk.
 *
 * Class ReleaseImage
 */
class ReleaseImage
{
	/**
	 * Path to save ogg audio samples.
	 *
	 * @var string
	 */
	public $audSavePath;

	/**
	 * Path to save video preview jpg pictures.
	 *
	 * @var string
	 */
	public $imgSavePath;

	/**
	 * Path to save large jpg pictures(xxx).
	 *
	 * @var string
	 */
	public $jpgSavePath;

	/**
	 * Path to save movie jpg covers.
	 *
	 * @var string
	 */
	public $movieImgSavePath;

	/**
	 * Path to save video ogv files.
	 *
	 * @var string
	 */
	public $vidSavePath;

	/**
	 * Constructor.
	 *
	 * @param Settings $pdo
	 */
	public function __construct(&$pdo = null)
	{
		// Creates the nZEDb_COVERS constant
		if ($pdo === null) {
			$pdo = new Settings();
		}
		//                                                            Table    |  Column
		$this->audSavePath    = nZEDb_COVERS . 'audiosample' . DS; // releases    guid
		$this->imgSavePath    = nZEDb_COVERS . 'preview'     . DS; // releases    guid
		$this->jpgSavePath    = nZEDb_COVERS . 'sample'      . DS; // releases    guid
		$this->movieImgSavePath = nZEDb_COVERS . 'movies'      . DS; // releases    imdbid
		$this->vidSavePath    = nZEDb_COVERS . 'video'       . DS; // releases    guid

		/* For reference. *
		$this->anidbImgPath   = nZEDb_COVERS . 'anime'       . DS; // anidb       anidbid | used in populate_anidb.php, not anidb.php
		$this->bookImgPath    = nZEDb_COVERS . 'book'        . DS; // bookinfo    id
		$this->consoleImgPath = nZEDb_COVERS . 'console'     . DS; // consoleinfo id
		$this->musicImgPath   = nZEDb_COVERS . 'music'       . DS; // musicinfo   id
		$this->audioImgPath   = nZEDb_COVERS . 'audio'       . DS; // unused folder, music folder already exists.
		**/
	}

	/**
	 * Get a URL or file image and convert it to string.
	 *
	 * @param string $imgLoc URL or file location.
	 *
	 * @return bool|mixed|string
	 */
	protected function fetchImage($imgLoc)
	{
		$img = false;
		if (strpos(strtolower($imgLoc), 'http:') === 0 || strpos(strtolower($imgLoc), 'https:') === 0) {
			$img = Misc::getUrl(['url' => $imgLoc]);
		} else if (is_file($imgLoc)) {
			$img = @file_get_contents($imgLoc);
		}
		if ($img !== false) {
			$imagick = new \Imagick();
			$imgFail = false;
			try {
				$imagick->readImageBlob($img);
			} catch (\ImagickException $imgError) {
				echo 'Bad image data, skipping processing' . PHP_EOL;
				$imgFail = true;
			}
			if ($imgFail === false) {
				$im = $imagick->readImageBlob($img);
				if ($im === true) {
					$imagick->clear();
					return $img;
				}
			}
		}

		return false;
	}

	/**
	 * Save an image to disk, optionally resizing it.
	 *
	 * @param string $imgName      What to name the new image.
	 * @param string $imgLoc       URL or location on the disk the original image is in.
	 * @param string $imgSavePath  Folder to save the new image in.
	 * @param string $imgMaxWidth  Max width to resize image to.   (OPTIONAL)
	 * @param string $imgMaxHeight Max height to resize image to.  (OPTIONAL)
	 * @param bool   $saveThumb    Save a thumbnail of this image? (OPTIONAL)
	 *
	 * @return bool                True on success, false on failure. Used on site to check if there
	 * 							   is an image.
	 */
	public function saveImage($imgName, $imgLoc, $imgSavePath, $imgMaxWidth = '', $imgMaxHeight = '', $saveThumb = false) : bool
	{
		// Try to get the image as a string.
		$cover = $this->fetchImage($imgLoc);
		if ($cover === false) {
			// Add check for debug mode and log this error if enabled.
			return false;
		}

		// Check if we need to resize it.
		if ($imgMaxWidth != '' && $imgMaxHeight != '') {
			$imagick = new \Imagick();
			$imagick->readImageBlob($cover);
			$width = $imagick->getImageWidth();
			$height = $imagick->getImageHeight();
			$ratio = min($imgMaxHeight / $height, $imgMaxWidth / $width);
			// New dimensions
			$new_width = (int)($ratio * $width);
			$new_height = (int)($ratio * $height);
			if ($new_width < $width && $new_width > 10 && $new_height > 10) {
				$imagick->thumbnailImage($new_width, $new_height, true);
				$imagick->setImageFormat('jpeg');
				$thumb = $imagick->getImageBlob();
				$imagick->clear();

				if ($saveThumb) {
					@file_put_contents($imgSavePath . $imgName . '_thumb.jpg', $thumb);
				} else {
					$cover = $thumb;
				}

				unset($thumb);
			}
			$imagick->clear();
		}

		// Store it on the hard drive.
		$coverPath = $imgSavePath . $imgName . '.jpg';
		$coverSave = @file_put_contents($coverPath, $cover);

		// Check if it's on the drive.
		if ($coverSave === false || !is_file($coverPath)) {
			// Add check for debug mode and log this error if enabled.
			return false;
		}

		return true;
	}

	/**
	 * Delete images for the release.
	 *
	 * @param string      $guid   The GUID of the release.
	 *
	 * @return void
	 */
	public function delete($guid)
	{
		$thumb = $guid . '_thumb.jpg';

		// Audiosample folder.
		@unlink($this->audSavePath . $guid . '.ogg');

		// Preview folder.
		@unlink($this->imgSavePath . $thumb);

		// Sample folder.
		@unlink($this->jpgSavePath . $thumb);

		// Video folder.
		@unlink($this->vidSavePath . $guid . '.ogv');
	}
}
