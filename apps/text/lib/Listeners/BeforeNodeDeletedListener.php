<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 Julien Veyssier <eneiluj@posteo.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Text\Listeners;

use OCA\Text\Service\AttachmentService;
use OCA\Text\Service\DocumentService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\File;

/**
 * @template-implements IEventListener<Event|BeforeNodeDeletedEvent>
 */
class BeforeNodeDeletedListener implements IEventListener {
	private AttachmentService $attachmentService;
	private DocumentService $documentService;

	public function __construct(AttachmentService $attachmentService,
		DocumentService $documentService) {
		$this->attachmentService = $attachmentService;
		$this->documentService = $documentService;
	}

	public function handle(Event $event): void {
		if (!$event instanceof BeforeNodeDeletedEvent) {
			return;
		}
		$node = $event->getNode();
		if (!$node instanceof File) {
			return;
		}
		if ($node->getMimeType() === 'text/markdown') {
			$this->attachmentService->deleteAttachments($node);
		}
		$this->documentService->resetDocument($node->getId(), true);
	}
}
