<?php

namespace App\Services;

use Illuminate\Contracts\Hashing\Hasher;

class Md5Hasher implements Hasher
{
    public function info($hashedValue)
    {
        return [
            'algo' => 'md5',
            'algoName' => 'MD5',
            'options' => [],
        ];
    }

    public function make($value, array $options = [])
    {
        return md5($value);
    }

    public function check($value, $hashedValue, array $options = [])
    {
        return md5($value) === $hashedValue;
    }

    public function needsRehash($hashedValue, array $options = [])
    {
        return false;
    }
}
