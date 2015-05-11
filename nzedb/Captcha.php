<?php
namespace nzedb;

use nzedb\db\Settings;


class Captcha {
	/**
	 * @var \nzedb\db\Settings
	 */
	private $pdo;

	/**
	 * ReCaptcha Site Key from the
	 * settings database.
	 *
	 * @var bool|string
	 */
	private $sitekey;

	/**
	 * ReCaptcha Secret Key from the
	 * settings database.
	 *
	 * @var bool|string
	 */
	private $secretkey;

	/**
	 * ReCaptcha instance if enabled.
	 *
	 * @var \ReCaptcha\ReCaptcha
	 */
	private $recaptcha;


	/**
	 * Contains the error output if ReCaptcha
	 * validation fails.
	 *
	 * @var string|bool
	 */
	private $error = false;


	/**
	 * List of page routes to apply the captcha.
	 *
	 * @todo Find a better way to enumerate this, I hate literals.
	 * @var array
	 */
	private $captcha_pages = [
		'login',
		'register',
		'contact-us',
		'forgottenpassword'
	];

	/**
	 * $_POST key for the user-supplied ReCaptcha response.
	 */
	const RECAPTCHA_POSTKEY = 'g-recaptcha-response';

	/**
	 * Error literal constants.
	 */
	const RECAPTCHA_ERROR_MISSING_SECRET 	= 'missing-input-secret';
	const RECAPTCHA_ERROR_INVALID_SECRET 	= 'invalid-input-secret';
	const RECAPTCHA_ERROR_MISSING_RESPONSE 	= 'missing-input-response';
	const RECAPTCHA_ERROR_INVALID_RESPONSE 	= 'invalid-input-response';

	/**
	 * Construct.
	 *
	 * @param array $options Class instances.
	 */
	public function __construct(array $options = []) {
		$defaults = [
			'Settings' => null
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
	}

	/**
	 * If site admin setup keys properly,
	 * allow display of recaptcha.
	 *
	 * @param string|bool $page
	 * @return bool
	 */
	public function shouldDisplay($page = false) {
		if ($page !== false) {
			if (in_array($page, $this->captcha_pages) && $this->_bootstrapCaptcha()) {
				return true;
			}
		} elseif ($this->_bootstrapCaptcha()) {
			return true;
		}

		return false;
	}

	/**
	 * Return formatted error messages.
	 *
	 * @return string
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Return sitekey for captcha html display.
	 *
	 * @return bool|string
	 */
	public function getSiteKey() {
		return $this->sitekey;
	}

	/**
	 * Process the submitted captcha and validate.
	 *
	 * @param array $response
	 * @param string $ip
	 * @return bool
	 */
	public function processCaptcha($response, $ip) {
		if (isset($response[self::RECAPTCHA_POSTKEY])) {
			$post_response = $response[self::RECAPTCHA_POSTKEY];
		} else {
			$post_response = '';
		}

		$verify_response = $this->recaptcha->verify($post_response, $ip);

		if (!$verify_response->isSuccess()) {
			$this->_handleErrors($verify_response->getErrorCodes());
			return false;
		}

		return true;
	}

	/**
	 * Build formatted error string for output using
	 * Google's reCaptcha error codes.
	 *
	 * @param array $codes
	 */
	private function _handleErrors($codes) {
		$rc_error = 'ReCaptcha Failed: ';

		foreach ($codes as $c) {
			switch($c) {
				case self::RECAPTCHA_ERROR_MISSING_SECRET:
					$rc_error .= 'Missing Secret Key';
					break;
				case self::RECAPTCHA_ERROR_INVALID_SECRET:
					$rc_error .= 'Invalid Secret Key';
					break;
				case self::RECAPTCHA_ERROR_MISSING_RESPONSE:
					$rc_error .= 'No Response!';
					break;
				case self::RECAPTCHA_ERROR_INVALID_RESPONSE:
					$rc_error .= 'Invalid response! You are a bot!';
					break;
				default:
					$rc_error .= 'Unknown Error!';
			}
		}

		$this->error = $rc_error;
	}

	/**
	 * Instantiate the ReCaptcha library and store it.
	 * Return bool on success/failure.
	 *
	 * @return bool
	 */
	private function _bootstrapCaptcha() {
		if ($this->recaptcha instanceof \ReCaptcha\ReCaptcha) {
			return true;
		}

		$this->sitekey = $this->pdo->getSetting('recaptchasitekey');
		$this->secretkey = $this->pdo->getSetting('recaptchasecretkey');

		if ($this->sitekey != false && $this->sitekey != '') {
			if ($this->secretkey != false && $this->secretkey != '') {
				$this->recaptcha = new \ReCaptcha\ReCaptcha($this->secretkey);
				return true;
			}
		}

		return false;
	}
}
