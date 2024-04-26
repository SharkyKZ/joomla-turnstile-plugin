#!/usr/bin/env php
<?php

use Sharky\Joomla\PluginBuildScript\Script;

require __DIR__ . '/vendor/autoload.php';

(
	new Script(
		str_replace('\\', '/', dirname(__DIR__)),
		str_replace('\\', '/', __DIR__),
		'turnstile',
		'captcha',
		'joomla-turnstile-plugin',
		'SharkyKZ',
		'Captcha - Cloudflare Turnstile',
		'Cloudflare Turnstile plugin.',
		'(5\.|4\.|3\.([89]|10))',
		'5.3.10',
	)
)->build();
