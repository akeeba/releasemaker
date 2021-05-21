<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Deployment;


interface ARSInterface
{
	public function __construct(array $config = []);

	public function getRelease(int $category, string $version): object;

	public function getReleaseById(int $release_id): object;

	public function addRelease(array $releaseData);

	public function editRelease(array $releaseData);

	public function getItem($release, $type, $fileOrURL);

	public function getItemById($item_id);

	public function addItem(array $itemData): string;

	public function editItem(array $itemData): string;
}