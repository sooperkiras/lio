<?php

namespace Console\App\Commands;

use Art4\JsonApiClient\V1\Document;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use GuzzleHttp\Exception\GuzzleException;

class AppsDescribeCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/{app_id}';

	/**
	 * @var string
	 */
	protected static $defaultName = 'apps:describe';

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('Gets your app description')
			->setHelp('try rebooting')
			->addArgument('app_id', InputArgument::REQUIRED, 'which app would you like to describe?')
			->addOption('json', 'j', InputOption::VALUE_NONE, 'Output as a raw json');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				str_replace('{app_id}', $input->getArgument('app_id'), self::API_ENDPOINT),
				['headers' => $this->httpHelper->getHeaders()]
			);
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			exit(1);
		}

		try {
			if (!empty($input->getOption('json'))) {
				$output->writeln($this->getOutputAsJson($response));
			} else {
				/** @var Document $document */
				$document = Parser::parseResponseString($response->getBody()->getContents());
				$table = $this->getOutputAsTable($document, new Table($output));
				$table->render();
			}
		} catch (ValidationException $e) {
			$output->writeln($e->getMessage());
			exit(1);
		}
	}

	protected function getOutputAsTable(Document $document, Table $table): Table
	{
		$table->setHeaderTitle('App Describe');
		$table->setHeaders([
			'Name', 'Description', 'Status', 'VCPU', 'Memory', 'Replicas',
		]);
		$table->addRow([
			$document->get('data.id'),
			$document->get('data.attributes.description'),
			$document->get('data.attributes.status'),
			$document->get('data.attributes.vcpu'),
			$document->get('data.attributes.memory'),
			$document->get('data.attributes.replicas'),
		]);

		return $table;
	}

	protected function getOutputAsJson(ResponseInterface $response): string
	{
		return $response->getBody()->getContents();
	}
}

