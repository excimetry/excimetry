<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: opentelemetry/proto/common/v1/common.proto

namespace Opentelemetry\Proto\Common\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * InstrumentationScope is a message representing the instrumentation scope information
 * such as the fully qualified name and version. 
 *
 * Generated from protobuf message <code>opentelemetry.proto.common.v1.InstrumentationScope</code>
 */
class InstrumentationScope extends \Google\Protobuf\Internal\Message
{
    /**
     * An empty instrumentation scope name means the name is unknown.
     *
     * Generated from protobuf field <code>string name = 1;</code>
     */
    protected $name = '';
    /**
     * Generated from protobuf field <code>string version = 2;</code>
     */
    protected $version = '';
    /**
     * Additional attributes that describe the scope. [Optional].
     * Attribute keys MUST be unique (it is not allowed to have more than one
     * attribute with the same key).
     *
     * Generated from protobuf field <code>repeated .opentelemetry.proto.common.v1.KeyValue attributes = 3;</code>
     */
    private $attributes;
    /**
     * Generated from protobuf field <code>uint32 dropped_attributes_count = 4;</code>
     */
    protected $dropped_attributes_count = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $name
     *           An empty instrumentation scope name means the name is unknown.
     *     @type string $version
     *     @type array<\Opentelemetry\Proto\Common\V1\KeyValue>|\Google\Protobuf\Internal\RepeatedField $attributes
     *           Additional attributes that describe the scope. [Optional].
     *           Attribute keys MUST be unique (it is not allowed to have more than one
     *           attribute with the same key).
     *     @type int $dropped_attributes_count
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Opentelemetry\Proto\Common\V1\Common::initOnce();
        parent::__construct($data);
    }

    /**
     * An empty instrumentation scope name means the name is unknown.
     *
     * Generated from protobuf field <code>string name = 1;</code>
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * An empty instrumentation scope name means the name is unknown.
     *
     * Generated from protobuf field <code>string name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setName($var)
    {
        GPBUtil::checkString($var, True);
        $this->name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string version = 2;</code>
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Generated from protobuf field <code>string version = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setVersion($var)
    {
        GPBUtil::checkString($var, True);
        $this->version = $var;

        return $this;
    }

    /**
     * Additional attributes that describe the scope. [Optional].
     * Attribute keys MUST be unique (it is not allowed to have more than one
     * attribute with the same key).
     *
     * Generated from protobuf field <code>repeated .opentelemetry.proto.common.v1.KeyValue attributes = 3;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Additional attributes that describe the scope. [Optional].
     * Attribute keys MUST be unique (it is not allowed to have more than one
     * attribute with the same key).
     *
     * Generated from protobuf field <code>repeated .opentelemetry.proto.common.v1.KeyValue attributes = 3;</code>
     * @param array<\Opentelemetry\Proto\Common\V1\KeyValue>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setAttributes($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Opentelemetry\Proto\Common\V1\KeyValue::class);
        $this->attributes = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint32 dropped_attributes_count = 4;</code>
     * @return int
     */
    public function getDroppedAttributesCount()
    {
        return $this->dropped_attributes_count;
    }

    /**
     * Generated from protobuf field <code>uint32 dropped_attributes_count = 4;</code>
     * @param int $var
     * @return $this
     */
    public function setDroppedAttributesCount($var)
    {
        GPBUtil::checkUint32($var);
        $this->dropped_attributes_count = $var;

        return $this;
    }

}

