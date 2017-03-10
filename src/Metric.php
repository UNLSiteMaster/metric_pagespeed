<?php
namespace SiteMaster\Plugins\Metric_pagespeed;

use SiteMaster\Core\Auditor\Logger\Metrics;
use SiteMaster\Core\Auditor\MetricInterface;
use SiteMaster\Core\Registry\Site;
use SiteMaster\Core\Auditor\Scan;
use SiteMaster\Core\Auditor\Site\Page;

class Metric extends MetricInterface
{
    /**
     * @param string $plugin_name
     * @param array $options
     */
    public function __construct($plugin_name, array $options = array())
    {
        $options = array_replace_recursive(
            array(
                'passing_grade' => 70,
                'strategy'      => 'mobile',
                'api_params'    => array(),
            ), $options);

        parent::__construct($plugin_name, $options);
    }
    
    /**
     * Get the human readable name of this metric
     *
     * @return string The human readable name of the metric
     */
    public function getName()
    {
        return 'Google PageSpeed Insights';
    }

    /**
     * Get the Machine name of this metric
     *
     * This is what defines this metric in the database
     *
     * @return string The unique string name of this metric
     */
    public function getMachineName()
    {
        return 'pagespeed';
    }

    /**
     * Determine if this metric should be graded as pass-fail
     *
     * @return bool True if pass-fail, False if normally graded
     */
    public function isPassFail()
    {
        //This metric is always pass/fail
        return true;
    }

    /**
     * Scan a given URI and apply all marks to it.
     *
     * All that this
     *
     * @param string $uri The uri to scan
     * @param \DOMXPath $xpath The xpath of the uri
     * @param int $depth The current depth of the scan
     * @param \SiteMaster\Core\Auditor\Site\Page $page The current page to scan
     * @param \SiteMaster\Core\Auditor\Logger\Metrics $context The logger class which calls this method, you can access the spider, page, and scan from this
     * @throws \Exception
     * @return bool True if there was a successful scan, false if not.  If false, the metric will be graded as incomplete
     */
    public function scan($uri, \DOMXPath $xpath, $depth, Page $page, Metrics $context)
    {
        $response = $this->getPageSpeed($uri);
        
        $score = $response['ruleGroups']['SPEED']['score'];
        
        if ($score >= $this->options['passing_grade']) {
            //Not perfect, but passing
            $mark = $this->getMark('passing_google_page_speed_score', 'Passed: Google PageSpeed Score', 0);
        } else {
            $mark = $this->getMark('failing_google_page_speed_score', 'Google PageSpeed Score', 100-$score, '', 'Improve the Google PageSpeed score to at least ' . $this->options['passing_grade']);
        }
        
        $page->addMark($mark, array(
            'value_found' => $score
        ));

        foreach ($response['formattedResults']['ruleResults'] as $rule_name=>$rule) {
            if (!in_array('SPEED', $rule['groups'])) {
                //skip all non-speed rules (non exist at the time of writing)
                continue;
            }

            $point_deduction = 0;
            $machine_name = $rule_name;
            if ($rule['ruleImpact'] == 0) {
                //list these as passing
                $point_deduction = -1;
                $machine_name = $machine_name . '_passing';
            }
            
            $help = '';
            if (isset($rule['urlBlocks'])) {
                foreach ($rule['urlBlocks'] as $block) {
                    $help .= $this->parseFormat($block['header']) . PHP_EOL . PHP_EOL;
                    if (isset($block['urls'])) {
                        foreach ($block['urls'] as $url) {
                            $help .= '  * ' . $this->parseFormat($url['result']) . PHP_EOL;
                        }
                    }
                }
            }
            
            $summary = '';
            if (isset($rule['summary'])) {
                $this->parseFormat($rule['summary']);
            }
            
            $mark = $this->getMark($machine_name, $rule['localizedRuleName'], $point_deduction, $summary);

            $page->addMark($mark, array(
                'value_found' => $rule['ruleImpact'],
                'help_text' => $help,
            ));
        }

        return true;
    }

    /**
     * Set the options array for this metric.
     *
     * This is for testing purposes
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param $url
     * @return array
     */
    public function getPageSpeed($url)
    {
        $api = new Api();
        return $api->getRawResults($url, 'en_us', $this->options['strategy'], $this->options['api_params']);
    }

    /**
     * Format a value_found for presentation
     *
     * @param $machine_name
     * @param $value_found
     * @return mixed
     */
    function formatValueFound($machine_name, $value_found)
    {
        //Don't format it if nothing was logged
        if (empty($value_found)) {
            return $value_found;
        }
        
        if ($machine_name == 'failing_google_page_speed_score') {
            return $value_found;
        }
        
        return 'impact: ' . $value_found;
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