# Rate Limiter

[<img src="https://img.shields.io/badge/License-MIT-yellow.svg">](https://opensource.org/licenses/MIT)

## Table of Contents

[Description](#description)<br />[Usage](#usage)<br />[Contributing](#contributing)<br />[License](#license)<br />[Questions](#questions)<br />

## Description
The limiter uses a sliding window algorithm and writes the time of successive requests to JSON files, one for each client's IP address. To prevent these logs getting too large, the limiter culls entries logged prior the current window. A cron job can be used to completely remove files that were last updated before the start of the current window. [This example](https://github.com/paulashby/open-triviata-api/blob/main/public/culler.php) may be helpful.

In the interests of usablity, the API responses include informative headers related to the status of the rate limiter. Headers include ```X-RateLimit-Limit```, ```X-RateLimit-Remaining```, ```X-RateLimit-Reset```, and when the limit is exceeded, ```429 Too Many Requests```, ```Retry-After```. 

## Usage
Download utilities/RateLimiter/SlidingWindow.php

Include in PHP file:<br />
```include_once "../utilities/RateLimiter/SlidingWindow.php";```<br />

Instantiate the limiter:<br />
```$limiter = new SlidingWindow(100);```<br />
The first argument is the number of requests per minute.
Passing an optional second argument of ```true``` will increase the window to five minutes and limit based on requests from ALL users. When this limit is exceeded, the response will be ```503 Service Temporarily Unavailable```.

Get the request IP address:<br />
```$ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);```<br />

Apply limiting to the request IP address:<br />
```$limiter->limit($ip);```

## Contributing

If you feel you could contribute to the synchroniser in some way, simply fork the repository and submit a Pull Request. If I like it, I may include it in the codebase.
  
## License
  
Released under the [MIT](https://opensource.org/licenses/MIT) license.

## Questions

Feel free to [email me](mailto:paul@primitive.co?subject=OpenTriviataSynchroniser%20query%20from%20GitHub) with any queries.

