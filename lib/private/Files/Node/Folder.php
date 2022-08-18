<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Files\Node;

use OC\Files\Cache\QuerySearchHelper;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Cache\Wrapper\CacheJail;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OC\Files\Search\SearchQuery;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\FileInfo;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchOrder;
use OCP\Files\Search\ISearchQuery;
use OCP\IUserManager;

class Folder extends Node implements \OCP\Files\Folder {
	/**
	 * Creates a Folder that represents a non-existing path
	 *
	 * @param string $path path
	 * @return string non-existing node class
	 */
	protected function createNonExistingNode($path) {
		return new NonExistingFolder($this->root, $this->view, $path);
	}

	/**
	 * @param string $path path relative to the folder
	 * @return string
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function getFullPath($path) {
		if (!$this->isValidPath($path)) {
			throw new NotPermittedException('Invalid path');
		}
		return $this->path . $this->normalizePath($path);
	}

	/**
	 * @param string $path
	 * @return string|null
	 */
	public function getRelativePath($path) {
		if ($this->path === '' or $this->path === '/') {
			return $this->normalizePath($path);
		}
		if ($path === $this->path) {
			return '/';
		} elseif (strpos($path, $this->path . '/') !== 0) {
			return null;
		} else {
			$path = substr($path, strlen($this->path));
			return $this->normalizePath($path);
		}
	}

	/**
	 * check if a node is a (grand-)child of the folder
	 *
	 * @param \OC\Files\Node\Node $node
	 * @return bool
	 */
	public function isSubNode($node) {
		return strpos($node->getPath(), $this->path . '/') === 0;
	}

	/**
	 * get the content of this directory
	 *
	 * @return Node[]
	 * @throws \OCP\Files\NotFoundException
	 */
	public function getDirectoryListing() {
		$folderContent = $this->view->getDirectoryContent($this->path);

		return array_map(function (FileInfo $info) {
			if ($info->getMimetype() === 'httpd/unix-directory') {
				return new Folder($this->root, $this->view, $info->getPath(), $info);
			} else {
				return new File($this->root, $this->view, $info->getPath(), $info);
			}
		}, $folderContent);
	}

	/**
	 * @param string $path
	 * @param FileInfo $info
	 * @return File|Folder
	 */
	protected function createNode($path, FileInfo $info = null) {
		if (is_null($info)) {
			$isDir = $this->view->is_dir($path);
		} else {
			$isDir = $info->getType() === FileInfo::TYPE_FOLDER;
		}
		if ($isDir) {
			return new Folder($this->root, $this->view, $path, $info);
		} else {
			return new File($this->root, $this->view, $path, $info);
		}
	}

