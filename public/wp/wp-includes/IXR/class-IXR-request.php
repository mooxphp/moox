<?php

/**
 * IXR_Request
 *
 * @since 1.5.0
 */
class IXR_Request
{
    public $method;

    public $args;

    public $xml;

    /**
     * PHP5 constructor.
     */
    public function __construct($method, $args)
    {
        $this->method = $method;
        $this->args = $args;
        $this->xml = <<<EOD
<?xml version="1.0"?>
<methodCall>
<methodName>{$this->method}</methodName>
<params>

EOD;
        foreach ($this->args as $arg) {
            $this->xml .= '<param><value>';
            $v = new IXR_Value($arg);
            $this->xml .= $v->getXml();
            $this->xml .= "</value></param>\n";
        }
        $this->xml .= '</params></methodCall>';
    }

    /**
     * PHP4 constructor.
     */
    public function IXR_Request($method, $args)
    {
        self::__construct($method, $args);
    }

    public function getLength()
    {
        return strlen($this->xml);
    }

    public function getXml()
    {
        return $this->xml;
    }
}
