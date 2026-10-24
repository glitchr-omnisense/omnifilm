<?php

namespace Omnifilm\Tests;

use Omnifilm\Capabilities;
use Omnifilm\Config;
use Omnifilm\Exception\InvalidConfigException;
use Omnifilm\Exception\NotSupportedException;
use Omnifilm\Model\Identifiers;
use Omnifilm\Model\Query;
use Omnifilm\Model\Scheme;
use Omnifilm\Model\Work;
use Omnifilm\Model\WorkRef;
use Omnifilm\Model\WorkType;
use Omnifilm\ProviderFactory;
use Omnifilm\ProviderInterface;
use Omnifilm\Registry;
use Omnifilm\Testing\FixedProviderFactory;
use PHPUnit\Framework\TestCase;

final class RegistryTest extends TestCase
{
    public function testProvidersAreBuiltOnceFromTheirFactoryAndOptions(): void
    {
        $registry = new Registry([new FixedProviderFactory(), self::bare()], ['films' => ['factory' => 'fixed'], 'names' => ['factory' => 'bare', 'options' => ['token' => 'not-a-real-token']]]);

        self::assertSame($registry->get('films'), $registry->get('films'));
        self::assertSame(['films', 'names'], $registry->names());
        self::assertSame(['fixed', 'bare'], $registry->factories());
        self::assertSame(['token' => 'not-a-real-token'], $registry->options('names'));
        self::assertTrue($registry->has('films'));
        self::assertFalse($registry->has('shows'));
        self::assertSame([], $registry->search('films')->search(new Query('x')));
    }

    public function testACapabilityTheProviderLacksIsSaid(): void
    {
        $registry = new Registry([self::bare()], ['names' => ['factory' => 'bare', 'options' => ['token' => 'x']]]);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('does not search');
        $registry->search('names');
    }

    public function testAMissingOptionIsNamed(): void
    {
        $registry = new Registry([self::bare()], ['names' => ['factory' => 'bare']]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The "bare" provider needs: token.');
        $registry->get('names');
    }

    public function testAnUnknownProviderOrFactoryIsNamed(): void
    {
        $registry = new Registry([new FixedProviderFactory()], ['films' => ['factory' => 'tmdb']]);

        try {
            $registry->get('shows');
            self::fail('No "shows" provider.');
        } catch (InvalidConfigException $e) {
            self::assertSame('No "shows" film provider; configured: films.', $e->getMessage());
        }
        $this->expectExceptionMessage('No "tmdb" factory for the "films" film provider; installed: fixed.');
        $registry->get('films');
    }

    public function testAWorkIsAskedOfTheFirstProviderThatResolvesItsRegister(): void
    {
        $stalker = new Work(WorkType::Movie, 'Stalker', Identifiers::of(['imdb' => 'tt0079944']), 'fixed');
        $registry = new Registry([self::bare(), new FixedProviderFactory([$stalker])], ['names' => ['factory' => 'bare', 'options' => ['token' => 'x']], 'films' => ['factory' => 'fixed']]);

        self::assertSame($stalker, $registry->work(WorkRef::imdb('tt0079944')));
        self::assertNull($registry->work(WorkRef::imdb('tt0079944', WorkType::Series)));
        self::assertNull($registry->work(WorkRef::imdb('tt0079944'), null, 'names'));
    }

    private static function bare(): ProviderFactory
    {
        return new class extends ProviderFactory {
            protected function populate(Config $c): void
            {
                $c->defaults(['omnifilm.factory_name' => 'bare', 'omnifilm.required_options' => ['token']]);
            }

            protected function build(Config $c): ProviderInterface
            {
                return new class implements ProviderInterface {
                    public function getName(): string
                    {
                        return 'bare';
                    }

                    public function getTitle(): string
                    {
                        return 'Bare';
                    }

                    public function capabilities(): Capabilities
                    {
                        return new Capabilities(types: [WorkType::Movie], lookupBy: ['movie' => [Scheme::Imdb]]);
                    }
                };
            }
        };
    }
}
