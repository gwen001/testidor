# TestIdor
PHP tool to test Insecure Direct Object Reference aka IDOR.  
A payload can be injected at multiple places, so each original values will be replaced at the same time.  
A relative payload means that the original value will be incremented and decremented.  

```
Usage: testidor.php [OPTIONS] -p <payloads> -f <request_file>

Options:
	-h	print this help
	-s	force https
	-t	set tolerance for result output

Payloads:
	The program can deal with mutiple payloads
	The payloads will replace orginal values in the request
	The payloads can be strings, numerics or relative value
	A payload is represented by a special character
	Each payloads are evaluated separately

	Payloads must be separated by a ;
	Payloads values must be separated by a ,
	Numeric values under 100 are considered as relative

	Injection points can be URL, headers, cookies
	Check example.txt as a request example
	Requests can be paste from Burp Suite

Examples:
	testidor.php -p "§=10" -f request.txt
	testidor.php -s -p "^=bob,alice,jim" -f request.txt
	testidor.php -t 10 -s -p "|=5;^=bob,alice,jim;$=123,456,789" -f request.txt
```

I don't believe in license.  
You can do want you want with this program.  
