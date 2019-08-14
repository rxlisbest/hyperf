<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace HyperfTest\AsyncQueue\Stub;

use Hyperf\Contract\CodeDegenerateInterface;
use Hyperf\Contract\CodeGenerateInterface;
use Hyperf\Utils\Context;

class DemoModelMeta implements CodeDegenerateInterface
{
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function degenerate(): CodeGenerateInterface
    {
        $data = Context::get('test.async-queue.demo.model.' . $this->id);

        return new DemoModel($this->id, ...$data);
    }
}