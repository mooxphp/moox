<?php

/**
 * IXR_Base64
 *
 * @since 1.5.0
 */
class IXR_Base64
{
    public $data;

    /**
     * PHP5 constructor.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * PHP4 constructor.
     */
    public function IXR_Base64($data)
    {
        self::__construct($data);
    }

    public function getXml()
    {
        return '<base64>'.base64_encode($this->data).'</base64>';
    }
}
