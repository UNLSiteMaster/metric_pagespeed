<?php
namespace SiteMaster\Plugins\Metric_axe;

use SiteMaster\Plugins\Metric_pagespeed\Api;

class ParserTest extends \PHPUnit_Framework_TestCase {
    
    public function testParseFormat()
    {
        $data = json_decode(file_get_contents(__DIR__.'/data/sample.json'), true);
        
        $parser = new Api();
        $rules = $data['formattedResults']['ruleResults'];
        
        $this->assertEquals(
            'Your page has no redirects. Learn more about [avoiding landing page redirects](https://developers.google.com/speed/docs/insights/AvoidRedirects).',
            $parser->parseFormat($rules['AvoidLandingPageRedirects']['summary']),
            'HYPERLINK should be correctly formatted');

        $this->assertEquals(
            'Compressing [https://nebraska.edu/committocomplete/_ui/libs/jquery/js/jquery-1.10.2.min.js](https://nebraska.edu/committocomplete/_ui/libs/jquery/js/jquery-1.10.2.min.js) could save 58.9KiB (64% reduction).',
            $parser->parseFormat($rules['EnableGzipCompression']['urlBlocks'][0]['urls'][0]['result']),
            'Other data types should be correctly formatted');
    }
}