	/**
	 * Get the node at $path
	 *
	 * @param string $path
	 * @return \OC\Files\Node\Node
	 * @throws \OCP\Files\NotFoundException
	 */
	public function get($path) {
		return $this->root->get($this->getFullPath($path));
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	public function nodeExists($path) {
		try {
			$this->get($path);
			return true;
		} catch (NotFoundException $e) {
			return false;
		}
	}

	/**
	 * @param string $path
	 * @return \OC\Files\Node\Folder
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function newFolder($path) {
		if ($this->checkPermissions(\OCP\Constants::PERMISSION_CREATE)) {
			$fullPath = $this->getFullPath($path);
			$nonExisting = new NonExistingFolder($this->root, $this->view, $fullPath);
			$this->sendHooks(['preWrite', 'preCreate'], [$nonExisting]);
			if (!$this->view->mkdir($fullPath)) {
				throw new NotPermittedException('Could not create folder');
			}
			$node = new Folder($this->root, $this->view, $fullPath);
			$this->sendHooks(['postWrite', 'postCreate'], [$node]);
			return $node;
		} else {
			throw new NotPermittedException('No create permission for folder');
		}
	}

	/**
	 * @param string $path
	 * @param string | resource | null $content
	 * @return \OC\Files\Node\File
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function newFile($path, $content = null) {
		if (empty($path)) {
			throw new NotPermittedException('Could not create as provided path is empty');
		}
		if ($this->checkPermissions(\OCP\Constants::PERMISSION_CREATE)) {
			$fullPath = $this->getFullPath($path);
			$nonExisting = new NonExistingFile($this->root, $this->view, $fullPath);
			$this->sendHooks(['preWrite', 'preCreate'], [$nonExisting]);
			if ($content !== null) {
				$result = $this->view->file_put_contents($fullPath, $content);
			} else {
				$result = $this->view->touch($fullPath);
			}
			if ($result === false) {
				throw new NotPermittedException('Could not create path');
			}
			$node = new File($this->root, $this->view, $fullPath);
			$this->sendHooks(['postWrite', 'postCreate'], [$node]);
			return $node;
		}
		throw new NotPermittedException('No create permission for path');
	}

	private function queryFromOperator(ISearchOperator $operator, string $uid = null): ISearchQuery {
		if ($uid === null) {
			$user = null;
		} else {
			/** @var IUserManager $userManager */
			$userManager = \OC::$server->query(IUserManager::class);
			$user = $userManager->get($uid);
		}
		return new SearchQuery($operator, 0, 0, [], $user);
	}

	/**
	 * search for files with the name matching $query
	 *
	 * @param string|ISearchQuery $query
	 * @return \OC\Files\Node\Node[]
	 */
	public function search($query) {
		if (is_string($query)) {
			$query = $this->queryFromOperator(new SearchComparison(ISearchComparison::COMPARE_LIKE, 'name', '%' . $query . '%'));
		}

		// search is handled by a single query covering all caches that this folder contains
		// this is done by collect

		$limitToHome = $query->limitToHome();
		if ($limitToHome && count(explode('/', $this->path)) !== 3) {
			throw new \InvalidArgumentException('searching by owner is only allows on the users home folder');
		}

		$rootLength = strlen($this->path);
		$mount = $this->root->getMount($this->path);
		$storage = $mount->getStorage();
		$internalPath = $mount->getInternalPath($this->path);

		// collect all caches for this folder, indexed by their mountpoint relative to this folder
		// and save the mount which is needed later to construct the FileInfo objects

		if ($internalPath !== '') {
			// a temporary CacheJail is used to handle filtering down the results to within this folder
			$caches = ['' => new CacheJail($storage->getCache(''), $internalPath)];
		} else {
			$caches = ['' => $storage->getCache('')];
		}
		$mountByMountPoint = ['' => $mount];

		if (!$limitToHome) {
			$mounts = $this->root->getMountsIn($this->path);
			foreach ($mounts as $mount) {
				$storage = $mount->getStorage();
				if ($storage) {
					$relativeMountPoint = ltrim(substr($mount->getMountPoint(), $rootLength), '/');
					$caches[$relativeMountPoint] = $storage->getCache('');
					$mountByMountPoint[$relativeMountPoint] = $mount;
				}
			}
		}

		/** @var QuerySearchHelper $searchHelper */
		$searchHelper = \OC::$server->get(QuerySearchHelper::class);
		$resultsPerCache = $searchHelper->searchInCaches($query, $caches);

		// loop trough all results per-cache, constructing the FileInfo object from the CacheEntry and merge them all
		$files = array_merge(...array_map(function (array $results, $relativeMountPoint) use ($mountByMountPoint) {
			$mount = $mountByMountPoint[$relativeMountPoint];
			return array_map(function (ICacheEntry $result) use ($relativeMountPoint, $mount) {
				return $this->cacheEntryToFileInfo($mount, $relativeMountPoint, $result);
			}, $results);
		}, array_values($resultsPerCache), array_keys($resultsPerCache)));

		// don't include this folder in the results
		$files = array_filter($files, function (FileInfo $file) {
			return $file->getPath() !== $this->getPath();
		});

		// since results were returned per-cache, they are no longer fully sorted
		$order = $query->getOrder();
		if ($order) {
			usort($files, function (FileInfo $a, FileInfo $b) use ($order) {
				foreach ($order as $orderField) {
					$cmp = $orderField->sortFileInfo($a, $b);
					if ($cmp !== 0) {
						return $cmp;
					}
				}
				return 0;
			});
		}

		return array_map(function (FileInfo $file) {
			return $this->createNode($file->getPath(), $file);
		}, $files);
	}

	private function cacheEntryToFileInfo(IMountPoint $mount, string $appendRoot, ICacheEntry $cacheEntry): FileInfo {
		$cacheEntry['internalPath'] = $cacheEntry['path'];
		$cacheEntry['path'] = $appendRoot . $cacheEntry->getPath();
		$subPath = $cacheEntry['path'] !== '' ? '/' . $cacheEntry['path'] : '';
		return new \OC\Files\FileInfo($this->path . $subPath, $mount->getStorage(), $cacheEntry['internalPath'], $cacheEntry, $mount);
	}

	/**
	 * search for files by mimetype
	 *
	 * @param string $mimetype
	 * @return Node[]
	 */
	public function searchByMime($mimetype) {
		if (strpos($mimetype, '/') === false) {
			$query = $this->queryFromOperator(new SearchComparison(ISearchComparison::COMPARE_LIKE, 'mimetype', $mimetype . '/%'));
		} else {
			$query = $this->queryFromOperator(new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', $mimetype));
		}
		return $this->search($query);
	}

	/**
	 * search for files by tag
	 *
	 * @param string|int $tag name or tag id
	 * @param string $userId owner of the tags
	 * @return Node[]
	 */
	public function searchByTag($tag, $userId) {
		$query = $this->queryFromOperator(new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'tagname', $tag), $userId);
		return $this->search($query);
	}

	/**
	 * @param int $id
	 * @return \OC\Files\Node\Node[]
	 */
	public function getById($id) {
		$mountCache = $this->root->getUserMountCache();
		if (strpos($this->getPath(), '/', 1) > 0) {
			[, $user] = explode('/', $this->getPath());
		} else {
			$user = null;
		}
		$mountsContainingFile = $mountCache->getMountsForFileId((int)$id, $user);

		// when a user has access trough the same storage trough multiple paths
		// (such as an external storage that is both mounted for a user and shared to the user)
		// the mount cache will only hold a single entry for the storage
		// this can lead to issues as the different ways the user has access to a storage can have different permissions
		//
		// so instead of using the cached entries directly, we instead filter the current mounts by the rootid of the cache entry

		$mountRootIds = array_map(function ($mount) {
			return $mount->getRootId();
		}, $mountsContainingFile);
		$mountRootPaths = array_map(function ($mount) {
			return $mount->getRootInternalPath();
		}, $mountsContainingFile);
		$mountRoots = array_combine($mountRootIds, $mountRootPaths);

		$mounts = $this->root->getMountsIn($this->path);
		$mounts[] = $this->root->getMount($this->path);

		$mountsContainingFile = array_filter($mounts, function ($mount) use ($mountRoots) {
			return isset($mountRoots[$mount->getStorageRootId()]);
		});

		if (count($mountsContainingFile) === 0) {
			if ($user === $this->getAppDataDirectoryName()) {
				return $this->getByIdInRootMount((int)$id);
			}
			return [];
		}

		$nodes = array_map(function (IMountPoint $mount) use ($id, $mountRoots) {
			$rootInternalPath = $mountRoots[$mount->getStorageRootId()];
			$cacheEntry = $mount->getStorage()->getCache()->get((int)$id);
			if (!$cacheEntry) {
				return null;
			}

			// cache jails will hide the "true" internal path
			$internalPath = ltrim($rootInternalPath . '/' . $cacheEntry->getPath(), '/');
			$pathRelativeToMount = substr($internalPath, strlen($rootInternalPath));
			$pathRelativeToMount = ltrim($pathRelativeToMount, '/');
			$absolutePath = rtrim($mount->getMountPoint() . $pathRelativeToMount, '/');
			return $this->root->createNode($absolutePath, new \OC\Files\FileInfo(
				$absolutePath, $mount->getStorage(), $cacheEntry->getPath(), $cacheEntry, $mount,
				\OC::$server->getUserManager()->get($mount->getStorage()->getOwner($pathRelativeToMount))
			));
		}, $mountsContainingFile);

		$nodes = array_filter($nodes);

		$folders = array_filter($nodes, function (Node $node) {
			return $this->getRelativePath($node->getPath());
		});
		usort($folders, function ($a, $b) {
			return $b->getPath() <=> $a->getPath();
		});
		return $folders;
	}

	protected function getAppDataDirectoryName(): string {
		$instanceId = \OC::$server->getConfig()->getSystemValueString('instanceid');
		return 'appdata_' . $instanceId;
	}

	/**
	 * In case the path we are currently in is inside the appdata_* folder,
	 * the original getById method does not work, because it can only look inside
	 * the user's mount points. But the user has no mount point for the root storage.
	 *
	 * So in that case we directly check the mount of the root if it contains
	 * the id. If it does we check if the path is inside the path we are working
	 * in.
	 *
	 * @param int $id
	 * @return array
	 */
	protected function getByIdInRootMount(int $id): array {
		$mount = $this->root->getMount('');
		$cacheEntry = $mount->getStorage()->getCache($this->path)->get($id);
		if (!$cacheEntry) {
			return [];
		}

		$absolutePath = '/' . ltrim($cacheEntry->getPath(), '/');
		$currentPath = rtrim($this->path, '/') . '/';

		if (strpos($absolutePath, $currentPath) !== 0) {
			return [];
		}

		return [$this->root->createNode(
			$absolutePath, new \OC\Files\FileInfo(
			$absolutePath,
			$mount->getStorage(),
			$cacheEntry->getPath(),
			$cacheEntry,
			$mount
		))];
	}

	public function getFreeSpace() {
		return $this->view->free_space($this->path);
	}

	public function delete() {
		if ($this->checkPermissions(\OCP\Constants::PERMISSION_DELETE)) {
			$this->sendHooks(['preDelete']);
			$fileInfo = $this->getFileInfo();
			$this->view->rmdir($this->path);
			$nonExisting = new NonExistingFolder($this->root, $this->view, $this->path, $fileInfo);
			$this->sendHooks(['postDelete'], [$nonExisting]);
			$this->exists = false;
		} else {
			throw new NotPermittedException('No delete permission for path');
		}
	}

	/**
	 * Add a suffix to the name in case the file exists
	 *
	 * @param string $name
	 * @return string
	 * @throws NotPermittedException
	 */
	public function getNonExistingName($name) {
		$uniqueName = \OC_Helper::buildNotExistingFileNameForView($this->getPath(), $name, $this->view);
		return trim($this->getRelativePath($uniqueName), '/');
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @return \OCP\Files\Node[]
	 */
	public function getRecent($limit, $offset = 0) {
		$query = new SearchQuery(
			new SearchBinaryOperator(
				// filter out non empty folders
				ISearchBinaryOperator::OPERATOR_OR,
				[
					new SearchBinaryOperator(
						ISearchBinaryOperator::OPERATOR_NOT,
						[
							new SearchComparison(
								ISearchComparison::COMPARE_EQUAL,
								'mimetype',
								FileInfo::MIMETYPE_FOLDER
							),
						]
					),
					new SearchComparison(
						ISearchComparison::COMPARE_EQUAL,
						'size',
						0
					),
				]
			),
			$limit,
			$offset,
			[
				new SearchOrder(
					ISearchOrder::DIRECTION_DESCENDING,
					'mtime'
				),
			]
		);
		return $this->search($query);
	}
}
