<?php

declare(strict_types=1);

namespace OC\Core\Command\FilesMetadata;

use OCP\FilesMetadata\IFilesMetadataManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Get extends Command {
	public function __construct(
		private IFilesMetadataManager $filesMetadataManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this->setName('metadata:get')
			 ->setDescription('update and returns up-to-date metadata')
			 ->addArgument(
				 'fileId',
				 InputArgument::REQUIRED,
				 'id of the file document'
			 )
			 ->addOption(
				 'background',
				 '',
				 InputOption::VALUE_NONE,
				 'emulate background jobs env'
			 );
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$fileId = (int) $input->getArgument('fileId');
		$metadata = $this->filesMetadataManager->refreshMetadata($fileId);
		$output->writeln(json_encode($metadata, JSON_PRETTY_PRINT));

		return 0;
	}
}
