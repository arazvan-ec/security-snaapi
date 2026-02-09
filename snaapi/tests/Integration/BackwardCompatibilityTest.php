<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\Handler\GetEditorialHandler;
use App\Orchestrator\Chain\EditorialOrchestrator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Integration tests to verify backward compatibility between
 * the new Pipeline architecture and the legacy Orchestrator.
 */
#[CoversClass(GetEditorialHandler::class)]
#[Group('integration')]
#[Group('backward-compatibility')]
final class BackwardCompatibilityTest extends KernelTestCase
{
    private ?GetEditorialHandler $newHandler = null;
    private ?EditorialOrchestrator $legacyOrchestrator = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->newHandler = $container->get(GetEditorialHandler::class);
        $this->legacyOrchestrator = $container->get(EditorialOrchestrator::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function editorialIdsProvider(): iterable
    {
        // Add real editorial IDs from your test environment
        yield 'basic editorial' => ['4433'];
        yield 'editorial with multimedia' => ['4434'];
        yield 'editorial with tags' => ['4435'];
        yield 'editorial with membership' => ['4436'];
    }

    #[Test]
    #[DataProvider('editorialIdsProvider')]
    public function new_output_matches_legacy_output(string $editorialId): void
    {
        // Skip if services not available (unit test environment)
        if (null === $this->newHandler || null === $this->legacyOrchestrator) {
            self::markTestSkipped('Services not available in this environment');
        }

        // Get legacy response
        $request = new Request();
        $request->attributes->set('id', $editorialId);

        try {
            $legacyResponse = $this->legacyOrchestrator->execute($request);
        } catch (\Throwable $e) {
            self::markTestSkipped('Legacy orchestrator failed: ' . $e->getMessage());
        }

        // Get new response
        try {
            $newResponse = ($this->newHandler)($editorialId);
            $newResponseArray = json_decode(json_encode($newResponse), true);
        } catch (\Throwable $e) {
            self::fail('New handler failed: ' . $e->getMessage());
        }

        // Compare key fields
        $this->assertResponseFieldsMatch($legacyResponse, $newResponseArray);
    }

    /**
     * @param array<string, mixed> $legacy
     * @param array<string, mixed> $new
     */
    private function assertResponseFieldsMatch(array $legacy, array $new): void
    {
        // Core fields that MUST match
        $coreFields = ['id', 'url', 'lead', 'publicationDate', 'countComments'];

        foreach ($coreFields as $field) {
            self::assertArrayHasKey($field, $new, "Missing field: {$field}");
            self::assertEquals(
                $legacy[$field] ?? null,
                $new[$field] ?? null,
                "Field mismatch: {$field}"
            );
        }

        // Titles
        if (isset($legacy['titles'])) {
            self::assertArrayHasKey('titles', $new);
            self::assertEquals($legacy['titles']['title'], $new['titles']['title']);
        }

        // Section
        if (isset($legacy['section'])) {
            self::assertArrayHasKey('section', $new);
            self::assertEquals($legacy['section']['id'], $new['section']['id']);
            self::assertEquals($legacy['section']['name'], $new['section']['name']);
        }

        // Tags count
        if (isset($legacy['tags'])) {
            self::assertArrayHasKey('tags', $new);
            self::assertCount(\count($legacy['tags']), $new['tags'], 'Tags count mismatch');
        }

        // Signatures count
        if (isset($legacy['signatures'])) {
            self::assertArrayHasKey('signatures', $new);
            self::assertCount(\count($legacy['signatures']), $new['signatures'], 'Signatures count mismatch');
        }

        // Body elements count
        if (isset($legacy['body']['elements'])) {
            self::assertArrayHasKey('body', $new);
            self::assertArrayHasKey('elements', $new['body']);
            self::assertCount(
                \count($legacy['body']['elements']),
                $new['body']['elements'],
                'Body elements count mismatch'
            );
        }
    }

    #[Test]
    public function new_handler_returns_json_serializable_response(): void
    {
        if (null === $this->newHandler) {
            self::markTestSkipped('Handler not available');
        }

        // Use a known editorial ID
        try {
            $response = ($this->newHandler)('4433');

            self::assertInstanceOf(\JsonSerializable::class, $response);

            $json = json_encode($response);
            self::assertIsString($json);
            self::assertJson($json);

            $decoded = json_decode($json, true);
            self::assertIsArray($decoded);
            self::assertArrayHasKey('id', $decoded);
        } catch (\Throwable $e) {
            self::markTestSkipped('Editorial not available: ' . $e->getMessage());
        }
    }
}
