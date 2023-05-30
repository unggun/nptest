<?php
namespace Icube\TierPrice\Model;

use Magento\Framework\Indexer\ActionInterface as IndexerInterface;
use Magento\Framework\Mview\ActionInterface as MviewInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;

class Indexer implements IndexerInterface, MviewInterface
{
    public function __construct(
        \Icube\TierPrice\Model\Indexer\TierPrice $tierPriceIndexer
    ) {
        $this->tierPriceIndexer = $tierPriceIndexer;
    }

    /**
     * It will execute when process indexer in "Update on schedule" Mode.
     */
    public function execute($ids)
    {
        if (empty($ids)) {
            throw new InputException(__('Bad value ids.'));
        }
        try {
            $this->tierPriceIndexer->reindexRows($ids);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()), $e);
        }
    }

    /**
     * for execute full indexation
     */
    public function executeFull()
    {
        try {
            $this->tierPriceIndexer->reindexAll();
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()), $e);
        }
    }

    /**
     * for execute partial indexation by ID list
     */
    public function executeList(array $ids)
    {
        if (empty($ids)) {
            throw new InputException(__('Bad value ids.'));
        }
        try {
            $this->tierPriceIndexer->reindexRows($ids);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()), $e);
        }
    }

    /**
     * for execute partial indexation by ID
     */
    public function executeRow($id)
    {
        if (!isset($id) || empty($id)) {
            throw new InputException(
                __('can\'t rebuild the index for an undefined entity.')
            );
        }

        try {
            $this->tierPriceIndexer->reindexRows([$id]);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()), $e);
        }
    }
}
