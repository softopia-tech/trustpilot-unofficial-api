
# Updated on 05-07-2024 
Just checekd this randomly and It still works.

# Unofficial Trustpilot Api For PHP

Unofficial Trustpilot Api to fetch reviews and trust score.

## Installation

You can install the package via composer:

```bash
composer require softopia/trustpilot-unofficial-api
```

## Usage
``` php
//use namespace
use Softopia\TrustApi\TrustApi;

//create an instance
$trust = new TrustApi('example.com');

//get average rating
$trust->getRating();

//get reviews count
$trust->getReviewsCount();

//get All reviews (max 600 reviews or you can say 30 pages)
$trust->getAllReviews($wantJson = true); //false if you want array back
```
## Response Format
```json
[
  {
    "reviewId": "xxxxxxxxxxx",
    "reviewUrl": "https://www.trustpilot.com/reviews/xxxxxxxxxxxx",
    "rating": 5,
    "reviewTitle": "Nice work",
    "reviewBody": "Nice work!!!!",
    "customer": {
      "id": "xxcxcxcxc",
      "name": "Nice name",
      "image": "https://user-images.trustpilot.com/xxxxxxxxx/73x73.png"
    }
  }
]
```

### Testing
``` bash
//TODO
```

## Credits

- [Ravi](https://github.com/softopia-tech)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## PHP Package Boilerplate

This package was generated using the [PHP Package Boilerplate](https://laravelpackageboilerplate.com).
