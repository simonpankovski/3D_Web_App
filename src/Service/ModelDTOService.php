<?php

namespace App\Service;

use App\DTO\ModelDTO;
use App\Entity\Model;

class ModelDTOService
{
    public function convertModelEntityToDTO(Model $model, array $links): ModelDTO
    {
        $modelDTO = new ModelDTO($model->getId(), $model->getName(), $model->getExtensions(), $model->getOwner()->getEmail(), $model->getPurchases(), $model->getPrice(), $model->isApproved(), $model->getCreatedOn(), $model->getUpdatedOn(), $links, $model->getRating(), $model->getPurchaseCount());
        $userEmails = [];
        $tags = [];
        if ($model->getPurchases() != null) {
            foreach ($model->getPurchases() as $purchase) {
                $userEmails[] = $purchase->getUser()->getEmail();
            }
        }
        foreach ($model->getTags() as $tag) {
            $tags[] = $tag->getName();
        }
        $modelDTO->setPurchases($userEmails);
        $modelDTO->setTags($tags);
        return $modelDTO;
    }
}