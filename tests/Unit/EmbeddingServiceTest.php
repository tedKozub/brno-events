<?php

namespace Tests\Unit;

use App\Services\EmbeddingService;
use PHPUnit\Framework\TestCase;

class EmbeddingServiceTest extends TestCase
{
    public function test_cosine_similarity_identical_vectors(): void
    {
        $a = [1.0, 2.0, 3.0];
        $b = [1.0, 2.0, 3.0];

        $sim = EmbeddingService::cosineSimilarity($a, $b);
        $this->assertEqualsWithDelta(1.0, $sim, 0.0001);
    }

    public function test_cosine_similarity_orthogonal_vectors(): void
    {
        $a = [1.0, 0.0];
        $b = [0.0, 1.0];

        $sim = EmbeddingService::cosineSimilarity($a, $b);
        $this->assertEqualsWithDelta(0.0, $sim, 0.0001);
    }

    public function test_cosine_similarity_opposite_vectors(): void
    {
        $a = [1.0, 0.0];
        $b = [-1.0, 0.0];

        $sim = EmbeddingService::cosineSimilarity($a, $b);
        $this->assertEqualsWithDelta(-1.0, $sim, 0.0001);
    }

    public function test_cosine_similarity_empty_or_mismatched(): void
    {
        $this->assertEquals(0.0, EmbeddingService::cosineSimilarity([], []));
        $this->assertEquals(0.0, EmbeddingService::cosineSimilarity([1.0], [1.0, 2.0]));
    }
}
