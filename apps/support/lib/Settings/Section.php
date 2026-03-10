<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Support\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class Section implements IIconSection {
	public function __construct(
		protected readonly IL10N $l,
		protected readonly IURLGenerator $url,
	) {
	}

	/**
	 * {@inheritdoc}
	 */
	public function getID(): string {
		return 'support';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName(): string {
		return $this->l->t('Support');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPriority(): int {
		return 1;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getIcon(): string {
		return $this->url->imagePath('support', 'section.svg');
	}
}
