# ChronoGG-clicker-PHP
Simple PHP script to automatically click Chrono.gg's coin for any amount of accounts. A RSS is generated with results so you can get daily feedback of each execution.

This script uses [Requests for PHP](https://github.com/rmccue/Requests) and is based on [AutoChronoGG](https://github.com/joaopsys/AutoChronoGG).

### How to obtain your Authorization token
In order to obtain your Authorization Token, you must follow these steps:
* Head to https://chrono.gg/ and login
* Right-click anywhere -> Inspect Element -> Go to the network tab -> Filter by XHR
* Keep the network tab open and refresh the page
* Some requests will appear, **click "account"** and copy the **Authorization** header under "Request Headers". It should start with "JWT", followed by a train of characters. **Make sure you copy all of it!**

### Configuration
1. Edit [accounts.json](/script/chronogg-clicker/accounts.json) and add any amount of accounts to it (Delete the example ones):
```
[
  {
    "account": "Any identifier, like email, username or whatever you want",
    "token": "JWT token"
  },
  {
    "account": "Identifier2",
    "token": "JWT token"
  }
]
```
2. Run [runAll.php](/script/chronogg-clicker/runAll.php)
3. Retrieve [rss.xml](/script/chronogg-clicker/rss.xml)

If you have any issues check your accounts.json file format on: https://jsonformatter.curiousconcept.com/

### Optional configuration
* A file called [results.json](/script/chronogg-clicker/results.json) is generated so you can see raw data if there was any issue generating the RSS
* Change line `date_default_timezone_set('Europe/Madrid');` inside [runAll.php](/script/chronogg-clicker/runAll.php) to your preferred timezone
* Uncomment `addLogMessage` inside [runAll.php](/script/chronogg-clicker/runAll.php) for extra logging
* A cronjob is highly recommended to execute this script automatically