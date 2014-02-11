<?php

/*
 * This file is part of the Ruler package, an OpenSky project.
 *
 * (c) 2011 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ruler;

/**
 * A Ruler Value.
 *
 * A Value represents a comparable terminal value. Variables and Comparison Operators
 * are resolved to Values by applying the current Context and the default Variable value.
 *
 * @author Justin Hileman <justin@shopopensky.com>
 */
class Value
{
    protected $value;

    /**
     * Value constructor.
     *
     * A Value object is immutable, and is used by Variables for comparing their default
     * values or facts from the current Context.
     *
     * @param mixed $value Immutable value represented by this Value object
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Return the value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Equal To comparison.
     *
     * @param Value $value Value object to compare against
     *
     * @return boolean
     */
    public function equalTo(Value $value)
    {
        return $this->value == $value->getValue();
    }

    /**
     * Identical To comparison.
     *
     * @param Value $value Value object to compare against
     *
     * @return boolean
     */
    public function sameAs(Value $value)
    {
        return $this->value === $value->getValue();
    }

    /**
     * Contains comparison.
     *
     * @param Value $value Value object to compare against
     *
     * @return boolean
     */
    public function contains(Value $value)
    {
        if (is_array($this->value)) {
            return in_array($value->getValue(), $this->value);
        } elseif (is_string($this->value)) {
            return strpos($this->value, $value->getValue()) !== false;
        }

        return false;
    }

    /**
     * In comparison.
     *
     * @param Value $value Value object to compare against
     *
     * @return boolean
     */
    public function in(Value $value)
    {
        if (is_array($value->getValue())) {
            return in_array($this->value, $value->getValue());
        } elseif (is_string($value->getValue())) {
            return strpos($value->getValue(), $this->value) !== false;
        }

        return false;
    }

