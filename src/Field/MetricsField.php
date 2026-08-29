<?php

/**
 * @package     Weltspiegel\Plugin\System\Statistics
 *
 * @copyright   Weltspiegel Cottbus
 * @license     MIT
 */

namespace Weltspiegel\Plugin\System\Statistics\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\CheckboxesField;
use Weltspiegel\Component\Statistics\Administrator\Helper\MetricRegistry;

/**
 * Checkbox list of all measurable metrics.
 *
 * Reads the options straight from the registry so that adding a metric stays a
 * one-place change — no duplicated option list in the plugin manifest.
 *
 * @since 1.0.0
 */
class MetricsField extends CheckboxesField
{
    /**
     * The form field type.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $type = 'Metrics';

    /**
     * Build the option list from the metric registry.
     *
     * @return  array
     *
     * @since 1.0.0
     */
    protected function getOptions(): array
    {
        $options = [];

        foreach (MetricRegistry::all() as $key => $definition) {
            $options[] = (object) [
                'value'    => $key,
                'text'     => $definition['label'],
                'disable'  => false,
                'class'    => '',
                'selected' => false,
                'checked'  => false,
                'onclick'  => '',
                'onchange' => '',
            ];
        }

        return array_merge(parent::getOptions(), $options);
    }
}
