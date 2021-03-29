<?php

namespace Softopia\TrustApi;

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
    public $url;

    public function __construct($url)
    {
        $this->checkUrl($url);
        $this->url    = $url;
        $this->client = new Client(HttpClient::create(['timeout' => 120]));
    }
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
        $this->totalreviews  = str_replace(',','',$this->mainpage->filter('.headline__review-count')->text());
        $this->averagerating = $this->mainpage->filter('.header_trustscore')->text();
        $this->totalpages    = ceil($this->totalreviews / self::REVIEWS_PER_PAGE);
        $this->totalpages    = $this->totalpages > 30 ? 30 : $this->totalpages; //max 30 pages
    }
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
        for ($i=1; $i <= $this->getTotalPages(); $i++) {
            $data = $this->getPageContent($this->url.'?page='.$i);
            $nodes = $data->filter('.review-card');
            yield $nodes->each(function ($node, $i) {
             return json_decode($node->filter('script')->text(), true);
            });
        }
    }

    private function checkUrl($url)
    {

        $pattern = "/\b(trustpilot.com\/review\/)\b/";
        if (!preg_match($pattern, $url)) {
            throw new InvalidArgumentException('Please provide a valid URL to get reviews! like "https://www.trustpilot.com/review/example.com"');
        }
        return true;
    }

    public function getAllReviews()
    {
        $allreviews = [];
        foreach ($this->scrapeReviews() as $reviewsAry) {
            foreach ($reviewsAry as $review) {
                array_push($allreviews, $review);
            }
        }
        return $allreviews;
    }
}
