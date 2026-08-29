<?php

/**
 * @package     Weltspiegel\Plugin\System\Statistics
 *
 * @copyright   Weltspiegel Cottbus
 * @license     MIT
 */

namespace Weltspiegel\Plugin\System\Statistics\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\SubscriberInterface;
use Weltspiegel\Component\Statistics\Administrator\Helper\MetricRegistry;

/**
 * Embeds the CSS measurement probes into every frontend page.
 *
 * How this works: for each enabled metric one invisible element is placed in the
 * page, and one media query per bucket points that element's background image at
 * the collect endpoint. The browser loads exactly the one image whose query
 * matches its own display — so the server learns the bucket without ever reading
 * anything from the device. This is the same mechanism as a responsive image
 * chosen via srcset, and it needs neither JavaScript nor consent.
 *
 * @since 1.0.0
 */
final class Statistics extends CMSPlugin implements SubscriberInterface
{
    /**
     * Load plugin language files automatically
     *
     * @var boolean
     *
     * @since 1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this plugin listens to.
     *
     * @return  array
     *
     * @since 1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterDispatch' => 'injectProbeStyles',
            'onAfterRender'   => 'injectProbeMarkup',
        ];
    }

    /**
     * Add the generated media queries to the document.
     *
     * Deliberately hooked to onAfterDispatch rather than the more obvious
     * onBeforeCompileHead: that event is fired by Joomla's metas renderer, so it
     * never fires on a template that omits `<jdoc:include type="metas" />` — as
     * the Weltspiegel template does, because it writes its own minimal head.
     * onAfterDispatch runs in every site request before the document is
     * rendered, so the declaration still reaches the styles renderer in time.
     *
     * @return  void
     *
     * @since 1.0.0
     */
    public function injectProbeStyles(): void
    {
        if (!$this->shouldMeasure()) {
            return;
        }

        $metrics = $this->getEnabledMetrics();

        if (empty($metrics)) {
            return;
        }

        $this->getApplication()->getDocument()->addStyleDeclaration($this->buildStyles($metrics));
    }

    /**
     * Append the probe elements just before the closing body tag.
     *
     * @return  void
     *
     * @since 1.0.0
     */
    public function injectProbeMarkup(): void
    {
        if (!$this->shouldMeasure()) {
            return;
        }

        $metrics = $this->getEnabledMetrics();

        if (empty($metrics)) {
            return;
        }

        $app  = $this->getApplication();
        $body = $app->getBody();
        $pos  = strripos($body, '</body>');

        if ($pos === false) {
            return;
        }

        $app->setBody(substr($body, 0, $pos) . $this->buildMarkup($metrics) . substr($body, $pos));
    }

    /**
     * Whether the current request should carry measurement probes.
     *
     * @return  bool
     *
     * @since 1.0.0
     */
    private function shouldMeasure(): bool
    {
        $app = $this->getApplication();

        if (!$app->isClient('site')) {
            return false;
        }

        // Only regular HTML pages — never feeds, JSON or the collect endpoint's
        // own raw response.
        if ($app->getDocument()->getType() !== 'html') {
            return false;
        }

        if ($app->getInput()->getCmd('option') === 'com_statistics') {
            return false;
        }

        // Staff previewing their own edits would otherwise distort the picture
        // of what actual visitors use.
        if ($this->params->get('exclude_logged_in', 1) && $app->getIdentity() && !$app->getIdentity()->guest) {
            return false;
        }

        return true;
    }

    /**
     * Metric keys to measure, filtered against the registry.
     *
     * @return  string[]
     *
     * @since 1.0.0
     */
    private function getEnabledMetrics(): array
    {
        return MetricRegistry::normalize($this->params->get('metrics', MetricRegistry::DEFAULT_METRICS));
    }

    /**
     * Build the probe stylesheet for the given metrics.
     *
     * The probes are kept 1x1 and fully transparent rather than hidden with
     * display:none — a hidden element would not load its background image at all.
     *
     * @param   string[]  $metrics  Metric keys
     *
     * @return  string
     *
     * @since 1.0.0
     */
    private function buildStyles(array $metrics): string
    {
        $endpoint = Uri::root(true) . '/index.php?option=com_statistics&task=collect.track&format=raw';

        $css = '.ws-stats-probes{position:absolute;top:0;left:0;width:1px;height:1px;'
            . 'overflow:hidden;opacity:0;pointer-events:none}'
            . '.ws-stats-probes span{display:block;width:1px;height:1px}';

        foreach ($metrics as $metric) {
            $definition = MetricRegistry::get($metric);

            foreach ($definition['buckets'] as $bucket => $condition) {
                $url = $endpoint . '&m=' . rawurlencode($metric) . '&b=' . rawurlencode($bucket);

                $css .= '@media ' . $condition . '{#ws-stats-p-' . $metric
                    . '{background-image:url("' . $url . '")}}';
            }
        }

        return $css;
    }

    /**
     * Build the probe markup for the given metrics.
     *
     * @param   string[]  $metrics  Metric keys
     *
     * @return  string
     *
     * @since 1.0.0
     */
    private function buildMarkup(array $metrics): string
    {
        $html = '<div class="ws-stats-probes" aria-hidden="true">';

        foreach ($metrics as $metric) {
            $html .= '<span id="ws-stats-p-' . htmlspecialchars($metric, ENT_QUOTES, 'UTF-8') . '"></span>';
        }

        return $html . '</div>';
    }
}
