<?php

namespace Console\App\Helpers;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Files\FilesListCommand;
use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class DeployHelper
{
	const RELEASE_FOLDER = 'releases';

	const PUBLIC_FOLDER = 'public';

	/**
	 * @param string $appType
	 * @param string $appPath
	 * @return bool
	 */
	static public function isCorrectApp(string $appType, string $appPath): bool
	{
		switch ($appType) {
			case 'laravel':
				$composerJson = json_decode(file_get_contents($appPath . 'composer.json'), true);
				return array_key_exists('laravel/framework', $composerJson['require']);
			default:
				return false;
		}
	}

	/**
	 * @param string $appId
	 * @param Application $application
	 * @return string
	 * @throws Exception
	 */
	static public function getActiveRelease(string $appId, Application $application): string
	{
		$filesListCommand = $application->find(FilesListCommand::getDefaultName());
		$args = [
			'command' => FilesListCommand::getDefaultName(),
			'app_id'  => $appId,
			'file_id' => self::PUBLIC_FOLDER,
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($filesListCommand->run(new ArrayInput($args), $bufferOutput) != '0') {
			throw new Exception($bufferOutput->fetch());
		}
		/** @var Document $document */
		$document = Parser::parseResponseString(trim($bufferOutput->fetch()));
		$releaseName = [];
		preg_match('/release_[0-9]*/', $document->get('data.attributes.target'), $releaseName);
		return !empty($releaseName[0]) ? $releaseName[0] : '';
	}

	/**
	 * @param string $appId
	 * @param Application $application
	 * @return array
	 * @throws Exception
	 */
	static public function getReleases(string $appId, Application $application): array
	{
		$filesListCommand = $application->find(FilesListCommand::getDefaultName());
		$args = [
			'command' => FilesListCommand::getDefaultName(),
			'app_id'  => $appId,
			'file_id' => DeployHelper::RELEASE_FOLDER,
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($filesListCommand->run(new ArrayInput($args), $bufferOutput) != '0') {
			throw new Exception($bufferOutput->fetch());
		}
		/** @var Document $document */
		$document = Parser::parseResponseString(trim($bufferOutput->fetch()));
		$releaseKey = 'data.relationships.children.data';
		$document->get($releaseKey);
		$serializer = new ArraySerializer(['recursive' => true]);
		return !empty($document->has($releaseKey)) ? $serializer->serialize($document->get($releaseKey)) : [];
	}

	/**
	 * @param string $appId
	 * @param Application $application
	 * @return bool
	 * @throws Exception
	 */
	static public function isReleasesFolderExists(string $appId, Application $application): bool
	{
		$filesListCommand = $application->find(FilesListCommand::getDefaultName());
		$args = [
			'command' => FilesListCommand::getDefaultName(),
			'app_id'  => $appId,
			'file_id' => self::RELEASE_FOLDER,
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		return $filesListCommand->run(new ArrayInput($args), $bufferOutput) == '0';
	}

	/**
	 * @param string $appId
	 * @param string $release
	 * @param Application $application
	 * @return string
	 * @throws Exception
	 */
	public static function getReleaseConfigContent(string $appId, string $release, Application $application): string
	{
		$filesListCommand = $application->find(FilesListCommand::getDefaultName());
		$args = [
			'command' => FilesListCommand::getDefaultName(),
			'app_id'  => $appId,
			'file_id' => $release . '/' . ConfigHelper::LAMP_IO_CONFIG,
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($filesListCommand->run(new ArrayInput($args), $bufferOutput) != '0') {
			throw new Exception($bufferOutput->fetch());
		}
		/** @var Document $document */
		$document = Parser::parseResponseString(trim($bufferOutput->fetch()));
		return !empty($document->has('data.attributes.contents')) ? $document->get('data.attributes.contents') : '';
	}
}