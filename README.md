# trafficapi
A rest-like API for requesting traffic data from our library traffic database.

The api allows both get and post operations. Post operations that change database data require an API key (these are set by our applications developer), but read-only operations require no authentication.

## Getting data out of the database

Data can be requested by issuing get reqests to the /trafficapi/ directory.  Requests are captured by an .htaccess file using mod_rewrite to direct the request to the correct script for handling.  All responses are in JSON and data is typically sorted by date, with the most recent date first-this makes it easy to do things like get the most recent traffic data.

All requests must use ssl.  This is because I don't want API keys for post operations transmitted in the open.  A request over http will not get any response from the API.

### get urls

/trafficapi/traffic

This url will return all the traffic labels in the database, along with their ID numbers and text descriptors.  this is also the base url for posting new traffic data (see later).

/trafficapi/entries

returns all entries in chronological order.

/trafficapi/entries/entry_number

Returns a specific entry from the entries table.  

/trafficapi/entries/entry_number/traffic

Gets all the traffic data from that particular entry.

/trafficapi/entries/entry_number/traffic/space_id

Get's traffic data for space with space_id for the entry entry_number.  so basically, traffic for a particular space on a  specific date/time.

/trafficapi/entries/bydate?start=start_date&end=end_date

Request entry data for a specific date range.  Start_date and end_date are both optional, leaving one off will make the api look for the latest or earliest entry and start the query from there, respectively. Leaving both off is identical to issuing a /trafficapi/entries request.  If included, start_date and end_date must be datetime strings in the format "YYYY-MM-DD HH:MM:SS" hours are 24 hour format. 

/trafficapi/feedback

Returns all feedback int he database ordered by date, with most recent entries first.  This is also the base url for posting new feedback data (see below)

/trafficapi/feedback/bydate?start=start_date&end=end_date

Request feedback data by date range.  Works identically to requesting entries by date.

/trafficapi/spaces

Returns data on all spaces tracked by the database, including the label and ID number.

/trafficapi/spaces/space_id/traffic

Returns all traffic data for the space corresponding to space_id, ordered chronologically by date.

/trafficapi/spaces/space_id/traffic/bydate?start=start_date&end=end_date

Traffic data for a specific space by date.  see entries for how the date searching works.

### Requesting modes and Averages 

The API will calculate averages and counts of the number of times a traffic level was entered for a specific area.  Requests for that data should be directed to the following endpoint:

/trafficapi/calculate

Additional data will need to be entered as JSON in the body of a POST request to that endpoint:

**include**

An array of date ranges in the format "YYYY/MM/DD",  With the start date first and end date second.  These dates will be included in calculations.  You can specify multiple date ranges. Optional.

**exclude**
An array of date ranges in the format "YYYY/MM/DD",  With the start date first and end date second.  These dates will be excluded from calculations.  You can specify multiple date ranges. Optional.

**mode**
Can be set to "average" or "mode."  Will tell the endpoint which calculation to perform.  Note that "mode" calculations require a specific space. Required.

**spaceID**

Required for "mode" calculations.  Must be set to a spaceID from the spaces table.  See above for how to request these IDs from the API itself. Required for mode calculations.

**hoursInclude**

Can be set for multiple hour ranges.  Ranges supplied will be the only ones included in calculations.  Specify hours as a 24 hour clock (0-24). Optional.

**HoursExclude**

Can be set for multiple hour ranges.  Ranges supplied will be excluded from calculations.  Specify hours as a 24 hour clock (0-24). Optional.

**sample request**

A sample json-encoded calculation request body for the mode calculation of a space.

~~~~ 
{"mode":"mode","spaceID":"8","include":[["10\/1\/2018","10\/31\/2018"]],"exclude":[["10\/3\/2018","10\/10\/2018"]],"hoursExclude":[["11","16"]],"hoursInclude":[["8","20"]]}
~~~~ 

A sample json-encoded calculation request body for the average calculation of a space.
~~~~ 
{"mode":"average","include":[["10\/1\/2018","10\/31\/2018"]],"exclude":[["10\/3\/2018","10\/10\/2018"]],"hoursExclude":[["11","16"]],"hoursInclude":[["8","20"]]}
~~~~ 


### Post URLs

the API supports only two post operations that actually change the database.  Both require API keys and as with any other transaction, use of SSL.  The API key must be passed in a basic authentication username/password header (leave the username blank).  Each post transation requires the API key.

#### Posting feedback

Just set a post parameter named "feedBackLevel" to a legal value (1-5) and issue it to /trafficapi/feedback.  

#### Posting Traffic Data

Traffic data must be passed to the /trafficapi/traffic endpoint.  The data must be passed in JSON as the payload of the POST request.  Following is a model of what the JSON needs to look like.  Note that the first entity has to be the initials.  An improperly formed JSON block will trigger a 400 "bad request" status.

~~~~ 
{"initials":"kaf","0":{"space":"1","level":"0"},"1":{"space":"2","level":"1","comments":"this is a comment"},"2":{"space":"3","level":"2"},"3":{"space":"4","level":"3"},"4":{"space":"5","level":"4","comments":"this was strange"},"5":{"space":"6","level":"4"},"6":{"space":"7","level":"3"},"7":{"space":"8","level":"3"},"8":{"space":"9","level":"3","comments":""},"9":{"space":"10","level":"3"},"10":{"space":"11","level":"3"},"11":{"space":"12","level":"3"},"12":{"space":"13","level":"3"},"13":{"space":"14","level":"3"},"14":{"space":"15","level":"3"},"15":{"space":"16","level":"3"}}
~~~~





