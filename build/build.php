<?php

define('PATH_ROOT', str_replace('\\', '/', dirname(__DIR__)));

final class PluginBuildScript
{
	private const PLUGIN_ELEMENT = 'turnstile';
	private const PLUGIN_TYPE = 'captcha';

	private const JOOMLA_MINIMUM = '3.8';
	private const JOOMLA_UNSUPPORTED = '5.0';
	private const PHP_MINIMUM = '5.3.10';
	private const PHP_UNSUPPORTED = '8.3';

	private const UPDATE_JOOMLA_REGEX = '(4|3\.([89]|10))';
	private const UPDATE_NAME = 'Captcha - Cloudflare Turnstile';
	private const UPDATE_DESCRIPTION = 'Cloudflare Turnstile plugin.';
	private const REPOSITORY_NAME = 'joomla-turnstile-plugin';

	private string $pluginDirectory;
	private string $pluginName;
	private string $repositoryUrl;
	private string $zipFile;
	private string $version;

	public function __construct()
	{
		$this->pluginDirectory = PATH_ROOT . '/code/plugins/' . self::PLUGIN_TYPE . '/' . self::PLUGIN_ELEMENT;

		$xml = simplexml_load_file($this->pluginDirectory . '/' . self::PLUGIN_ELEMENT . '.xml');
		$this->version = (string) $xml->version;

		$this->pluginName = 'plg_' . self::PLUGIN_TYPE . '_' . self::PLUGIN_ELEMENT;
		$this->repositoryUrl = 'https://github.com/SharkyKZ/' . self::REPOSITORY_NAME;
		$this->zipFile = __DIR__ . '/zips/' . $this->pluginName . '-' . $this->version . '.zip';
	}

	public function build(): void
	{
		$this->buildZip();
		$this->updateUpdateXml();
		$this->updateChangelogXml();
	}

	private function buildZip(): void
	{
		if(!is_dir(__DIR__ . '/zips'))
		{
			mkdir(__DIR__ . '/zips', 0755);
		}

		$zip = new ZipArchive;
		$zip->open($this->zipFile, ZipArchive::CREATE);
		$iterator = new RecursiveDirectoryIterator($this->pluginDirectory);
		$iterator2 = new RecursiveIteratorIterator($iterator);

		foreach ($iterator2 as $file)
		{
			if ($file->isFile())
			{
				$zip->addFile(
					$file->getPathName(),
					str_replace(['\\', $this->pluginDirectory . '/'], ['/', ''], $file->getPathName())
				);
			}
		}

		$zip->addFile(PATH_ROOT . '/LICENSE', 'LICENSE');
		$zip->close();
	}

	private function updateUpdateXml(): void
	{
		$manifestFile = PATH_ROOT . '/updates/updates.xml';
		$xml = simplexml_load_file($manifestFile);
		$children = $xml->children();
		$counter = 0;

		foreach ($children as $update)
		{
			if ((string) $update->version === $this->version)
			{
				unset($children[$counter]);
			}

			$counter++;
		}

		//  Static values.
		$update = $xml->addChild('update');
		$update->addChild('name', self::UPDATE_NAME);
		$update->addChild('description', self::UPDATE_DESCRIPTION);
		$update->addChild('element', self::PLUGIN_ELEMENT);
		$update->addChild('type', 'plugin');
		$update->addChild('folder', self::PLUGIN_TYPE);
		$update->addChild('client', 'site');
		$update->addChild('maintainer', 'SharkyKZ');
		$update->addChild('maintainerurl', $this->repositoryUrl);

		// Dynamic values.
		$update->addChild('version', $this->version);
		$node = $update->addChild('downloads');
		$node = $node->addChild('downloadurl', $this->repositoryUrl . '/releases/download/' . $this->version . '/' . basename($this->zipFile));
		$node->addAttribute('type', 'full');
		$node->addAttribute('format', 'zip');

		foreach (array_intersect(['sha512', 'sha384', 'sha256'], hash_algos()) as $algo)
		{
			$update->addChild($algo, hash_file($algo, $this->zipFile));
		}

		$node = $update->addChild('infourl', $this->repositoryUrl . '/releases/tag/' . $this->version);
		$node->addAttribute('title', self::UPDATE_NAME);
		$update->addChild('changelogurl', 'https://raw.githubusercontent.com/SharkyKZ/' . self::REPOSITORY_NAME . '/master/updates/changelog.xml');

		// System requirements.
		$node = $update->addChild('targetplatform');
		$node->addAttribute('name', 'joomla');
		$node->addAttribute('version', self::UPDATE_JOOMLA_REGEX);
		$update->addChild('php_minimum', self::PHP_MINIMUM);

		file_put_contents($manifestFile, $this->formatXml($xml->asXml()));
	}

	private function updateChangelogXml(): void
	{
		$manifestFile = PATH_ROOT . '/updates/changelog.xml';
		$xml = simplexml_load_file($manifestFile);

		foreach ($xml->children() as $update)
		{
			if ((string) $update->version === $this->version)
			{
				return;
			}
		}

		$changelog = $xml->addChild('changelog');
		$changelog->addChild('element', self::PLUGIN_ELEMENT);
		$changelog->addChild('type', 'plugin');
		$changelog->addChild('version', $this->version);

		file_put_contents($manifestFile, $this->formatXml($xml->asXml()));
	}

	private function formatXml(string $xml): string
	{
		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXml($xml);
		$output = $dom->saveXML();

		return str_replace('  ', "\t", $output);
	}
}

(new PluginBuildScript)->build();