    /**
     * InIpRange comparison.
     * Network ranges can be specified as:
     * 1. Wildcard format:     1.2.3.*
     * 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
     * 3. Start-End IP format: 1.2.3.0-1.2.3.255
     *
     * Return value BOOLEAN : ip_in_range($ip, $range);
     *
     * Copyright 2008: Paul Gregg <pgregg@pgregg.com>
     * 10 January 2008
     * Version: 1.2
     *
     * Source website: http://www.pgregg.com/projects/php/ip_in_range/
     *
     * @param Value $value Value object to compare against
     *
     * @return boolean
     */
    public function inIpRange(Value $value)
    {
        $ranges = array();
        if (is_array($value->getValue())) {
            foreach ($value->getValue() as $range) {
                $ranges[] = $range;
            }
        } else if (is_string($value->getValue())) {
            $ranges[] = $value->getValue();
        }
        if (count($ranges) > 0) {
            $ip = $this->value;
            foreach ($ranges as $range) {
                if (strpos($range, '/') !== false) {
                    // $range is in IP/NETMASK format
                    list($range, $netmask) = explode('/', $range, 2);
                    if (strpos($netmask, '.') !== false) {
                        // $netmask is a 255.255.0.0 format
                        $netmask = str_replace('*', '0', $netmask);
                        $netmask_dec = ip2long($netmask);
                        if ((ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec)) {
                            return true;
                        }
                    } else {
                        // $netmask is a CIDR size block
                        // fix the range argument
                        $x = explode('.', $range);
                        while (count($x) < 4)
                          $x[] = '0';
                        list($a, $b, $c, $d) = $x;
                        $range = sprintf("%u.%u.%u.%u", empty($a) ? '0' : $a, empty($b) ? '0' : $b, empty($c) ? '0' : $c, empty($d) ? '0' : $d);
                        $range_dec = ip2long($range);
                        $ip_dec = ip2long($ip);

                        # Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
                        #$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));
                        # Strategy 2 - Use math to create it
                        $wildcard_dec = pow(2, (32 - $netmask)) - 1;
                        $netmask_dec = ~ $wildcard_dec;

                        if (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec)) {
                            return true;
                        }
                    }
                } else {
                    // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
                    if (strpos($range, '*') !== false) { // a.b.*.* format
                        // Just convert to A-B format by setting * to 0 for A and 255 for B
                        $lower = str_replace('*', '0', $range);
                        $upper = str_replace('*', '255', $range);
                        $range = "$lower-$upper";
                    }
                    if (strpos($range, '-') !== false) { // A-B format
                        list($lower, $upper) = explode('-', $range, 2);
                        $lower_dec = (float) sprintf("%u", ip2long($lower));
                        $upper_dec = (float) sprintf("%u", ip2long($upper));
                        $ip_dec = (float) sprintf("%u", ip2long($ip));
                        if (($ip_dec >= $lower_dec) && ($ip_dec <= $upper_dec)) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

  /**
     * Greater Than comparison.
     *
     * @param Value $value Value object to compare against
     *
     * @return boolean
     */
    public function greaterThan(Value $value)
    {
        return $this->value > $value->getValue();
    }

    /**
     * Less Than comparison.
     *
     * @param Value $value Value object to compare against
     *
     * @return boolean
     */
    public function lessThan(Value $value)
    {
        return $this->value < $value->getValue();
    }

  /**
     * After Than comparison.
     *
     * @param Value $value Value dateTime string to compare against
     *
     * @return boolean
     */
    public function afterThan(Value $value)
    {
        return strtotime($this->value) > strtotime($value->getValue());
    }

    /**
     * Before Than comparison.
     *
     * @param Value $value Value dateTime string to compare against
     *
     * @return boolean
     */
    public function beforeThan(Value $value)
    {
        return strtotime($this->value) < strtotime($value->getValue());
    }

    public function add(Value $value)
    {
        if (!is_numeric($this->value) || !is_numeric($value->getValue())) {
            throw new \RuntimeException("Arithmetic: values must be numeric");
        }

        return $this->value + $value->getValue();
    }

    public function divide(Value $value)
    {
        if (!is_numeric($this->value) || !is_numeric($value->getValue())) {
            throw new \RuntimeException("Arithmetic: values must be numeric");
        }
        if (0 == $value->getValue()) {
            throw new \RuntimeException("Division by zero");
        }

        return $this->value / $value->getValue();
    }

    public function modulo(Value $value)
    {
        if (!is_numeric($this->value) || !is_numeric($value->getValue())) {
            throw new \RuntimeException("Arithmetic: values must be numeric");
        }
        if (0 == $value->getValue()) {
            throw new \RuntimeException("Division by zero");
        }

        return $this->value % $value->getValue();
    }

    public function multiply(Value $value)
    {
        if (!is_numeric($this->value) || !is_numeric($value->getValue())) {
            throw new \RuntimeException("Arithmetic: values must be numeric");
        }

        return $this->value * $value->getValue();
    }

    public function subtract(Value $value)
    {
        if (!is_numeric($this->value) || !is_numeric($value->getValue())) {
            throw new \RuntimeException("Arithmetic: values must be numeric");
        }

        return $this->value - $value->getValue();
    }

    public function negate()
    {
        if (!is_numeric($this->value)) {
            throw new \RuntimeException("Arithmetic: values must be numeric");
        }

        return -$this->value;
    }

    public function ceil()
    {
        if (!is_numeric($this->value)) {
            throw new \RuntimeException("Arithmetic: values must be numeric");
        }

        return (int) ceil($this->value);
    }

    public function floor()
    {
        if (!is_numeric($this->value)) {
            throw new \RuntimeException("Arithmetic: values must be numeric");
        }

        return (int) floor($this->value);
    }

    public function exponentiate(Value $value)
    {
        if (!is_numeric($this->value) || !is_numeric($value->getValue())) {
            throw new \RuntimeException("Arithmetic: values must be numeric");
        }

        return pow($this->value, $value->getValue());
    }
}
