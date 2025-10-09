<?php

namespace OCA\Text\Migration;

use OCA\Text\Service\DocumentService;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ResetSessionsBeforeYjs implements IRepairStep {
	public function __construct(private IAppConfig $config,
		private DocumentService $documentService) {
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'Force-reset all Text document sessions';
	}

	public function run(IOutput $output): void {
		$appVersion = $this->config->getValueString('text', 'installed_version');

		if (!$appVersion || version_compare($appVersion, '3.10.1') !== -1) {
			return;
		}

		$this->documentService->clearAll();
	}
}
