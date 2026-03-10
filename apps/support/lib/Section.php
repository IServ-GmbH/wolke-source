<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Support;

class Section implements ISection {
	/** @var IDetail[] */
	private array $details = [];

	public function __construct(
		private readonly string $identifier,
		private readonly string $title,
		int $order = 0,
	) {
	}

	public function getIdentifier(): string {
		return $this->identifier;
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function addDetail(IDetail $details): void {
		$this->details[] = $details;
	}

	/** @inheritdoc */
	public function getDetails(): array {
		return $this->details;
	}

	public function createDetail(string $title, string $information, int $type = IDetail::TYPE_SINGLE_LINE): IDetail {
		$detail = new Detail($this->getIdentifier(), $title, $information, $type);
		$this->addDetail($detail);
		return $detail;
	}
}
