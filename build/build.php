<?php

require (dirname(__DIR__)) . '/build-script/script.php';

(
	new PluginBuildScript(
		str_replace('\\', '/', dirname(__DIR__)),
		str_replace('\\', '/', __DIR__),
		'turnstile',
		'captcha',
		'joomla-turnstile-plugin',
		'SharkyKZ',
		'Captcha - Cloudflare Turnstile',
		'Cloudflare Turnstile plugin.',
		'(4\.|3\.([89]|10))',
		'5.3.10',
	)
)->build();
