<?php

/**
 * Class ParagonIE_Sodium_Core32_Int32
 *
 * Encapsulates a 32-bit integer.
 *
 * These are immutable. It always returns a new instance.
 */
class ParagonIE_Sodium_Core32_Int32
{
    /**
     * @var array<int, int> - two 16-bit integers
     *
     * 0 is the higher 16 bits
     * 1 is the lower 16 bits
     */
    public $limbs = [0, 0];

    /**
     * @var int
     */
    public $overflow = 0;

    /**
     * @var bool
     */
    public $unsignedInt = false;

    /**
     * ParagonIE_Sodium_Core32_Int32 constructor.
     *
     * @param  array  $array
     * @param  bool  $unsignedInt
     */
    public function __construct($array = [0, 0], $unsignedInt = false)
    {
        $this->limbs = [
            (int) $array[0],
            (int) $array[1],
        ];
        $this->overflow = 0;
        $this->unsignedInt = $unsignedInt;
    }

    /**
     * Adds two int32 objects
     *
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function addInt32(ParagonIE_Sodium_Core32_Int32 $addend)
    {
        $i0 = $this->limbs[0];
        $i1 = $this->limbs[1];
        $j0 = $addend->limbs[0];
        $j1 = $addend->limbs[1];

        $r1 = $i1 + ($j1 & 0xFFFF);
        $carry = $r1 >> 16;

        $r0 = $i0 + ($j0 & 0xFFFF) + $carry;
        $carry = $r0 >> 16;

        $r0 &= 0xFFFF;
        $r1 &= 0xFFFF;

        $return = new ParagonIE_Sodium_Core32_Int32(
            [$r0, $r1]
        );
        $return->overflow = $carry;
        $return->unsignedInt = $this->unsignedInt;

        return $return;
    }

    /**
     * Adds a normal integer to an int32 object
     *
     * @param  int  $int
     * @return ParagonIE_Sodium_Core32_Int32
     *
     * @throws SodiumException
     * @throws TypeError
     */
    public function addInt($int)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($int, 'int', 1);
        /** @var int $int */
        $int = (int) $int;

        $int = (int) $int;

        $i0 = $this->limbs[0];
        $i1 = $this->limbs[1];

        $r1 = $i1 + ($int & 0xFFFF);
        $carry = $r1 >> 16;

        $r0 = $i0 + (($int >> 16) & 0xFFFF) + $carry;
        $carry = $r0 >> 16;
        $r0 &= 0xFFFF;
        $r1 &= 0xFFFF;
        $return = new ParagonIE_Sodium_Core32_Int32(
            [$r0, $r1]
        );
        $return->overflow = $carry;
        $return->unsignedInt = $this->unsignedInt;

