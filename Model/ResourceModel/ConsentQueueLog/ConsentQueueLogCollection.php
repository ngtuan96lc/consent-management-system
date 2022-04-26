<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Model\ResourceModel\ConsentQueueLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use SoftLoft\ConsentManagementSystem\Model\ResourceModel\ConsentQueueLog;

class ConsentQueueLogCollection extends AbstractCollection
{
    /**
     * _construct function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \SoftLoft\ConsentManagementSystem\Model\ConsentQueueLog::class,
            ConsentQueueLog::class
        );
    }
}
