<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: opentelemetry/proto/metrics/v1/metrics.proto

namespace Opentelemetry\Proto\Metrics\V1\SummaryDataPoint;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Represents the value at a given quantile of a distribution.
 * To record Min and Max values following conventions are used:
 * - The 1.0 quantile is equivalent to the maximum value observed.
 * - The 0.0 quantile is equivalent to the minimum value observed.
 * See the following issue for more context:
 * https://github.com/open-telemetry/opentelemetry-proto/issues/125
 *
 * Generated from protobuf message <code>opentelemetry.proto.metrics.v1.SummaryDataPoint.ValueAtQuantile</code>
 */
class ValueAtQuantile extends \Google\Protobuf\Internal\Message
{
    /**
     * The quantile of a distribution. Must be in the interval
     * [0.0, 1.0].
     *
     * Generated from protobuf field <code>double quantile = 1;</code>
     */
    protected $quantile = 0.0;
    /**
     * The value at the given quantile of a distribution.
     * Quantile values must NOT be negative.
     *
     * Generated from protobuf field <code>double value = 2;</code>
     */
    protected $value = 0.0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type float $quantile
     *           The quantile of a distribution. Must be in the interval
     *           [0.0, 1.0].
     *     @type float $value
     *           The value at the given quantile of a distribution.
     *           Quantile values must NOT be negative.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Opentelemetry\Proto\Metrics\V1\Metrics::initOnce();
        parent::__construct($data);
    }

    /**
     * The quantile of a distribution. Must be in the interval
     * [0.0, 1.0].
     *
     * Generated from protobuf field <code>double quantile = 1;</code>
     * @return float
     */
    public function getQuantile()
    {
        return $this->quantile;
    }

    /**
     * The quantile of a distribution. Must be in the interval
     * [0.0, 1.0].
     *
     * Generated from protobuf field <code>double quantile = 1;</code>
     * @param float $var
     * @return $this
     */
    public function setQuantile($var)
    {
        GPBUtil::checkDouble($var);
        $this->quantile = $var;

        return $this;
    }

    /**
     * The value at the given quantile of a distribution.
     * Quantile values must NOT be negative.
     *
     * Generated from protobuf field <code>double value = 2;</code>
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * The value at the given quantile of a distribution.
     * Quantile values must NOT be negative.
     *
     * Generated from protobuf field <code>double value = 2;</code>
     * @param float $var
     * @return $this
     */
    public function setValue($var)
    {
        GPBUtil::checkDouble($var);
        $this->value = $var;

        return $this;
    }

}

