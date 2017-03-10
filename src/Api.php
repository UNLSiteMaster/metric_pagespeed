<?php
namespace SiteMaster\Plugins\Metric_pagespeed;

use PageSpeed\Insights\Service;

class Api
{
    public function getRawResults($url, $lang, $strategy, $params)
    {
        $pageSpeed = new Service();
        return $pageSpeed->getResults($url, $lang, $strategy, $params);
    }

    /**
     * Replaces all the placeholder with it's values
     * and returns the parsed result
     *
     * @param array $data
     * @return string
     */
    public function parseFormat(array $data)
    {
        $format = $data['format'];

        //if arguments are given replace them in the format
        if (isset ($data['args'])) {
            foreach ($data['args'] as $arg) {
                $key    = $arg['key'];
                $type   = $arg['type'];
                $value  = $arg['value'];

                //hyperlink has a beginning and ending
                if ($type == 'HYPERLINK') {
                    $format = str_replace(
                        '{{BEGIN_LINK}}',
                        '[',
                        $format);

                    $format = str_replace(
                        '{{END_LINK}}',
                        "]($value)",
                        $format);
                } else if ($type == 'URL') {
                    $format = str_replace(
                        '{{'.$key.'}}',
                        "[$value]($value)",
                        $format);
                } else {
                    $format = str_replace(
                        '{{'.$key.'}}',
                        "$value",
                        $format);
                }
            }
        }

        return $format;
    }
}
