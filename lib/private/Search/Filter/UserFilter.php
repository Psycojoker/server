<?php

declare(strict_types=1);

/**
 * @copyright 2023 Benjamin Gaussorgues <benjamin.gaussorgues@nextcloud.com>
 *
 * @author Benjamin Gaussorgues <benjamin.gaussorgues@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Search\Filter;

use OC\Search\InvalidFilter;
use OC\Search\SingleFilter;
use OCP\IUser;
use OCP\IUserManager;

class UserFilter extends SingleFilter {
	private IUser $user;

	public function __construct(
		string $name,
		string $value,
		private IUserManager $userManager,
	) {
		parent::__construct($name, $value);
	}

	protected function set(string $value): void {
		$this->user = $this->userManager->get($value);
		if ($this->user === null) {
			throw new InvalidFilter($value);
		}
	}

	public function get(): IUser {
		return $this->user;
	}

	public static function type(): string {
		return 'user';
	}
}
