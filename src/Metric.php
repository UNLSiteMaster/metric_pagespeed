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
        if (isset($this->options['pass_fail']) && $this->options['pass_fail'] == true) {
            //Simulate a pass/fail metric grade
            return true;
        }

        return false;
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

        if (100 == $score) {
            //Perfect page!!!
            return true; 
        }
        
        if ($score >= $this->options['passing_grade']) {
            //Not perfect, but passing
            $mark = $this->getMark('passing_google_page_speed_score', 'Passed: Google PageSpeed Score', 0);
        } else {
            $mark = $this->getMark('failing_google_page_speed_score', 'Google PageSpeed Score', 10, '', 'Improve the Google PageSpeed score to at least ' . $this->options['passing_grade']);
        }
        
        $page->addMark($mark, array(
            'value_found' => $score
        ));

        foreach ($response['formattedResults']['ruleResults'] as $rule_name=>$rule) {
            if (!in_array('SPEED', $rule['groups'])) {
                //skip all non-speed rules (non exist at the time of writing)
                continue;
            }

            if ($rule['ruleImpact'] == 0) {
                //Skip rules that passed.
                continue;
            }
            
            $mark = $this->getMark(md5($rule_name), $rule['localizedRuleName'], 0, '', 'View the Google PageSpeed results for this page for more information on how to fix this issue.  Use the link to the service in the metric summary.');

            $page->addMark($mark, array(
                'value_found' => $rule['ruleImpact']
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
        $pageSpeed = new \PageSpeed\Insights\Service();
        return $pageSpeed->getResults($url, 'en_us', $this->options['strategy']);
    }
}