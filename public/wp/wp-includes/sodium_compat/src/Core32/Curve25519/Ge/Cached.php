<?php

if (class_exists('ParagonIE_Sodium_Core32_Curve25519_Ge_Cached', false)) {
    return;
}
/**
 * Class ParagonIE_Sodium_Core32_Curve25519_Ge_Cached
 */
class ParagonIE_Sodium_Core32_Curve25519_Ge_Cached
{
    /**
     * @var ParagonIE_Sodium_Core32_Curve25519_Fe
     */
    public $YplusX;

    /**
     * @var ParagonIE_Sodium_Core32_Curve25519_Fe
     */
    public $YminusX;

    /**
     * @var ParagonIE_Sodium_Core32_Curve25519_Fe
     */
    public $Z;

    /**
     * @var ParagonIE_Sodium_Core32_Curve25519_Fe
     */
    public $T2d;

    /**
     * ParagonIE_Sodium_Core32_Curve25519_Ge_Cached constructor.
     *
     * @internal You should not use this directly from another application
     */
    public function __construct(
        ?ParagonIE_Sodium_Core32_Curve25519_Fe $YplusX = null,
        ?ParagonIE_Sodium_Core32_Curve25519_Fe $YminusX = null,
        ?ParagonIE_Sodium_Core32_Curve25519_Fe $Z = null,
        ?ParagonIE_Sodium_Core32_Curve25519_Fe $T2d = null
    ) {
        if ($YplusX === null) {
            $YplusX = new ParagonIE_Sodium_Core32_Curve25519_Fe;
        }
        $this->YplusX = $YplusX;
        if ($YminusX === null) {
            $YminusX = new ParagonIE_Sodium_Core32_Curve25519_Fe;
        }
        $this->YminusX = $YminusX;
        if ($Z === null) {
            $Z = new ParagonIE_Sodium_Core32_Curve25519_Fe;
        }
        $this->Z = $Z;
        if ($T2d === null) {
            $T2d = new ParagonIE_Sodium_Core32_Curve25519_Fe;
        }
        $this->T2d = $T2d;
    }
}
