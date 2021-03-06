<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Metadata;

use function array_flip;
use function array_unique;
use function assert;
use function strtolower;
use function trim;
use PHPUnit\Framework\TestSize\TestSize;

/**
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class GroupsFacade
{
    /**
     * @psalm-param class-string $className
     */
    public function groups(string $className, string $methodName): array
    {
        $groups = [];

        foreach (Registry::parser()->forClassAndMethod($className, $methodName) as $metadata) {
            assert($metadata instanceof Metadata);

            if ($metadata->isGroup()) {
                assert($metadata instanceof Group);

                $groups[] = $metadata->groupName();
            }

            if ($metadata->isCoversClass() || $metadata->isCoversMethod() || $metadata->isCoversFunction()) {
                assert($metadata instanceof CoversClass || $metadata instanceof CoversMethod || $metadata instanceof CoversFunction);

                $groups[] = '__phpunit_covers_' . self::canonicalizeName($metadata->asStringForCodeUnitMapper());
            }

            if ($metadata->isCovers()) {
                assert($metadata instanceof Covers);

                $groups[] = '__phpunit_covers_' . self::canonicalizeName($metadata->target());
            }

            if ($metadata->isUsesClass() || $metadata->isUsesMethod() || $metadata->isUsesFunction()) {
                assert($metadata instanceof UsesClass || $metadata instanceof UsesMethod || $metadata instanceof UsesFunction);

                $groups[] = '__phpunit_uses_' . self::canonicalizeName($metadata->asStringForCodeUnitMapper());
            }

            if ($metadata->isUses()) {
                assert($metadata instanceof Uses);

                $groups[] = '__phpunit_uses_' . self::canonicalizeName($metadata->target());
            }
        }

        return array_unique($groups);
    }

    /**
     * @psalm-param class-string $className
     */
    public function size(string $className, string $methodName): TestSize
    {
        $groups = array_flip($this->groups($className, $methodName));

        if (isset($groups['large'])) {
            return TestSize::large();
        }

        if (isset($groups['medium'])) {
            return TestSize::medium();
        }

        if (isset($groups['small'])) {
            return TestSize::small();
        }

        return TestSize::unknown();
    }

    private static function canonicalizeName(string $name): string
    {
        return strtolower(trim($name, '\\'));
    }
}
