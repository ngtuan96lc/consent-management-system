<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Model;

use Magento\Framework\Model\AbstractModel;

class ConsentQueueLog extends AbstractModel
{
    /**
     * _construct function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\ConsentQueueLog::class);
    }
}