        return $return;
    }

    /**
     * @param  int  $b
     * @return int
     */
    public function compareInt($b = 0)
    {
        $gt = 0;
        $eq = 1;

        $i = 2;
        $j = 0;
        while ($i > 0) {
            $i--;
            /** @var int $x1 */
            $x1 = $this->limbs[$i];
            /** @var int $x2 */
            $x2 = ($b >> ($j << 4)) & 0xFFFF;
            /** @var int $gt */
            $gt |= (($x2 - $x1) >> 8) & $eq;
            /** @var int $eq */
            $eq &= (($x2 ^ $x1) - 1) >> 8;
        }

        return ($gt + $gt - $eq) + 1;
    }

    /**
     * @param  int  $m
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function mask($m = 0)
    {
        /** @var int $hi */
        $hi = ((int) $m >> 16);
        $hi &= 0xFFFF;
        /** @var int $lo */
        $lo = ((int) $m) & 0xFFFF;

        return new ParagonIE_Sodium_Core32_Int32(
            [
                (int) ($this->limbs[0] & $hi),
                (int) ($this->limbs[1] & $lo),
            ],
            $this->unsignedInt
        );
    }

    /**
     * @param  array<int, int>  $a
     * @param  array<int, int>  $b
     * @param  int  $baseLog2
     * @return array<int, int>
     */
    public function multiplyLong(array $a, array $b, $baseLog2 = 16)
    {
        $a_l = count($a);
        $b_l = count($b);
        /** @var array<int, int> $r */
        $r = array_fill(0, $a_l + $b_l + 1, 0);
        $base = 1 << $baseLog2;
        for ($i = 0; $i < $a_l; $i++) {
            $a_i = $a[$i];
            for ($j = 0; $j < $a_l; $j++) {
                $b_j = $b[$j];
                $product = ($a_i * $b_j) + $r[$i + $j];
                $carry = ((int) $product >> $baseLog2 & 0xFFFF);
                $r[$i + $j] = ((int) $product - (int) ($carry * $base)) & 0xFFFF;
                $r[$i + $j + 1] += $carry;
            }
        }

        return array_slice($r, 0, 5);
    }

    /**
     * @param  int  $int
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function mulIntFast($int)
    {
        // Handle negative numbers
        $aNeg = ($this->limbs[0] >> 15) & 1;
        $bNeg = ($int >> 31) & 1;
        $a = array_reverse($this->limbs);
        $b = [
            $int & 0xFFFF,
            ($int >> 16) & 0xFFFF,
        ];
        if ($aNeg) {
            for ($i = 0; $i < 2; $i++) {
                $a[$i] = ($a[$i] ^ 0xFFFF) & 0xFFFF;
            }
            $a[0]++;
        }
        if ($bNeg) {
            for ($i = 0; $i < 2; $i++) {
                $b[$i] = ($b[$i] ^ 0xFFFF) & 0xFFFF;
            }
            $b[0]++;
        }
        // Multiply
        $res = $this->multiplyLong($a, $b);

        // Re-apply negation to results
        if ($aNeg !== $bNeg) {
            for ($i = 0; $i < 2; $i++) {
                $res[$i] = (0xFFFF ^ $res[$i]) & 0xFFFF;
            }
            // Handle integer overflow
            $c = 1;
            for ($i = 0; $i < 2; $i++) {
                $res[$i] += $c;
                $c = $res[$i] >> 16;
                $res[$i] &= 0xFFFF;
            }
        }

        // Return our values
        $return = new ParagonIE_Sodium_Core32_Int32;
        $return->limbs = [
            $res[1] & 0xFFFF,
            $res[0] & 0xFFFF,
        ];
        if (count($res) > 2) {
            $return->overflow = $res[2] & 0xFFFF;
        }
        $return->unsignedInt = $this->unsignedInt;

        return $return;
    }

    /**
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function mulInt32Fast(ParagonIE_Sodium_Core32_Int32 $right)
    {
        $aNeg = ($this->limbs[0] >> 15) & 1;
        $bNeg = ($right->limbs[0] >> 15) & 1;

        $a = array_reverse($this->limbs);
        $b = array_reverse($right->limbs);
        if ($aNeg) {
            for ($i = 0; $i < 2; $i++) {
                $a[$i] = ($a[$i] ^ 0xFFFF) & 0xFFFF;
            }
            $a[0]++;
        }
        if ($bNeg) {
            for ($i = 0; $i < 2; $i++) {
                $b[$i] = ($b[$i] ^ 0xFFFF) & 0xFFFF;
            }
            $b[0]++;
        }
        $res = $this->multiplyLong($a, $b);
        if ($aNeg !== $bNeg) {
            if ($aNeg !== $bNeg) {
                for ($i = 0; $i < 2; $i++) {
                    $res[$i] = ($res[$i] ^ 0xFFFF) & 0xFFFF;
                }
                $c = 1;
                for ($i = 0; $i < 2; $i++) {
                    $res[$i] += $c;
                    $c = $res[$i] >> 16;
                    $res[$i] &= 0xFFFF;
                }
            }
        }
        $return = new ParagonIE_Sodium_Core32_Int32;
        $return->limbs = [
            $res[1] & 0xFFFF,
            $res[0] & 0xFFFF,
        ];
        if (count($res) > 2) {
            $return->overflow = $res[2];
        }

        return $return;
    }

    /**
     * @param  int  $int
     * @param  int  $size
     * @return ParagonIE_Sodium_Core32_Int32
     *
     * @throws SodiumException
     * @throws TypeError
     */
    public function mulInt($int = 0, $size = 0)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($int, 'int', 1);
        ParagonIE_Sodium_Core32_Util::declareScalarType($size, 'int', 2);
        if (ParagonIE_Sodium_Compat::$fastMult) {
            return $this->mulIntFast((int) $int);
        }
        /** @var int $int */
        $int = (int) $int;
        /** @var int $size */
        $size = (int) $size;

        if (! $size) {
            $size = 31;
        }
        /** @var int $size */
        $a = clone $this;
        $return = new ParagonIE_Sodium_Core32_Int32;
        $return->unsignedInt = $this->unsignedInt;

        // Initialize:
        $ret0 = 0;
        $ret1 = 0;
        $a0 = $a->limbs[0];
        $a1 = $a->limbs[1];

        /** @var int $size */
        /** @var int $i */
        for ($i = $size; $i >= 0; $i--) {
            $m = (int) (-($int & 1));
            $x0 = $a0 & $m;
            $x1 = $a1 & $m;

            $ret1 += $x1;
            $c = $ret1 >> 16;

            $ret0 += $x0 + $c;

            $ret0 &= 0xFFFF;
            $ret1 &= 0xFFFF;

            $a1 = ($a1 << 1);
            $x1 = $a1 >> 16;
            $a0 = ($a0 << 1) | $x1;
            $a0 &= 0xFFFF;
            $a1 &= 0xFFFF;
            $int >>= 1;
        }
        $return->limbs[0] = $ret0;
        $return->limbs[1] = $ret1;

        return $return;
    }

    /**
     * @param  int  $size
     * @return ParagonIE_Sodium_Core32_Int32
     *
     * @throws SodiumException
     * @throws TypeError
     */
    public function mulInt32(ParagonIE_Sodium_Core32_Int32 $int, $size = 0)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($size, 'int', 2);
        if (ParagonIE_Sodium_Compat::$fastMult) {
            return $this->mulInt32Fast($int);
        }
        if (! $size) {
            $size = 31;
        }
        /** @var int $size */
        $a = clone $this;
        $b = clone $int;
        $return = new ParagonIE_Sodium_Core32_Int32;
        $return->unsignedInt = $this->unsignedInt;

        // Initialize:
        $ret0 = 0;
        $ret1 = 0;
        $a0 = $a->limbs[0];
        $a1 = $a->limbs[1];
        $b0 = $b->limbs[0];
        $b1 = $b->limbs[1];

        /** @var int $size */
        /** @var int $i */
        for ($i = $size; $i >= 0; $i--) {
            $m = (int) (-($b1 & 1));
            $x0 = $a0 & $m;
            $x1 = $a1 & $m;

            $ret1 += $x1;
            $c = $ret1 >> 16;

            $ret0 += $x0 + $c;

            $ret0 &= 0xFFFF;
            $ret1 &= 0xFFFF;

            $a1 = ($a1 << 1);
            $x1 = $a1 >> 16;
            $a0 = ($a0 << 1) | $x1;
            $a0 &= 0xFFFF;
            $a1 &= 0xFFFF;

            $x0 = ($b0 & 1) << 16;
            $b0 = ($b0 >> 1);
            $b1 = (($b1 | $x0) >> 1);

            $b0 &= 0xFFFF;
            $b1 &= 0xFFFF;
        }
        $return->limbs[0] = $ret0;
        $return->limbs[1] = $ret1;

        return $return;
    }

    /**
     * OR this 32-bit integer with another.
     *
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function orInt32(ParagonIE_Sodium_Core32_Int32 $b)
    {
        $return = new ParagonIE_Sodium_Core32_Int32;
        $return->unsignedInt = $this->unsignedInt;
        $return->limbs = [
            (int) ($this->limbs[0] | $b->limbs[0]),
            (int) ($this->limbs[1] | $b->limbs[1]),
        ];
        /** @var int overflow */
        $return->overflow = $this->overflow | $b->overflow;

        return $return;
    }

    /**
     * @param  int  $b
     * @return bool
     */
    public function isGreaterThan($b = 0)
    {
        return $this->compareInt($b) > 0;
    }

    /**
     * @param  int  $b
     * @return bool
     */
    public function isLessThanInt($b = 0)
    {
        return $this->compareInt($b) < 0;
    }

    /**
     * @param  int  $c
     * @return ParagonIE_Sodium_Core32_Int32
     *
     * @throws SodiumException
     * @throws TypeError
     *
     * @psalm-suppress MixedArrayAccess
     */
    public function rotateLeft($c = 0)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($c, 'int', 1);
        /** @var int $c */
        $c = (int) $c;

        $return = new ParagonIE_Sodium_Core32_Int32;
        $return->unsignedInt = $this->unsignedInt;
        $c &= 31;
        if ($c === 0) {
            // NOP, but we want a copy.
            $return->limbs = $this->limbs;
        } else {
            /** @var int $c */

            /** @var int $idx_shift */
            $idx_shift = ($c >> 4) & 1;

            /** @var int $sub_shift */
            $sub_shift = $c & 15;

            /** @var array<int, int> $limbs */
            $limbs = &$return->limbs;

            /** @var array<int, int> $myLimbs */
            $myLimbs = &$this->limbs;

            for ($i = 1; $i >= 0; $i--) {
                /** @var int $j */
                $j = ($i + $idx_shift) & 1;
                /** @var int $k */
                $k = ($i + $idx_shift + 1) & 1;
                $limbs[$i] = (int) (
                    (
                        ((int) ($myLimbs[$j]) << $sub_shift)
                            |
                        ((int) ($myLimbs[$k]) >> (16 - $sub_shift))
                    ) & 0xFFFF
                );
            }
        }

        return $return;
    }

    /**
     * Rotate to the right
     *
     * @param  int  $c
     * @return ParagonIE_Sodium_Core32_Int32
     *
     * @throws SodiumException
     * @throws TypeError
     *
     * @psalm-suppress MixedArrayAccess
     */
    public function rotateRight($c = 0)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($c, 'int', 1);
        /** @var int $c */
        $c = (int) $c;

        $return = new ParagonIE_Sodium_Core32_Int32;
        $return->unsignedInt = $this->unsignedInt;
        $c &= 31;
        /** @var int $c */
        if ($c === 0) {
            // NOP, but we want a copy.
            $return->limbs = $this->limbs;
        } else {
            /** @var int $c */

            /** @var int $idx_shift */
            $idx_shift = ($c >> 4) & 1;

            /** @var int $sub_shift */
            $sub_shift = $c & 15;

            /** @var array<int, int> $limbs */
            $limbs = &$return->limbs;

            /** @var array<int, int> $myLimbs */
            $myLimbs = &$this->limbs;

            for ($i = 1; $i >= 0; $i--) {
                /** @var int $j */
                $j = ($i - $idx_shift) & 1;
                /** @var int $k */
                $k = ($i - $idx_shift - 1) & 1;
                $limbs[$i] = (int) (
                    (
                        ((int) ($myLimbs[$j]) >> (int) ($sub_shift))
                            |
                        ((int) ($myLimbs[$k]) << (16 - (int) ($sub_shift)))
                    ) & 0xFFFF
                );
            }
        }

        return $return;
    }

    /**
     * @param  bool  $bool
     * @return self
     */
    public function setUnsignedInt($bool = false)
    {
        $this->unsignedInt = ! empty($bool);

        return $this;
    }

    /**
     * @param  int  $c
     * @return ParagonIE_Sodium_Core32_Int32
     *
     * @throws SodiumException
     * @throws TypeError
     */
    public function shiftLeft($c = 0)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($c, 'int', 1);
        /** @var int $c */
        $c = (int) $c;

        $return = new ParagonIE_Sodium_Core32_Int32;
        $return->unsignedInt = $this->unsignedInt;
        $c &= 63;
        /** @var int $c */
        if ($c === 0) {
            $return->limbs = $this->limbs;
        } elseif ($c < 0) {
            /** @var int $c */
            return $this->shiftRight(-$c);
        } else {
            /** @var int $c */
            /** @var int $tmp */
            $tmp = $this->limbs[1] << $c;
            $return->limbs[1] = (int) ($tmp & 0xFFFF);
            /** @var int $carry */
            $carry = $tmp >> 16;

            /** @var int $tmp */
            $tmp = ($this->limbs[0] << $c) | ($carry & 0xFFFF);
            $return->limbs[0] = (int) ($tmp & 0xFFFF);
        }

        return $return;
    }

    /**
     * @param  int  $c
     * @return ParagonIE_Sodium_Core32_Int32
     *
     * @throws SodiumException
     * @throws TypeError
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedOperand
     */
    public function shiftRight($c = 0)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($c, 'int', 1);
        /** @var int $c */
        $c = (int) $c;

        $return = new ParagonIE_Sodium_Core32_Int32;
        $return->unsignedInt = $this->unsignedInt;
        $c &= 63;
        /** @var int $c */
        if ($c >= 16) {
            $return->limbs = [
                (int) ($this->overflow & 0xFFFF),
                (int) ($this->limbs[0]),
            ];
            $return->overflow = $this->overflow >> 16;

            return $return->shiftRight($c & 15);
        }
        if ($c === 0) {
            $return->limbs = $this->limbs;
        } elseif ($c < 0) {
            /** @var int $c */
            return $this->shiftLeft(-$c);
        } else {
            if (! is_int($c)) {
                throw new TypeError;
            }
            /** @var int $c */
            // $return->limbs[0] = (int) (($this->limbs[0] >> $c) & 0xffff);
            $carryLeft = (int) ($this->overflow & ((1 << ($c + 1)) - 1));
            $return->limbs[0] = (int) ((($this->limbs[0] >> $c) | ($carryLeft << (16 - $c))) & 0xFFFF);
            $carryRight = (int) ($this->limbs[0] & ((1 << ($c + 1)) - 1));
            $return->limbs[1] = (int) ((($this->limbs[1] >> $c) | ($carryRight << (16 - $c))) & 0xFFFF);
            $return->overflow >>= $c;
        }

        return $return;
    }

    /**
     * Subtract a normal integer from an int32 object.
     *
     * @param  int  $int
     * @return ParagonIE_Sodium_Core32_Int32
     *
     * @throws SodiumException
     * @throws TypeError
     */
    public function subInt($int)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($int, 'int', 1);
        /** @var int $int */
        $int = (int) $int;

        $return = new ParagonIE_Sodium_Core32_Int32;
        $return->unsignedInt = $this->unsignedInt;

        /** @var int $tmp */
        $tmp = $this->limbs[1] - ($int & 0xFFFF);
        /** @var int $carry */
        $carry = $tmp >> 16;
        $return->limbs[1] = (int) ($tmp & 0xFFFF);

        /** @var int $tmp */
        $tmp = $this->limbs[0] - (($int >> 16) & 0xFFFF) + $carry;
        $return->limbs[0] = (int) ($tmp & 0xFFFF);

        return $return;
    }

    /**
     * Subtract two int32 objects from each other
     *
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function subInt32(ParagonIE_Sodium_Core32_Int32 $b)
    {
        $return = new ParagonIE_Sodium_Core32_Int32;
        $return->unsignedInt = $this->unsignedInt;

        /** @var int $tmp */
        $tmp = $this->limbs[1] - ($b->limbs[1] & 0xFFFF);
        /** @var int $carry */
        $carry = $tmp >> 16;
        $return->limbs[1] = (int) ($tmp & 0xFFFF);

        /** @var int $tmp */
        $tmp = $this->limbs[0] - ($b->limbs[0] & 0xFFFF) + $carry;
        $return->limbs[0] = (int) ($tmp & 0xFFFF);

        return $return;
    }

    /**
     * XOR this 32-bit integer with another.
     *
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function xorInt32(ParagonIE_Sodium_Core32_Int32 $b)
    {
        $return = new ParagonIE_Sodium_Core32_Int32;
        $return->unsignedInt = $this->unsignedInt;
        $return->limbs = [
            (int) ($this->limbs[0] ^ $b->limbs[0]),
            (int) ($this->limbs[1] ^ $b->limbs[1]),
        ];

        return $return;
    }

    /**
     * @param  int  $signed
     * @return self
     *
     * @throws SodiumException
     * @throws TypeError
     */
    public static function fromInt($signed)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($signed, 'int', 1);
        /** @var int $signed */
        $signed = (int) $signed;

        return new ParagonIE_Sodium_Core32_Int32(
            [
                (int) (($signed >> 16) & 0xFFFF),
                (int) ($signed & 0xFFFF),
            ]
        );
    }

    /**
     * @param  string  $string
     * @return self
     *
     * @throws SodiumException
     * @throws TypeError
     */
    public static function fromString($string)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($string, 'string', 1);
        $string = (string) $string;
        if (ParagonIE_Sodium_Core32_Util::strlen($string) !== 4) {
            throw new RangeException(
                'String must be 4 bytes; '.ParagonIE_Sodium_Core32_Util::strlen($string).' given.'
            );
        }
        $return = new ParagonIE_Sodium_Core32_Int32;

        $return->limbs[0] = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[0]) & 0xFF) << 8);
        $return->limbs[0] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[1]) & 0xFF);
        $return->limbs[1] = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[2]) & 0xFF) << 8);
        $return->limbs[1] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[3]) & 0xFF);

        return $return;
    }

    /**
     * @param  string  $string
     * @return self
     *
     * @throws SodiumException
     * @throws TypeError
     */
    public static function fromReverseString($string)
    {
        ParagonIE_Sodium_Core32_Util::declareScalarType($string, 'string', 1);
        $string = (string) $string;
        if (ParagonIE_Sodium_Core32_Util::strlen($string) !== 4) {
            throw new RangeException(
                'String must be 4 bytes; '.ParagonIE_Sodium_Core32_Util::strlen($string).' given.'
            );
        }
        $return = new ParagonIE_Sodium_Core32_Int32;

        $return->limbs[0] = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[3]) & 0xFF) << 8);
        $return->limbs[0] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[2]) & 0xFF);
        $return->limbs[1] = (int) ((ParagonIE_Sodium_Core32_Util::chrToInt($string[1]) & 0xFF) << 8);
        $return->limbs[1] |= (ParagonIE_Sodium_Core32_Util::chrToInt($string[0]) & 0xFF);

        return $return;
    }

    /**
     * @return array<int, int>
     */
    public function toArray()
    {
        return [(int) ($this->limbs[0] << 16 | $this->limbs[1])];
    }

    /**
     * @return string
     *
     * @throws TypeError
     */
    public function toString()
    {
        return
            ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[0] >> 8) & 0xFF).
            ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[0] & 0xFF).
            ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[1] >> 8) & 0xFF).
            ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[1] & 0xFF);
    }

    /**
     * @return int
     */
    public function toInt()
    {
        return (int) (
            (($this->limbs[0] & 0xFFFF) << 16)
                |
            ($this->limbs[1] & 0xFFFF)
        );
    }

    /**
     * @return ParagonIE_Sodium_Core32_Int32
     */
    public function toInt32()
    {
        $return = new ParagonIE_Sodium_Core32_Int32;
        $return->limbs[0] = (int) ($this->limbs[0] & 0xFFFF);
        $return->limbs[1] = (int) ($this->limbs[1] & 0xFFFF);
        $return->unsignedInt = $this->unsignedInt;
        $return->overflow = (int) ($this->overflow & 0x7FFFFFFF);

        return $return;
    }

    /**
     * @return ParagonIE_Sodium_Core32_Int64
     */
    public function toInt64()
    {
        $return = new ParagonIE_Sodium_Core32_Int64;
        $return->unsignedInt = $this->unsignedInt;
        if ($this->unsignedInt) {
            $return->limbs[0] += (($this->overflow >> 16) & 0xFFFF);
            $return->limbs[1] += (($this->overflow) & 0xFFFF);
        } else {
            $neg = -(($this->limbs[0] >> 15) & 1);
            $return->limbs[0] = (int) ($neg & 0xFFFF);
            $return->limbs[1] = (int) ($neg & 0xFFFF);
        }
        $return->limbs[2] = (int) ($this->limbs[0] & 0xFFFF);
        $return->limbs[3] = (int) ($this->limbs[1] & 0xFFFF);

        return $return;
    }

    /**
     * @return string
     *
     * @throws TypeError
     */
    public function toReverseString()
    {
        return ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[1] & 0xFF).
            ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[1] >> 8) & 0xFF).
            ParagonIE_Sodium_Core32_Util::intToChr($this->limbs[0] & 0xFF).
            ParagonIE_Sodium_Core32_Util::intToChr(($this->limbs[0] >> 8) & 0xFF);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->toString();
        } catch (TypeError $ex) {
            // PHP engine can't handle exceptions from __toString()
            return '';
        }
    }
}
