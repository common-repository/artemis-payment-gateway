<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class SignerSponsorshipUpdatedEffectResponse extends EffectResponse
{
    private string $newSponsor;
    private string $formerSponsor;
    private string $signer;

    /**
     * @return string
     */
    public function getNewSponsor(): string
    {
        return $this->newSponsor;
    }

    /**
     * @return string
     */
    public function getFormerSponsor(): string
    {
        return $this->formerSponsor;
    }

    /**
     * @return string
     */
    public function getSigner(): string
    {
        return $this->signer;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['new_sponsor'])) $this->newSponsor = $json['new_sponsor'];
        if (isset($json['former_sponsor'])) $this->formerSponsor = $json['former_sponsor'];
        if (isset($json['signer'])) $this->signer = $json['signer'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : SignerSponsorshipUpdatedEffectResponse {
        $result = new SignerSponsorshipUpdatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}