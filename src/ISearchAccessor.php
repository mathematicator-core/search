<?php

declare(strict_types=1);

namespace Mathematicator\Search;


interface ISearchAccessor
{
	/**
	 * @return Search
	 */
	public function get(): Search;
}
