<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class AccountThresholdsUpdatedEffectResponse extends EffectResponse
{
    private int $lowThreshold;
    private int $medThreshold;
    private int $highThreshold;

    /**
     * @return int
     */
    public function getLowThreshold(): int
    {
        return $this->lowThreshold;
    }

    /**
     * @return int
     */
    public function getMedThreshold(): int
    {
        return $this->medThreshold;
    }

    /**
     * @return int
     */
    public function getHighThreshold(): int
    {
        return $this->highThreshold;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['low_threshold'])) $this->lowThreshold = $json['low_threshold'];
        if (isset($json['med_threshold'])) $this->medThreshold = $json['med_threshold'];
        if (isset($json['high_threshold'])) $this->highThreshold = $json['high_threshold'];

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AccountThresholdsUpdatedEffectResponse {
        $result = new AccountThresholdsUpdatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}