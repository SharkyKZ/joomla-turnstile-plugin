<?php
/**
 * @copyright   (C) 2022 SharkyKZ
 * @license     GPL-2.0-or-later
 */

defined('_JEXEC') || exit;

use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Utilities\ArrayHelper;

/**
 * Cloudflare Turnstile captcha plugin.
 *
 * @since  1.0.0
 */
final class PlgCaptchaTurnstile extends CMSPlugin
{
	/**
	 * Application instance.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplicationInterface
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Supported error codes.
	 *
	 * @var    string[]
	 * @since  1.0.0
	 */
	private static $errorCodes = array(
		'missing-input-secret',
		'invalid-input-secret',
		'missing-input-response',
		'invalid-input-response',
		'bad-request',
		'timeout-or-duplicate',
		'internal-error',
	);

	/**
	 * Supported script's built-in languages.
	 *
	 * @var    string[]
	 * @since  1.2.0
	 */
	private static $languages = array(
		'ar-eg',
		'ar',
		'de',
		'en',
		'es',
		'fa',
		'fr',
		'id',
		'it',
		'ja',
		'ko',
		'nl',
		'pl',
		'pt',
		'pt-br',
		'ru',
		'tlh',
		'tr',
		'uk',
		'uk-ua',
		'zh',
		'zh-cn',
		'zh-tw',
	);

	/**
	 * Initialises the captcha.
	 *
	 * @param   string|null  $id  The id of the field.
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 * @throws  RuntimeException
	 */
	public function onInit($id = null)
	{
		$this->app->getDocument()->addScript(
			'https://challenges.cloudflare.com/turnstile/v0/api.js',
			array(),
			array('async' => true, 'defer' => true, 'referrerpolicy' => 'no-referrer')
		);

		return true;
	}

	/**
	 * Generates HTML field markup.
	 *
	 * @param   string|null  $name   The name of the field.
	 * @param   string|null  $id     The id of the field.
	 * @param   string|null  $class  The class of the field.
	 *
	 * @return  string  The HTML to be embedded in the form.
	 *
	 * @since  1.0.0
	 */
	public function onDisplay($name = null, $id = null, $class = '')
	{
		$this->loadLanguage();

		$attributes = array(
			'class' => rtrim('cf-turnstile ' . $class),
			'data-sitekey' => $this->params->get('siteKey', ''),
		);

		if ($id !== null && $id !== '')
		{
			$attributes['id'] = $id;
		}

		if ($name !== null && $name !== '')
		{
			$attributes['data-response-field-name'] = $name;
		}

		if ($value = $this->params->get('theme'))
		{
			$attributes['data-theme'] = $value;
		}

		if ($value = $this->params->get('size'))
		{
			$attributes['data-size'] = $value;
		}

		// Use script's built-in language if available.
		$languageTag = strtolower($this->app->getLanguage()->getTag());

		// Use full tag first, fall back to short tag.
		$languageTags = array(
			$languageTag,
			strstr($languageTag, '-', true),
		);

		if ($foundLanguages = array_intersect($languageTags, self::$languages))
		{
			$attributes['data-language'] = reset($foundLanguages);
		}

		$attributes = array_map(
			function ($value)
			{
				return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
			},
			$attributes
		);

		$html = '<div ' . ArrayHelper::toString($attributes) . '></div>';

		ob_start();
		include PluginHelper::getLayoutPath($this->_type, $this->_name, 'noscript');
		$html .= ob_get_clean();

		return $html;
	}

	/**
	 * Makes HTTP request to remote service to verify user's answer.
	 *
	 * @param   string|null  $code  Answer provided by user.
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 * @throws  RuntimeException
	 */
	public function onCheckAnswer($code = null)
	{
		if ($code === null || $code === '')
		{
			// No answer provided, form was manipulated.
			return false;
		}

		try
		{
			$http = HttpFactory::getHttp();
		}
		catch (RuntimeException $exception)
		{
			if (JDEBUG)
			{
				throw $exception;
			}

			// No HTTP transports supported.
			return !$this->params->get('strictMode');
		}

		$data = array(
			'response' => $code,
			'secret' => $this->params->get('secret'),
		);

		try
		{
			$response = $http->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', $data);
			$body = json_decode($response->body);
		}
		catch (RuntimeException $exception)
		{
			if (JDEBUG)
			{
				throw $exception;
			}

			// Connection or transport error.
			return !$this->params->get('strictMode');
		}

		// Remote service error.
		if ($body === null)
		{
			if (JDEBUG)
			{
				$this->loadLanguage();

				throw new RuntimeException($this->app->getLanguage()->_('PLG_CAPTCHA_TURNSTILE_ERROR_INVALID_RESPONSE'));
			}

			return !$this->params->get('strictMode');
		}

		if (!isset($body->success) || $body->success !== true)
		{
			// If error codes are pvovided, use them for language strings.
			if (!empty($body->{'error-codes'}) && is_array($body->{'error-codes'}))
			{
				$this->loadLanguage();

				if ($errors = array_intersect($body->{'error-codes'}, self::$errorCodes))
				{
					throw new RuntimeException($this->app->getLanguage()->_('PLG_CAPTCHA_TURNSTILE_ERROR_' . strtoupper(str_replace('-', '_', reset($errors)))));
				}
			}

			return false;
		}

		return true;
	}
}
