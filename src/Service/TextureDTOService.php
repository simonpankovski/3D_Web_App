<?php

namespace App\Service;

use App\DTO\TextureDTO;
use App\Entity\Texture;

class TextureDTOService
{
    public function convertModelEntityToDTO(Texture $texture, array $links): TextureDTO
    {
        $textureDTO = new TextureDTO($texture->getId(), $texture->getRating(), $texture->getName(), $texture->getOwner()->getEmail(), $texture->getPurchases(), $texture->getPrice(), $texture->isApproved(), $texture->getCreatedOn(), $texture->getUpdatedOn(), $texture->getPurchaseCount(), $links);
        $userEmails = [];
        if ($texture->getPurchases() != null){
            foreach ($texture->getPurchases() as $purchase) {
                $userEmails[] = $purchase->getUser()->getEmail();
            }
        }
        $textureDTO->setPurchases($userEmails);
        return $textureDTO;
    }
}