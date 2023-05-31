<?php

namespace Icube\MisiPintar\Api;

interface DataProcessorInterface
{
    /**
     * Run execution process to modify data
     *
     * @param array $data
     * @return array
     */
    public function modify(array $data): array;
}
