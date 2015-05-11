# metric_pagespeed
Google Pagespeed Insights metric for SiteMaster


## Sample configuration
You must define an api key.  See https://developers.google.com/speed/docs/insights/v1/getting_started for details on how to get access

```
'metric_pagespeed' => array(
        'weight' => 0,
        'api_params' => array(
            'key' => 'my-key-here'
        ),
    )
```