<?php
$url = 'https://developers.google.com/speed/pagespeed/insights/';
?>

<p>
    This metric uses <a href="<?php echo $url ?>">Google PageSpeed Insights service</a> to gage how fast this page is. This metric is currently testing against the <strong><?php echo $context->options['strategy'] ?></strong> strategy.
</p>

<?php if (isset($parent) && $parent->context->getRawObject() instanceof \SiteMaster\Core\Auditor\Site\Page\MetricGrade): ?>
    <?php $page = $parent->context->getPage(); ?>
    <p>
        To find out how to fix these errors and for more information, you can run this page though the <a href="<?php echo $url .= '?url=' . urlencode($page->uri) ?>" target="_blank" class="wdn-button">Google PageSpeed service</a>
    </p>
<?php endif; ?>
