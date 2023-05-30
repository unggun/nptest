<?php

namespace Icube\MisiPintar\Model\Modifier;

class CaptionModifier implements \Icube\MisiPintar\Api\DataProcessorInterface
{
    /**
     * @inheritDoc
     */
    public function modify(array $data): array
    {
        foreach ($data as &$dt) {
            $dt['rewardCaption'] = sprintf("%s %s", "Hadiah", $dt['rewardCaption']);
        };
        return $data;
    }
}
