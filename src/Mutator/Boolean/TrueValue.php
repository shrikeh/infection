<?php
/**
 * This code is licensed under the BSD 3-Clause License.
 *
 * Copyright (c) 2017, Maks Rafalko
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * * Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

declare(strict_types=1);

namespace Infection\Mutator\Boolean;

use function array_key_exists;
use Generator;
use Infection\Mutator\Definition;
use Infection\Mutator\GetMutatorName;
use Infection\Mutator\Mutator;
use Infection\Mutator\MutatorCategory;
use Infection\Mutator\Util\MutatorConfig;
use Infection\Visitor\ParentConnectorVisitor;
use PhpParser\Node;

/**
 * @internal
 */
final class TrueValue implements Mutator
{
    use GetMutatorName;

    private const DEFAULT_SETTINGS = [
        'array_search' => false,
        'in_array' => false,
    ];

    private $settings;

    public function __construct(MutatorConfig $config)
    {
        $this->settings = $config->getMutatorSettings();
    }

    public static function getDefinition(): ?Definition
    {
        return new Definition(
            'Replaces a boolean literal (`true`) with its opposite value (`false`). ',
            MutatorCategory::ORTHOGONAL_REPLACEMENT,
            null
        );
    }

    /**
     * @param Node\Expr\ConstFetch $node
     *
     * @return Generator<Node\Expr\ConstFetch>
     */
    public function mutate(Node $node): Generator
    {
        yield new Node\Expr\ConstFetch(new Node\Name('false'));
    }

    public function canMutate(Node $node): bool
    {
        if (!($node instanceof Node\Expr\ConstFetch)) {
            return false;
        }

        if ($node->name->toLowerString() !== 'true') {
            return false;
        }

        $parentNode = $node->getAttribute(ParentConnectorVisitor::PARENT_KEY);
        $grandParentNode = $parentNode !== null ? $parentNode->getAttribute(ParentConnectorVisitor::PARENT_KEY) : null;

        if (!$grandParentNode instanceof Node\Expr\FuncCall || !$grandParentNode->name instanceof Node\Name) {
            return true;
        }

        $resultSettings = array_merge(self::DEFAULT_SETTINGS, $this->settings);

        $functionName = $grandParentNode->name->toLowerString();

        return array_key_exists($functionName, $resultSettings) && $resultSettings[$functionName] !== false;
    }
}
