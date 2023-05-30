<?php

namespace Icube\MisiPintar\Model\Resolver\DataProvider;

class DataProcessorPool
{
    /**
     * @param \Icube\MisiPintar\Api\DataProcessorInterface[] $dataProcessors
     * @codeCoverageIgnore
     */
    public function __construct(
        array $dataProcessors
    ) {
        $this->dataProcessors = $dataProcessors;
    }

    public function process(array $data, $dataKey)
    {
        if (!isset($data[$dataKey])) {
            return $data;
        }
        foreach ($this->dataProcessors as $key => $processor) {
            $result = $processor->modify($data[$dataKey]);
        }
        $data[$dataKey] = $result ?? '';
        return $data;
    }
}
