<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Support\NewWaveProductAttributes;

trait HasProductExtendedDetails
{
    public function hasExtendedDetails(): bool
    {
        return ! empty($this->technical_specs)
            || ! empty($this->construction_features)
            || ! empty($this->certifications)
            || ! empty($this->customization_notes);
    }

    /**
     * @return array<int, array{key: string, label: string, short: string, icon: string, symbol: string, category: string}>
     */
    public function getCareInstructionsDetails(): array
    {
        return NewWaveProductAttributes::parseCareDetails($this->customization_notes);
    }

    /**
     * @return array<int, array{key: string, label: string, short: string, icon: string}>
     */
    public function getConstructionFeaturesDetails(): array
    {
        return NewWaveProductAttributes::parseFeatureDetails($this->construction_features);
    }

    /**
     * @return array<int, array{key: string, label: string, short: string, icon: string}>
     */
    public function getCertificationsDetails(): array
    {
        return NewWaveProductAttributes::parseCertificationDetails($this->certifications);
    }

    public function isCareInstructionNotes(): bool
    {
        return NewWaveProductAttributes::isCareInstructionText($this->customization_notes, $this->type);
    }
}
