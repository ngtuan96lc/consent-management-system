<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ConsentQueueLog extends AbstractDb
{
    public const TABLE_NAME = 'consent_queue_log';

    /**
     * _construct function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, 'message_log_id');
    }
}
