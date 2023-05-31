<?php declare(strict_types=1);

namespace Icube\OverrideAdvancerate\Model;

use Ced\Advancerate\Model\ResourceModel\Carrier\Advancerate as CarrierAdvancerate;
use Magento\Framework\Model\AbstractModel;

class Advancerate extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(CarrierAdvancerate::class);
    }
}
