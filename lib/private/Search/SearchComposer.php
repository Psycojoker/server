<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
namespace OC\Search;

use InvalidArgumentException;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\Search\Filter\StringFilter;
use OCP\IUser;
use OCP\Search\FilterCollection;
use OCP\Search\IProvider;
use OCP\Search\IProviderV2;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use function array_map;

/**
 * Queries individual \OCP\Search\IProvider implementations and composes a
 * unified search result for the user's search term
 *
 * The search process is generally split into two steps
 *
 *   1. Get a list of provider (`getProviders`)
 *   2. Get search results of each provider (`search`)
 *
 * The reasoning behind this is that the runtime complexity of a combined search
 * result would be O(n) and linearly grow with each provider added. This comes
 * from the nature of php where we can't concurrently fetch the search results.
 * So we offload the concurrency the client application (e.g. JavaScript in the
 * browser) and let it first get the list of providers to then fetch all results
 * concurrently. The client is free to decide whether all concurrent search
 * results are awaited or shown as they come in.
 *
 * @see IProvider::search() for the arguments of the individual search requests
 */
class SearchComposer {
	/**
	 * @var IProvider[]
	 */
	private array $providers = [];

	private array $filters = [];
	private array $handlers = [];

	public function __construct(
		private Coordinator $bootstrapCoordinator,
		private ContainerInterface $container,
		private LoggerInterface $logger
	) {
	}

	/**
	 * Load all providers dynamically that were registered through `registerProvider`
	 *
	 * If a provider can't be loaded we log it but the operation continues nevertheless
	 */
	private function loadLazyProviders(): void {
		$context = $this->bootstrapCoordinator->getRegistrationContext();
		if ($context === null) {
			// Too early, nothing registered yet
			return;
		}

		$registrations = $context->getSearchProviders();
		foreach ($registrations as $registration) {
			try {
				/** @var IProvider $provider */
				$provider = $this->container->get($registration->getService());
				$providerId = $provider->getId();
				$this->providers[$providerId] = $provider;
				$this->handlers[$providerId] = [$providerId];
			} catch (ContainerExceptionInterface $e) {
				// Log an continue. We can be fault tolerant here.
				$this->logger->error('Could not load search provider dynamically: ' . $e->getMessage(), [
					'exception' => $e,
					'app' => $registration->getAppId(),
				]);
			}
		}

		$this->loadSupportedFilters();
		$this->loadAlternateIds();
	}

	private function loadSupportedFilters(): void {
		foreach($this->providers as $providerId => $provider) {
			if (!$provider instanceof IProviderV2) {
				// TODO Send deprecated warning?
				$this->registerFilter('term', StringFilter::class, $provider->getId());
				continue;
			}

			foreach ($provider->getSupportedFilters() as $name => $filter) {
				$this->registerFilter($name, $filter, $provider->getId());

			}
		}
	}

	private function registerFilter(string $name, string $filter, string $providerId): void {
		if (!class_exists($filter)) {
			// FIXME Fail nicely
			var_dump($filter);
			exit;
		}
		if (!isset($this->filters[$name])) {
			$this->filters[$name] = [$providerId => $filter];
			return;
		}

		$this->filters[$name][$providerId] = $filter;
	}

	private function loadAlternateIds(): void {
		foreach($this->providers as $providerId => $provider) {
			if (!$provider instanceof IProviderV2) {
				// TODO Send deprecated warning?
				continue;
			}

			foreach($provider->getAlternateIds() as $alternateId) {
				$this->handlers[$alternateId][] = $providerId;
			}
		}
	}

	/**
	 * Get a list of all provider IDs & Names for the consecutive calls to `search`
	 * Sort the list by the order property
	 *
	 * @param string $route the route the user is currently at
	 * @param array $routeParameters the parameters of the route the user is currently at
	 *
	 * @return array
	 */
	public function getProviders(string $route, array $routeParameters): array {
		$this->loadLazyProviders();

		$providers = array_values(
			array_map(function (IProvider $provider) use ($route, $routeParameters) {
				$triggers = [$provider->getId()];
				if ($provider instanceof IProviderV2) {
					$triggers = array_merge($triggers, $provider->getAlternateIds());
					$filters = $provider->getSupportedFilters();
				} else {
					$filters = ['term' => StringFilter::class];
				}
				return [
					'id' => $provider->getId(),
					'name' => $provider->getName(),
					'order' => $provider->getOrder($route, $routeParameters),
					'triggers' => $triggers,
					'filters' => $this->getFiltersAsArray($filters),
				];
			}, $this->providers)
		);

		usort($providers, function ($provider1, $provider2) {
			return $provider1['order'] <=> $provider2['order'];
		});

		/**
		 * Return an array with the IDs, but strip the associative keys
		 */
		return $providers;
	}

	/**
	 * @param $filters IFilter[]
	 * @return array<string, array{ type: string, multiple: bool }>
	 */
	private function getFiltersAsArray(array $filters): array {
		$filterList = [];
		foreach($filters as $name => $filter) {
			$filterList[$name] = [
				'type' => $filter::type(),
				'multiple' => $filter::multiple(),
			];
		}

		return $filterList;
	}

	public function buildFilterList(string $providerId, array $filters): FilterCollection {
		$this->loadLazyProviders();

		$list = [];
		foreach($filters as $name => $value) {
			if (!isset($this->filters[$name])) {
				// Non existing filter
				continue;
			}
			if (!isset($this->filters[$name][$providerId])) {
				// Current filter isn't supported by app
				throw new InvalidFilter($value);
			}
			$class = $this->filters[$name][$providerId];
			$list[$name] = new $class($value);

		}

		return new FilterCollection(... $list);
	}

	/**
	 * Query an individual search provider for results
	 *
	 * @param IUser $user
	 * @param string $providerId one of the IDs received by `getProviders`
	 * @param ISearchQuery $query
	 *
	 * @return SearchResult
	 * @throws InvalidArgumentException when the $providerId does not correspond to a registered provider
	 */
	public function search(
		IUser $user,
		string $providerId,
		ISearchQuery $query,
	): SearchResult {
		// TODO Only load specified provider?
		$this->loadLazyProviders();

		$provider = $this->providers[$providerId] ?? null;
		if ($provider === null) {
			throw new InvalidArgumentException("Provider $providerId is unknown");
		}

		return $provider->search($user, $query);
	}
}
