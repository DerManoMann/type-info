<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Radebatz\TypeInfo\Tests\Type;

use PHPUnit\Framework\TestCase;
use Radebatz\TypeInfo\Tests\Fixtures\DummyEnum;
use Radebatz\TypeInfo\Type\EnumType;

class EnumTypeTest extends TestCase
{
    public function testToString()
    {
        $this->assertSame(DummyEnum::class, (string) new EnumType(DummyEnum::class));
    }
}
