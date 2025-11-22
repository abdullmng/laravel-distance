<?php

namespace Abdullmng\Distance\Traits;

trait CalculatesGeocodingAccuracy
{
    /**
     * Calculate accuracy score based on how well the result matches the original query.
     *
     * @param string $returnedAddress The address returned by the geocoder
     * @param string $originalAddress The original address queried
     * @param array $metadata Additional metadata to help calculate accuracy
     * @return float 0-1 score (1 = highest accuracy)
     */
    protected function calculateMatchAccuracy(
        string $returnedAddress,
        string $originalAddress,
        array $metadata = []
    ): float {
        $score = 0.5; // Base score

        $displayName = strtolower($returnedAddress);
        $originalLower = strtolower($originalAddress);

        // Extract key words from original address (ignore common words)
        $commonWords = [
            'no', 'number', 'street', 'st', 'avenue', 'ave', 'road', 'rd',
            'estate', 'close', 'drive', 'dr', 'lane', 'ln', 'way', 'place', 'pl'
        ];
        
        $originalWords = array_filter(
            preg_split('/[\s,]+/', $originalLower),
            fn($word) => strlen($word) > 2 && !in_array($word, $commonWords)
        );

        if (!empty($originalWords)) {
            $matchedWords = 0;
            foreach ($originalWords as $word) {
                if (str_contains($displayName, $word)) {
                    $matchedWords++;
                }
            }
            $matchRatio = $matchedWords / count($originalWords);
            $score += $matchRatio * 0.4; // Up to 40% based on word matching
        }

        // Bonus for exact match
        if ($displayName === $originalLower) {
            $score = 1.0;
        }

        // Bonus for containing the full original address
        if (str_contains($displayName, $originalLower)) {
            $score += 0.1;
        }

        // Ensure score is between 0 and 1
        return max(0.0, min(1.0, $score));
    }

    /**
     * Determine accuracy based on result type/category.
     *
     * @param string $type The type/category of the geocoding result
     * @return float 0-1 score
     */
    protected function getTypeAccuracy(string $type): float
    {
        $type = strtolower($type);

        // Very accurate - specific addresses
        $highAccuracy = ['house', 'building', 'address', 'poi', 'venue'];
        if (in_array($type, $highAccuracy)) {
            return 0.9;
        }

        // Moderate accuracy - streets and neighborhoods
        $mediumAccuracy = ['street', 'road', 'neighbourhood', 'neighborhood', 'suburb', 'quarter', 'residential'];
        if (in_array($type, $mediumAccuracy)) {
            return 0.6;
        }

        // Low accuracy - cities and larger areas
        $lowAccuracy = ['city', 'town', 'village', 'municipality', 'district'];
        if (in_array($type, $lowAccuracy)) {
            return 0.4;
        }

        // Very low accuracy - regions and countries
        $veryLowAccuracy = ['state', 'province', 'region', 'country'];
        if (in_array($type, $veryLowAccuracy)) {
            return 0.2;
        }

        // Unknown type
        return 0.5;
    }

    /**
     * Calculate combined accuracy score.
     *
     * @param string $returnedAddress
     * @param string $originalAddress
     * @param string|null $type
     * @param array $metadata
     * @return float
     */
    protected function calculateCombinedAccuracy(
        string $returnedAddress,
        string $originalAddress,
        ?string $type = null,
        array $metadata = []
    ): float {
        $matchScore = $this->calculateMatchAccuracy($returnedAddress, $originalAddress, $metadata);
        
        if ($type) {
            $typeScore = $this->getTypeAccuracy($type);
            // Weighted average: 60% match score, 40% type score
            return ($matchScore * 0.6) + ($typeScore * 0.4);
        }

        return $matchScore;
    }
}

