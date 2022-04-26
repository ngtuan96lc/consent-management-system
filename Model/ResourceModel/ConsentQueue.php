<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ConsentQueue extends AbstractDb
{
    public const TABLE_NAME = 'consent_queue';

    /**
     * _construct function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, 'message_id');
    }
}
