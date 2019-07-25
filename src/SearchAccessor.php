<?php

namespace Mathematicator\Search;


interface SearchAccessor
{

	/**
	 * @return Search
	 */
	public function get(): Search;

}