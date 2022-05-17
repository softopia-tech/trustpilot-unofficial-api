<?php

namespace Softopia\TrustApi;

use Exception;
use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;

class TrustApi
{
    private $client;
    private $totalpages    = 1;
    private $totalreviews  = 0;
    private $averagerating = 0;
    private $mainpage      = null;
    const REVIEWS_PER_PAGE = 20;
    const REVIEWS_URL      = 'https://www.trustpilot.com/review/';
    const SINGLE_REVIEW_URL      = 'https://www.trustpilot.com/reviews/';
    public $url;

    /**
     * @param [string] $url [URL of the destination website]
     */
    public function __construct($url)
    {
        $url          = self::REVIEWS_URL . $url;
        $this->url    = $url;
        $this->client = new Client(HttpClient::create(['timeout' => 120]));
        $this->checkUrl();
    }
    /**
     * [getPageContent get current page reviews]
     * @param  [sting] $url
     * @return [Object] [Symfony\Component\DomCrawler\Crawler]
     */
    private function getPageContent($url)
    {
        try {
            return $this->client->request('GET', $url);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 1);
        }

    }

    public function loadFirstPage()
    {
        $this->mainpage      = $this->getPageContent($this->url);
        $this->totalreviews  = str_replace(',', '', $this->mainpage->filter('[data-reviews-count-typography]')->text());
        $this->averagerating = $this->mainpage->filter('[data-rating-typography]')->text();
        $this->totalpages    = ceil($this->totalreviews / self::REVIEWS_PER_PAGE);
        $this->totalpages    = $this->totalpages > 30 ? 30 : $this->totalpages; //max 30 pages
    }
    /**
     * [getRating get Average rating of the site]
     * @return [float]
     */
    public function getRating(): float
    {
        if (is_null($this->mainpage)) {
            $this->loadFirstPage();
        }
        return (float) $this->averagerating;
    }

    public function getTotalPages(): int
    {
        if (is_null($this->mainpage)) {
            $this->loadFirstPage();
        }
        return (int) $this->totalpages;
    }

    public function getReviewsCount(): int
    {
        if (is_null($this->mainpage)) {
            $this->loadFirstPage();
        }
        return (int) $this->totalreviews;
    }

    protected function scrapeReviews()
    {
        if (is_null($this->mainpage)) {
            $this->loadFirstPage();
        }
        for ($i = 1; $i <= $this->getTotalPages(); $i++) {
            $data  = $this->getPageContent($this->url . '?page=' . $i);
            $nodes = $data->filter('#__NEXT_DATA__');
            yield $nodes->each(function ($node, $i) {
                return json_decode($node->filter('script')->text(), true);
            });
        }
    }
    /**
     * [checkUrl check if the passed url is correct and exists on Trustpiolet]
     * @return [boolean]
     */
    private function checkUrl()
    {
        if (!$this->url_exist($this->url)) {
            throw new Exception("Website not available on Trustpilot, Please check by opening following url in new tab [" . $this->url . "]", 1);
        }
        $pattern = "/\b(trustpilot.com\/review\/)\b/";
        if (!preg_match($pattern, $this->url)) {
            throw new InvalidArgumentException('Please provide a valid URL to get reviews! like "https://www.trustpilot.com/review/example.com"');
        }
        return true;
    }

    public function getAllReviews($json = false)
    {
        $allreviews = [];
        foreach ($this->scrapeReviews() as $reviewsAry) {
            $reviewsAry = $reviewsAry[0]['props']['pageProps']['reviews'];
            foreach ($reviewsAry as $review) {
                array_push($allreviews, $this->formatReview($review));
            }
        }
        return !$json ? $allreviews : json_encode($allreviews);
    }

    private function url_exist($url)
    {
        $client     = HttpClient::create();
        $response   = $client->request('GET', $url);
        $statusCode = $response->getStatusCode();
        if ($statusCode === 200) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * [formatReview]
     * @param  array  $review
     * @return [array] [formatted review array]
     */
    private function formatReview(array $review): array
    {
        return [
            'reviewId'    => $review['id'],
            'reviewUrl'   => self::SINGLE_REVIEW_URL.'/'.$review['id'],
            'rating'      => $review['rating'],
            'reviewTitle' => $review['title'],
            'reviewBody'  => $review['text'],
            'customer'    => [
                'id'    => $review['consumer']['id'],
                'name'  => $review['consumer']['displayName'],
                'image' => $review['consumer']['imageUrl'],
            ],

        ];
    }
}
