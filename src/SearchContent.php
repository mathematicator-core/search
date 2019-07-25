<?php

namespace Mathematicator\Search;

use App\Model\Video\VideoManager;
use Nette\Application\LinkGenerator;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Utils\Strings;
use ShopUp\FunctionUtils\Search;

/**
 * @deprecated
 */
class SearchContent
{

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var VideoManager
	 */
	private $videoManager;

	/**
	 * @var LinkGenerator
	 */
	private $linkGenerator;

	/**
	 * @var string
	 */
	private $query;

	/**
	 * @param IStorage $IStorage
	 * @param VideoManager $videoManager
	 * @param LinkGenerator $linkGenerator
	 */
	public function __construct(IStorage $IStorage, VideoManager $videoManager, LinkGenerator $linkGenerator)
	{
		$this->cache = new Cache($IStorage, 'search-content');
		$this->videoManager = $videoManager;
		$this->linkGenerator = $linkGenerator;
	}

	/**
	 * @param string $query
	 */
	public function setQuery(string $query)
	{
		$this->query = Strings::normalize($query);
	}

	/**
	 * @param int $limit
	 * @param int $descriptionLength
	 * @return VideoResult[]
	 */
	public function searchVideos($limit = 5, $descriptionLength = 300)
	{
		$videos = [];

		$results = $this->videoManager->searchVideos($this->query, $limit);

		foreach ($results as $_result) {
			$video = new VideoResult();
			$video->setName(Search::highlightFoundWords($_result->getName(), $this->query, '<b class="highlight">\\0</b>'));
			$video->setLink($this->linkGenerator->link('Video:play', [
				'slug' => $_result->getSlug(),
			]));
			$video->setThumbnail(
				$_result->getThumbnail()
			);
			$video->setDescription(Search::highlightFoundWords($this->smartTruncate($this->query, $_result->getDescription(), $descriptionLength), $this->query, '<b class="highlight">\\0</b>'));

			$videos[] = $video;
		}

		return $this->orderVideos($videos, $limit);
	}

	/**
	 * @param VideoResult[] $results
	 * @param int $limit
	 * @return VideoResult[]
	 */
	private function orderVideos(array $results, int $limit)
	{
		$videos = [];

		foreach ($results as $result) {
			$title = 1;
			$content = 1;

//			$title += abs(Strings::length($result->name) - 100) / 4;
//			$title += $title > 10 ? 10 : $title;
			$title += \count(explode('<b class="', $result->name)) * 8;

//			$content += Strings::length($result->description) < 6 ? 0 : 5;
			$content += \count(explode('<b class="', $result->description)) * 2;

			$result->score = round($title + $content, 2);

			$videos[] = $result;
		}

		usort($videos, function (VideoResult $resultA, VideoResult $resultB) {
			return $resultA->score < $resultB->score ? 1 : -1;
		});

		$limitedReturn = [];

		foreach ($videos as $item) {
			$limitedReturn[] = $item;
			$limit--;

			if ($limit === 0) {
				break;
			}
		}

		return $limitedReturn;
	}

	/**
	 * Ořízne řetězec podle hledaných klíčových slov tak, aby se vrátila ta část,
	 * co má největší množství výskytů hledaných slov. Délku výstupu lze ovlivnit.
	 *
	 * @param string $query
	 * @param string $data
	 * @param int $len [60]
	 * @return mixed|string
	 */
	private function smartTruncate($query, $data, $len = 60)
	{
		if (strlen($data) < $len + 5) {
			return $data;
		}
		if (strlen($query) > 25) {
			return Strings::truncate($data, $len);
		}
		$words = explode(' ', $query);

		$candidates = [];
		$start = 0;

		while (true) {
			$part = substr($data, $start, $len);
			$contains = false;
			$containsCount = 0;
			foreach ($words as $word) {
				$word = trim($word);
				if ($word && stripos($part, $word) !== false) {
					$contains = true;
					$containsCount++;
				}
			}

			if ($contains) {
				$candidates[$containsCount] = $part;
			}

			if (isset($data[$start + $len + 1])) {
				$start++;
			} else {
				break;
			}
		}

		$finalString = Strings::truncate($data, $len);
		$finalStringCount = -1;

		foreach ($candidates as $key => $value) {
			if ($key > $finalStringCount) {
				$finalStringCount = $key;
				$finalString = $value;
			}
		}

		return \strlen($data) > \strlen($finalString) ? '... ' . $finalString . ' ...' : $finalString;
	}

}
