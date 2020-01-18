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

use Generator;
use Infection\Mutator\Definition;
use Infection\Mutator\GetMutatorName;
use Infection\Mutator\Mutator;
use Infection\Mutator\MutatorCategory;
use PhpParser\Node;

/**
 * @internal
 */
final class ArrayItem implements Mutator
{
    use GetMutatorName;

    public static function getDefinition(): ?Definition
    {
        return new Definition(
            <<<'TXT'
Replaces a key-value pair (`[$key => $value]`) array declaration with a value array declaration
(`[$key > $value]`) where the key or the value are potentially impure (i.e. have a side-effect);
For example `[foo() => $b->bar]`.
TXT
            ,
            MutatorCategory::SEMANTIC_REDUCTION,
            <<<'TXT'
This mutation highlights the reliance of the side-effect(s) of the called key(s) and/or value(s)
- completely disregarding the actual values of the array. The array content should either be
checked or the impure calls should be made outside of the scope of the array.
TXT
        );
    }

    /**
     * @param Node\Expr\ArrayItem $node
     *
     * @return Generator<Node\Expr\BinaryOp\Greater>
     */
    public function mutate(Node $node): Generator
    {
        /** @var Node\Expr $key */
        $key = $node->key;
        /** @var Node\Expr $value */
        $value = $node->value;

        yield new Node\Expr\BinaryOp\Greater($key, $value, $node->getAttributes());
    }

    public function canMutate(Node $node): bool
    {
        return $node instanceof Node\Expr\ArrayItem && $node->key && ($this->isNodeWithSideEffects($node->value) || $this->isNodeWithSideEffects($node->key));
    }

    private function isNodeWithSideEffects(Node $node): bool
    {
        return
            // __get() can have side-effects
            $node instanceof Node\Expr\PropertyFetch ||
            // these clearly can have side-effects
            $node instanceof Node\Expr\MethodCall ||
            $node instanceof Node\Expr\FuncCall;
    }
}
